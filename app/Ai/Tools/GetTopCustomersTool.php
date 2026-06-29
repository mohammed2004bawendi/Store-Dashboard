<?php

namespace App\Ai\Tools;

use App\Models\Customer;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Database\Eloquent\Builder;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class GetTopCustomersTool implements Tool
{
    /**
     * @var array<int, string>
     */
    private const COMPLETED_STATUSES = [
        'تم التوصيل',
        'مكتمل',
        'delivered',
        'completed',
    ];

    public function description(): Stringable|string
    {
        return 'Get the best customers ranked by total money spent, with order count as a secondary metric. Use this for top customers, best customers, highest spending customers, or customers with the most orders.';
    }

    public function handle(Request $request): Stringable|string
    {
        $limit = max(1, min((int) $request->integer('limit', 5), 25));
        $period = $request->string('period', 'all_time')->toString();
        $completedOnly = $request->boolean('completed_only', false);

        if (! in_array($period, ['all_time', 'current_month'], true)) {
            $period = 'all_time';
        }

        $customers = Customer::query()
            ->whereHas('orders', fn (Builder $query) => $this->applyOrderFilters($query, $period, $completedOnly))
            ->withCount([
                'orders as orders_count' => fn (Builder $query) => $this->applyOrderFilters($query, $period, $completedOnly),
            ])
            ->withSum([
                'orders as total_spent' => fn (Builder $query) => $this->applyOrderFilters($query, $period, $completedOnly),
            ], 'total_price')
            ->orderByDesc('total_spent')
            ->orderByDesc('orders_count')
            ->limit($limit)
            ->get()
            ->map(fn (Customer $customer): array => [
                'id' => $customer->id,
                'name' => $customer->name,
                'total_spent' => (float) ($customer->total_spent ?? 0),
                'orders_count' => (int) $customer->orders_count,
            ])
            ->values();

        return json_encode([
            'metric' => 'total_spent',
            'period' => $period,
            'completed_only' => $completedOnly,
            'completed_statuses' => self::COMPLETED_STATUSES,
            'customers' => $customers,
            'note' => $customers->isEmpty()
                ? 'No matching customer order data was found. Do not invent customer names or totals.'
                : null,
        ], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'limit' => $schema->integer()
                ->description('Maximum number of customers to return. Default is 5.')
                ->min(1)
                ->max(25),
            'period' => $schema->string()
                ->description('Use all_time by default, or current_month when the user asks about this month.'),
            'completed_only' => $schema->boolean()
                ->description('Set true only when the user specifically asks for completed or delivered orders.'),
        ];
    }

    private function applyOrderFilters(Builder $query, string $period, bool $completedOnly): void
    {
        if ($completedOnly) {
            $query->whereIn('status', self::COMPLETED_STATUSES);
        }

        if ($period === 'current_month') {
            $query->whereBetween('created_at', [
                now()->startOfMonth(),
                now()->endOfMonth(),
            ]);
        }
    }
}
