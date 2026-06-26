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

        $orders = Order::with('customer')->get();

        SimpleExcelWriter::create($path)
            ->addRows($orders->map(function (Order $order) {
                return [
                    "\u{0631}\u{0642}\u{0645} \u{0627}\u{0644}\u{0637}\u{0644}\u{0628}" => $order->id,
                    "\u{0627}\u{0644}\u{0639}\u{0645}\u{064A}\u{0644}" => $order->customer->name ?? '',
                    "\u{0627}\u{0644}\u{062D}\u{0627}\u{0644}\u{0629}" => $order->status,
                    "\u{0627}\u{0644}\u{0625}\u{062C}\u{0645}\u{0627}\u{0644}\u{064A}" => $order->total_price,
                    "\u{062A}\u{0627}\u{0631}\u{064A}\u{062E} \u{0627}\u{0644}\u{0625}\u{0646}\u{0634}\u{0627}\u{0621}" => $order->created_at->format('Y-m-d H:i:s'),
                ];
            }));

        return [
            'filename' => $filename,
            'path' => $path,
        ];
    }
}
