<?php

namespace App\Domain\Orders\Actions;

use App\Domain\Orders\Data\OrderItemData;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Notifications\quantityReminder;
use Exception;
use Illuminate\Support\Facades\Notification;

class AddOrderItemsAction
{
    /**
     * @param array<int, OrderItemData> $items
     */
    public function execute(Order $order, array $items): float
    {
        $total = 0;

        foreach ($items as $item) {
            $product = Product::findOrFail($item->id);
            $quantity = $item->quantity;

            if ($product->quantity < $quantity) {
                throw new Exception("\u{0627}\u{0644}\u{0643}\u{0645}\u{064A}\u{0629} \u{0627}\u{0644}\u{0645}\u{0637}\u{0644}\u{0648}\u{0628}\u{0629} \u{0645}\u{0646} \u{0627}\u{0644}\u{0645}\u{0646}\u{062A}\u{062C} {$product->name} \u{063A}\u{064A}\u{0631} \u{0645}\u{062A}\u{0648}\u{0641}\u{0631}\u{0629}.");
            }

            if ($product->quantity < 2 && $product->quantity >= 0) {
                Notification::send(User::all(), new quantityReminder($product));
            }

            $order->products()->attach($product->id, [
                'quantity' => $quantity,
                'price' => $product->price,
            ]);

            $product->decrement('quantity', $quantity);

            $total += $quantity * $product->price;
        }

        return $total;
    }
}
