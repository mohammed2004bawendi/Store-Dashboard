<?php

namespace App\Ai\Tools;

use App\Models\Order;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Database\Eloquent\Builder;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class GetPendingOrdersTool implements Tool
{
    /**
     * Values discovered from order forms, requests, seeders, actions, and tests.
     *
     * @var array<int, string>
     */
    private const PENDING_STATUSES = [
        'قيد التنفيذ',
        'processing',
        'pending',
        'in_progress',
        'in progress',
        'قيد التجهيز',
        'جديد',
    ];

    public function description(): Stringable|string
    {
        return 'Get orders that are pending, processing, in progress, or otherwise incomplete. Use this for pending orders, incomplete orders, orders in progress, and processing orders.';
    }

    public function handle(Request $request): Stringable|string
    {
        $limit = max(1, min((int) $request->integer('limit', 10), 50));
        $query = $this->pendingOrdersQuery();
        $count = (clone $query)->count();

        $orders = $query
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get()
            ->map(fn (Order $order): array => [
                'id' => $order->id,
                'customer' => $order->customer?->name,
                'status' => $order->status,
                'total' => is_numeric($order->total_price) ? (float) $order->total_price : $order->total_price,
                'created_at' => $order->created_at?->format('Y-m-d H:i:s'),
            ])
            ->values();

        return json_encode([
            'count' => $count,
            'returned' => $orders->count(),
            'metric' => 'pending_orders',
            'status_column' => 'status',
            'total_column' => 'total_price',
            'customer_relationship' => 'customer',
            'pending_statuses' => self::PENDING_STATUSES,
            'orders' => $orders,
            'note' => $count === 0
                ? 'No pending or incomplete orders were found. Do not invent orders.'
                : null,
        ], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'limit' => $schema->integer()
                ->description('Maximum number of pending orders to return. Default is 10.')
                ->min(1)
                ->max(50),
        ];
    }

    private function pendingOrdersQuery(): Builder
    {
        return Order::query()
            ->with('customer')
            ->select(['id', 'customer_id', 'status', 'total_price', 'created_at'])
            ->whereIn('status', self::PENDING_STATUSES);
    }
}
