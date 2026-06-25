<?php

namespace App\Domain\Orders\Actions;

use App\Models\Customer;
use App\Models\Order;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Collection;

class ListOrderProductsAction
{
    public function execute(Customer $customer, Order $order, ?string $search): Collection
    {
        if ($customer->id != $order->customer_id) {
            throw new HttpResponseException(response()->json("\u{0647}\u{0630}\u{0627} \u{0627}\u{0644}\u{0632}\u{0628}\u{0648}\u{0646} \u{0644}\u{0627} \u{064A}\u{0645}\u{062A}\u{0644}\u{0643} \u{0647}\u{0630}\u{0647} \u{0627}\u{0644}\u{0637}\u{0644}\u{0628}\u{064A}\u{0629}"));
        }

        return $order->products()
            ->when($search, fn ($query, string $search) => $query->where('name', 'like', "%{$search}%"))
            ->get();
    }
}
