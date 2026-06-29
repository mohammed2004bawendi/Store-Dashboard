<?php

use App\Ai\Agents\OrderSearchAgent;
use App\Ai\OrderSmartSearch;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

beforeEach(function () {
    Cache::flush();

    config([
        'ai.default' => 'gemini',
        'ai.providers.gemini.key' => 'testing-key',
    ]);

    Sanctum::actingAs(User::factory()->create(['role' => 'admin']));
});

function createSearchOrder(string $customerName, string $status = 'قيد التنفيذ', int $total = 100, ?string $createdAt = null): Order
{
    $customer = Customer::factory()->create([
        'name' => $customerName,
        'phone' => '091'.fake()->numerify('#######'),
    ]);

    return Order::factory()->create([
        'customer_id' => $customer->id,
        'status' => $status,
        'total_price' => $total,
        'created_at' => $createdAt ?? now(),
    ]);
}

it('filters Arabic pending orders query through structured AI output', function () {
    $pending = createSearchOrder('Ahmed Pending', 'قيد التنفيذ', 250);
    createSearchOrder('Sara Done', 'تم التوصيل', 500);

    OrderSearchAgent::fake([
        ['status' => 'pending'],
    ])->preventStrayPrompts();

    $this->getJson('/api/orders?search='.urlencode('الطلبات قيد التنفيذ'))
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $pending->id);

    OrderSearchAgent::assertPrompted(
        fn ($prompt) => str_contains($prompt->prompt, 'الطلبات قيد التنفيذ')
    );
});

it('filters English pending orders query through structured AI output', function () {
    $pending = createSearchOrder('English Pending', 'processing', 180);
    createSearchOrder('English Delivered', 'delivered', 300);

    OrderSearchAgent::fake([
        ['status' => 'pending'],
    ])->preventStrayPrompts();

    $this->getJson('/api/orders?search='.urlencode('pending orders'))
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $pending->id);
});

it('filters by customer name from structured output', function () {
    $order = createSearchOrder('Ahmed Ali', 'قيد التنفيذ', 120);
    createSearchOrder('Mohamed Salem', 'قيد التنفيذ', 130);

    OrderSearchAgent::fake([
        ['customer_name' => 'Ahmed'],
    ])->preventStrayPrompts();

    $this->getJson('/api/orders?search='.urlencode('الطلبات الخاصة بأحمد'))
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $order->id);
});

it('filters by product name from structured output', function () {
    $rice = Product::factory()->create(['name' => 'Rice']);
    $oil = Product::factory()->create(['name' => 'Oil']);
    $riceOrder = createSearchOrder('Rice Buyer', 'قيد التنفيذ', 90);
    $oilOrder = createSearchOrder('Oil Buyer', 'قيد التنفيذ', 70);

    $riceOrder->products()->attach($rice->id, ['quantity' => 2, 'price' => 20]);
    $oilOrder->products()->attach($oil->id, ['quantity' => 1, 'price' => 15]);

    OrderSearchAgent::fake([
        ['product_name' => 'Rice'],
    ])->preventStrayPrompts();

    $this->getJson('/api/orders?search='.urlencode('orders containing rice'))
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $riceOrder->id);
});

it('filters today orders from structured output', function () {
    $today = createSearchOrder('Today Customer', 'قيد التنفيذ', 100, now()->setTime(12, 0)->toDateTimeString());
    createSearchOrder('Yesterday Customer', 'قيد التنفيذ', 100, now()->subDay()->setTime(12, 0)->toDateTimeString());

    OrderSearchAgent::fake([
        ['created_today' => true],
    ])->preventStrayPrompts();

    $this->getJson('/api/orders?search='.urlencode('الطلبات التي تم إنشاؤها اليوم'))
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $today->id);
});

it('returns last 10 orders from structured output', function () {
    for ($i = 1; $i <= 12; $i++) {
        createSearchOrder("Customer {$i}", 'قيد التنفيذ', 100 + $i, now()->subMinutes(12 - $i)->toDateTimeString());
    }

    OrderSearchAgent::fake([
        ['limit' => 10, 'sort' => 'latest'],
    ])->preventStrayPrompts();

    $this->getJson('/api/orders?search='.urlencode('آخر 10 طلبات'))
        ->assertOk()
        ->assertJsonCount(10, 'data')
        ->assertJsonPath('data.0.customer.name', 'Customer 12');
});

it('falls back to traditional search when AI interpretation fails', function () {
    $order = createSearchOrder('Ahmed Fallback', 'قيد التنفيذ', 150);
    createSearchOrder('Other Customer', 'قيد التنفيذ', 100);

    OrderSearchAgent::fake(fn () => throw new RuntimeException('AI is unavailable'));

    $this->getJson('/api/orders?search='.urlencode('Ahmed'))
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $order->id);
});

it('does not allow SQL injection through structured filters', function () {
    createSearchOrder('Safe Customer', 'قيد التنفيذ', 100);
    createSearchOrder('Another Safe Customer', 'قيد التنفيذ', 200);

    OrderSearchAgent::fake([
        ['customer_name' => "%' OR 1=1 --"],
    ])->preventStrayPrompts();

    $this->getJson('/api/orders?search='.urlencode("orders for %' OR 1=1 --"))
        ->assertOk()
        ->assertJsonCount(0, 'data');
});

it('normalizes structured output safely', function () {
    $filters = app(OrderSmartSearch::class)->normalizeStructuredFilters([
        'status' => 'processing',
        'limit' => 500,
        'sort' => 'sideways',
        'min_products_count' => 3,
        'unsafe_sql' => 'DROP TABLE orders',
    ]);

    expect($filters)->toBe([
        'status' => 'pending',
        'limit' => 50,
        'min_products_count' => 3,
    ]);
});
