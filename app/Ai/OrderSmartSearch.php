<?php

namespace App\Ai;

use App\Ai\Agents\OrderSearchAgent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Laravel\Ai\Exceptions\AiException;
use Throwable;

class OrderSmartSearch
{
    private const CACHE_SECONDS = 300;

    private const PENDING_STATUSES = [
        'قيد التنفيذ',
        'processing',
        'pending',
        'in_progress',
        'in progress',
        'قيد التجهيز',
        'جديد',
    ];

    private const COMPLETED_STATUSES = [
        'تم التوصيل',
        'delivered',
        'completed',
        'مكتمل',
    ];

    private const CANCELLED_STATUSES = [
        'ملغي',
        'cancelled',
        'canceled',
    ];

    /**
     * @return array{query: Builder, filters: array<string, mixed>, ai_applied: bool, fallback_search: string|null, limit: int|null}
     */
    public function apply(Builder $query, ?string $search): array
    {
        $search = $this->cleanSearch($search);

        if ($search === '') {
            return $this->result($query);
        }

        $filters = $this->interpret($search);

        if ($filters === []) {
            $this->applyTraditionalSearch($query, $search);

            return $this->result($query, aiApplied: false, fallbackSearch: $search);
        }

        $filters = $this->normalizeStructuredFilters($filters);
        $applied = $this->applyStructuredFilters($query, $filters);

        if (! $applied) {
            $fallback = $filters['fallback_search'] ?? $search;
            $this->applyTraditionalSearch($query, $fallback);

            return $this->result($query, $filters, false, $fallback);
        }

        return $this->result(
            query: $query,
            filters: $filters,
            aiApplied: true,
            fallbackSearch: $filters['fallback_search'] ?? null,
            limit: $filters['limit'] ?? null,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function normalizeStructuredFilters(array $filters): array
    {
        $allowed = [
            'status',
            'customer_name',
            'customer_phone_prefix',
            'product_name',
            'created_today',
            'limit',
            'sort',
            'min_products_count',
            'fallback_search',
        ];

        $filters = array_intersect_key($filters, array_flip($allowed));

        foreach (['status', 'customer_name', 'customer_phone_prefix', 'product_name', 'sort', 'fallback_search'] as $key) {
            if (isset($filters[$key]) && is_string($filters[$key])) {
                $filters[$key] = trim(mb_substr($filters[$key], 0, 100));
            }

            if (($filters[$key] ?? null) === '') {
                unset($filters[$key]);
            }
        }

        if (isset($filters['status'])) {
            $status = mb_strtolower((string) $filters['status']);
            $filters['status'] = match ($status) {
                'pending', 'processing', 'in_progress', 'in progress', 'incomplete', 'قيد التنفيذ', 'قيد التجهيز', 'معلقة', 'لم تكتمل' => 'pending',
                'completed', 'delivered', 'complete', 'تم التوصيل', 'مكتملة', 'مكتمل' => 'completed',
                'cancelled', 'canceled', 'ملغي' => 'cancelled',
                default => null,
            };

            if ($filters['status'] === null) {
                unset($filters['status']);
            }
        }

        if (isset($filters['sort'])) {
            $sort = mb_strtolower((string) $filters['sort']);
            $filters['sort'] = in_array($sort, ['latest', 'oldest'], true) ? $sort : null;

            if ($filters['sort'] === null) {
                unset($filters['sort']);
            }
        }

        foreach (['limit', 'min_products_count'] as $key) {
            if (isset($filters[$key])) {
                $filters[$key] = max(1, min((int) $filters[$key], $key === 'limit' ? 50 : 100));
            }
        }

        if (isset($filters['created_today'])) {
            $filters['created_today'] = filter_var($filters['created_today'], FILTER_VALIDATE_BOOLEAN);
        }

        return array_filter($filters, fn ($value) => $value !== null && $value !== '');
    }

    protected function applyStructuredFilters(Builder $query, array $filters): bool
    {
        $applied = false;

        if (isset($filters['status'])) {
            $query->whereIn('status', $this->statusesForAlias($filters['status']));
            $applied = true;
        }

        if (isset($filters['customer_name'])) {
            $name = $filters['customer_name'];
            $query->whereHas('customer', fn (Builder $query) => $query->where('name', 'like', "%{$name}%"));
            $applied = true;
        }

        if (isset($filters['customer_phone_prefix'])) {
            $prefix = $filters['customer_phone_prefix'];
            $query->whereHas('customer', fn (Builder $query) => $query->where('phone', 'like', "{$prefix}%"));
            $applied = true;
        }

        if (isset($filters['product_name'])) {
            $productName = $filters['product_name'];
            $query->whereHas('products', fn (Builder $query) => $query->where('name', 'like', "%{$productName}%"));
            $applied = true;
        }

        if (($filters['created_today'] ?? false) === true) {
            $query->whereDate('created_at', Carbon::today()->toDateString());
            $applied = true;
        }

        if (isset($filters['min_products_count'])) {
            $query->has('products', '>=', (int) $filters['min_products_count']);
            $applied = true;
        }

        match ($filters['sort'] ?? 'latest') {
            'oldest' => $query->oldest('created_at'),
            default => $query->latest('created_at'),
        };

        return $applied || isset($filters['sort'], $filters['limit']);
    }

    public function applyTraditionalSearch(Builder $query, string $search): void
    {
        $query->where(function (Builder $query) use ($search) {
            if (ctype_digit($search)) {
                $query->orWhere('id', (int) $search);
            }

            $query->orWhereHas('customer', fn (Builder $query) => $query->where('name', 'like', "%{$search}%"))
                ->orWhereHas('products', fn (Builder $query) => $query->where('name', 'like', "%{$search}%"));
        });
    }

    /**
     * @return array<string, mixed>
     */
    protected function interpret(string $search): array
    {
        if (! $this->hasConfiguredProviderKey()) {
            return $this->localHeuristicFilters($search);
        }

        try {
            return Cache::remember($this->cacheKey($search), self::CACHE_SECONDS, function () use ($search) {
                $response = OrderSearchAgent::make()->prompt($this->buildPrompt($search), timeout: 20);

                return $response->structured ?? [];
            });
        } catch (AiException|Throwable $exception) {
            Log::warning('Order smart search AI interpretation failed.', [
                'exception' => $exception::class,
                'message' => $exception->getMessage(),
            ]);

            return $this->localHeuristicFilters($search);
        }
    }

    /**
     * Small deterministic fallback for common dashboard searches when AI is unavailable.
     *
     * @return array<string, mixed>
     */
    protected function localHeuristicFilters(string $search): array
    {
        $normalized = mb_strtolower($search);

        $filters = [];

        if (str_contains($normalized, 'قيد التنفيذ') || str_contains($normalized, 'pending') || str_contains($normalized, 'processing') || str_contains($normalized, 'incomplete')) {
            $filters['status'] = 'pending';
        } elseif (str_contains($normalized, 'تم التوصيل') || str_contains($normalized, 'مكتمل') || str_contains($normalized, 'completed') || str_contains($normalized, 'delivered')) {
            $filters['status'] = 'completed';
        } elseif (str_contains($normalized, 'ملغي') || str_contains($normalized, 'cancelled') || str_contains($normalized, 'canceled')) {
            $filters['status'] = 'cancelled';
        }

        if (str_contains($normalized, 'اليوم') || str_contains($normalized, 'today')) {
            $filters['created_today'] = true;
        }

        if (preg_match('/(?:آخر|last)\s+(\d{1,2})/u', $search, $matches)) {
            $filters['limit'] = (int) $matches[1];
            $filters['sort'] = 'latest';
        }

        if (preg_match('/(?:أكثر من|more than)\s+(\d{1,2})\s+(?:منتجات|products)/u', $search, $matches)) {
            $filters['min_products_count'] = (int) $matches[1] + 1;
        }

        if (preg_match('/(?:رقمه يبدأ بـ|phone starts with)\s*([0-9]+)/u', $search, $matches)) {
            $filters['customer_phone_prefix'] = $matches[1];
        }

        return $filters;
    }

    protected function statusesForAlias(string $status): array
    {
        return match ($status) {
            'pending' => self::PENDING_STATUSES,
            'completed' => self::COMPLETED_STATUSES,
            'cancelled' => self::CANCELLED_STATUSES,
            default => [$status],
        };
    }

    protected function buildPrompt(string $search): string
    {
        return 'Search query: '.mb_substr($search, 0, 300);
    }

    protected function cleanSearch(?string $search): string
    {
        return trim(mb_substr((string) $search, 0, 300));
    }

    protected function cacheKey(string $search): string
    {
        return 'orders.smart_search.'.md5(mb_strtolower($search));
    }

    protected function hasConfiguredProviderKey(): bool
    {
        $provider = config('ai.default', 'openai');

        return is_string($provider) && filled(config("ai.providers.{$provider}.key"));
    }

    /**
     * @return array{query: Builder, filters: array<string, mixed>, ai_applied: bool, fallback_search: string|null, limit: int|null}
     */
    private function result(
        Builder $query,
        array $filters = [],
        bool $aiApplied = false,
        ?string $fallbackSearch = null,
        ?int $limit = null,
    ): array {
        return [
            'query' => $query,
            'filters' => $filters,
            'ai_applied' => $aiApplied,
            'fallback_search' => $fallbackSearch,
            'limit' => $limit,
        ];
    }
}
