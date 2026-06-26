<?php

namespace App\Domain\Orders\Actions;

use App\Models\Order;
use Spatie\SimpleExcel\SimpleExcelWriter;

class ExportOrdersAction
{
    public function execute(): array
    {
        $filename = 'orders_' . now()->timestamp . '.xlsx';
        $path = storage_path("app/public/{$filename}");

        $orders = Order::with(['customer:id,name'])->get();

        SimpleExcelWriter::create($path)
            ->addRows($orders->map(function (Order $order) {
                return [
                    'Order ID' => $order->id,
                    'Customer' => $order->customer->name ?? '',
                    'Status' => $order->status,
                    'Total' => $order->total_price,
                    'Created At' => $order->created_at->format('Y-m-d H:i:s'),
                ];
            }));

        return [
            'filename' => $filename,
            'path' => $path,
        ];
    }
}
