<?php

namespace Tests\Feature;

use App\Domain\Orders\Actions\CreateOrderAction;
use App\Domain\Orders\Actions\DeleteOrderAction;
use App\Domain\Orders\Actions\ListOrdersAction;
use App\Domain\Orders\Actions\UpdateOrderAction;
use App\Domain\Orders\Data\CreateOrderData;
use App\Domain\Orders\Data\OrderFiltersData;
use App\Domain\Orders\Data\UpdateOrderData;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class OrderActionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_order_action_creates_customer_order_items_and_total(): void
    {
        Notification::fake();

        $product = Product::factory()->create([
            'price' => 25,
            'quantity' => 10,
        ]);

        app(CreateOrderAction::class)->execute(
            CreateOrderData::fromArray([
                'name' => 'Ahmed',
                'phone' => '0912345678',
                'address' => 'Tripoli',
                'status' => 'processing',
                'products' => [
                    ['id' => $product->id, 'quantity' => 2],
                ],
            ])
        );

        $order = Order::first();

        $this->assertDatabaseHas('customers', ['phone' => '0912345678']);
        $this->assertSame(50, $order->total_price);
        $this->assertSame(8, $product->fresh()->quantity);
        $this->assertDatabaseHas('order_product', [
            'order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => 2,
        ]);
    }

    public function test_update_order_action_replaces_products_and_recalculates_total(): void
    {
        Notification::fake();

        $customer = Customer::factory()->create();
        $product = Product::factory()->create([
            'price' => 10,
            'quantity' => 8,
        ]);

        $order = Order::factory()->create([
            'customer_id' => $customer->id,
            'total_price' => 20,
        ]);

        $order->products()->attach($product->id, [
            'quantity' => 2,
            'price' => 10,
        ]);

        app(UpdateOrderAction::class)->execute(
            $order,
            UpdateOrderData::fromArray([
                'products' => [
                    ['id' => $product->id, 'quantity' => 3],
                ],
            ])
        );

        $this->assertSame(30, $order->fresh()->total_price);
        $this->assertSame(7, $product->fresh()->quantity);
        $this->assertDatabaseHas('order_product', [
            'order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => 3,
        ]);
    }

    public function test_delete_order_action_restores_product_quantities_and_deletes_order(): void
    {
        $customer = Customer::factory()->create();
        $product = Product::factory()->create(['quantity' => 8]);
        $order = Order::factory()->create(['customer_id' => $customer->id]);

        $order->products()->attach($product->id, [
            'quantity' => 2,
            'price' => $product->price,
        ]);

        app(DeleteOrderAction::class)->execute($order);

        $this->assertSame(10, $product->fresh()->quantity);
        $this->assertDatabaseMissing('orders', ['id' => $order->id]);
        $this->assertDatabaseMissing('order_product', ['order_id' => $order->id]);
    }

    public function test_list_orders_action_applies_filters(): void
    {
        Cache::flush();

        $customer = Customer::factory()->create(['name' => 'Ahmed Ali']);
        Order::factory()->create([
            'customer_id' => $customer->id,
            'status' => 'processing',
            'total_price' => 100,
        ]);

        Order::factory()->create([
            'customer_id' => Customer::factory()->create()->id,
            'status' => 'delivered',
            'total_price' => 50,
        ]);

        $orders = app(ListOrdersAction::class)->execute(
            OrderFiltersData::fromArray([
                'search' => 'Ahmed',
                'status' => 'processing',
            ])
        );

        $this->assertSame(1, $orders->total());
        $this->assertSame(100, $orders->items()[0]->total_price);
    }
}
