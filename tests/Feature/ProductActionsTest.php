<?php

namespace Tests\Feature;

use App\Domain\Products\Actions\CountProductCustomersAction;
use App\Domain\Products\Actions\CreateProductAction;
use App\Domain\Products\Actions\DeleteProductAction;
use App\Domain\Products\Actions\ListProductsAction;
use App\Domain\Products\Actions\UpdateProductAction;
use App\Domain\Products\Data\CreateProductData;
use App\Domain\Products\Data\ProductFiltersData;
use App\Domain\Products\Data\UpdateProductData;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Notifications\quantityReminder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class ProductActionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_product_action_persists_product(): void
    {
        $product = app(CreateProductAction::class)->execute(
            CreateProductData::fromArray([
                'name' => 'Coffee',
                'description' => 'Ground coffee',
                'price' => 25,
                'quantity' => 10,
                'status' => 'available',
            ])
        );

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'name' => 'Coffee',
        ]);
    }

    public function test_update_product_action_updates_product_and_notifies_when_quantity_is_low(): void
    {
        Notification::fake();

        User::factory()->create();
        $product = Product::factory()->create(['quantity' => 10]);

        app(UpdateProductAction::class)->execute(
            $product,
            UpdateProductData::fromArray(['quantity' => 1])
        );

        $this->assertSame(1, $product->fresh()->quantity);
        Notification::assertSentTo(User::all(), quantityReminder::class);
    }

    public function test_delete_product_action_removes_product(): void
    {
        $product = Product::factory()->create();

        app(DeleteProductAction::class)->execute($product);

        $this->assertDatabaseMissing('products', [
            'id' => $product->id,
        ]);
    }

    public function test_list_products_action_applies_filters(): void
    {
        Cache::flush();

        Product::factory()->create([
            'name' => 'Coffee Beans',
            'price' => 20,
            'quantity' => 5,
            'status' => 'available',
        ]);

        Product::factory()->create([
            'name' => 'Tea Leaves',
            'price' => 10,
            'quantity' => 2,
            'status' => 'available',
        ]);

        $products = app(ListProductsAction::class)->execute(
            ProductFiltersData::fromArray([
                'search' => 'Coffee',
                'min_price' => 15,
                'status' => 'available',
            ])
        );

        $this->assertSame(1, $products->total());
        $this->assertSame('Coffee Beans', $products->items()[0]->name);
    }

    public function test_count_product_customers_action_counts_distinct_customers(): void
    {
        $product = Product::factory()->create();
        $customer = Customer::factory()->create();
        $order = Order::factory()->create(['customer_id' => $customer->id]);

        $order->products()->attach($product->id, [
            'quantity' => 2,
            'price' => $product->price,
        ]);

        $count = app(CountProductCustomersAction::class)->execute($product);

        $this->assertSame(1, $count);
    }
}
