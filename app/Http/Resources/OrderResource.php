<?php

namespace App\Http\Resources;

use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
public function toArray(Request $request): array
{



    return [

    'id' => $this->id,
    'customer' => [
        'id' => $this->customer_id,
        'name' => $this->customer->name,
        'address' => $this->customer->address,
    ],
   'products' => $this->products->map(function ($product) {
        return [
            'id' => $product->id,
            'name' => $product->name,
            'price' => $product->price,
            'quantity' => $product->pivot->quantity,
        ];
    }),
    'total_price' => $this->total_price,
    'status' => $this->status,
    'created_at' => $this->created_at,
];

}


}


