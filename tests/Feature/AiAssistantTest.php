<?php

use App\Ai\Agents\StoreAssistantAgent;
use App\Ai\Tools\GetLowStockProductsTool;
use App\Ai\Tools\GetPendingOrdersTool;
use App\Ai\Tools\GetTopCustomersTool;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Laravel\Ai\Events\ToolInvoked;
use Laravel\Ai\Responses\Data\ToolCall;
use Laravel\Ai\Tools\Request as ToolRequest;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

function actingAsDashboardUser(): User
{
    $user = User::factory()->create(['role' => 'admin']);

    Sanctum::actingAs($user);

    return $user;
}

it('requires authentication for the assistant endpoint', function () {
    $this->postJson('/api/ai/assistant', [
        'message' => 'أرني المنتجات التي أوشكت على النفاد',
    ])->assertUnauthorized();
});

it('requires a message', function () {
    actingAsDashboardUser();

    $this->postJson('/api/ai/assistant', [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['message']);
});

it('returns a safe Arabic error when no AI provider key is configured', function () {
    actingAsDashboardUser();
    config([
        'ai.default' => 'gemini',
        'ai.providers.gemini.key' => null,
    ]);

    StoreAssistantAgent::fake()->preventStrayPrompts();

    $this->postJson('/api/ai/assistant', [
        'message' => 'أرني المنتجات التي أوشكت على النفاد',
    ])->assertStatus(503)
        ->assertJsonPath('conversation_id', null)
        ->assertJsonFragment([
            'reply' => 'ميزة المساعد الذكي غير مفعّلة بعد. أضف مفتاح مزوّد الذكاء الاصطناعي في ملف .env ثم امسح كاش الإعدادات.',
        ]);

    StoreAssistantAgent::assertNeverPrompted();
});

it('does not prompt the AI for destructive actions', function () {
    actingAsDashboardUser();
    config(['ai.providers.openai.key' => 'testing-key']);

    StoreAssistantAgent::fake()->preventStrayPrompts();

    $this->postJson('/api/ai/assistant', [
        'message' => 'احذف هذا المنتج',
    ])->assertOk()
        ->assertJsonFragment([
            'conversation_id' => null,
        ]);

    StoreAssistantAgent::assertNeverPrompted();
});

it('returns low-stock products from the tool', function () {
    $low = Product::factory()->create([
        'name' => 'قهوة',
        'quantity' => 2,
        'price' => 15,
    ]);

    Product::factory()->create([
        'name' => 'شاي',
        'quantity' => 8,
        'price' => 10,
    ]);

    $payload = json_decode((string) (new GetLowStockProductsTool)->handle(
        new ToolRequest(['threshold' => 5])
    ), true, flags: JSON_THROW_ON_ERROR);

    expect($payload['threshold'])->toBe(5)
        ->and($payload['products'])->toHaveCount(1)
        ->and($payload['products'][0]['id'])->toBe($low->id)
        ->and($payload['products'][0]['quantity'])->toBe(2);
});

it('handles Arabic low-stock prompts through the store assistant agent', function () {
    actingAsDashboardUser();
    config([
        'ai.providers.openai.key' => 'testing-key',
        'ai.conversations.generate_title' => false,
    ]);

    StoreAssistantAgent::fake([
        'هذه المنتجات أوشكت على النفاد: قهوة - الكمية المتبقية: 2 - السعر: 15',
    ])->preventStrayPrompts();

    $this->postJson('/api/ai/assistant', [
        'message' => 'شنو المنتجات اللي قربت تخلص؟',
    ])->assertOk()
        ->assertJsonPath('reply', 'هذه المنتجات أوشكت على النفاد: قهوة - الكمية المتبقية: 2 - السعر: 15');

    StoreAssistantAgent::assertPrompted(
        fn ($prompt) => str_contains($prompt->prompt, 'قربت تخلص')
    );
});

it('returns top customers by total spent', function () {
    $topCustomer = Customer::factory()->create(['name' => 'Ahmed']);
    $otherCustomer = Customer::factory()->create(['name' => 'Sara']);

    Order::factory()->create([
        'customer_id' => $topCustomer->id,
        'status' => 'تم التوصيل',
        'total_price' => 3000,
    ]);

    Order::factory()->create([
        'customer_id' => $topCustomer->id,
        'status' => 'قيد التنفيذ',
        'total_price' => 1500,
    ]);

    Order::factory()->create([
        'customer_id' => $otherCustomer->id,
        'status' => 'تم التوصيل',
        'total_price' => 2000,
    ]);

    $payload = json_decode((string) (new GetTopCustomersTool)->handle(
        new ToolRequest(['limit' => 2])
    ), true, flags: JSON_THROW_ON_ERROR);

    expect($payload['metric'])->toBe('total_spent')
        ->and($payload['customers'])->toHaveCount(2)
        ->and($payload['customers'][0]['id'])->toBe($topCustomer->id)
        ->and($payload['customers'][0]['name'])->toBe('Ahmed')
        ->and($payload['customers'][0]['total_spent'])->toBe(4500)
        ->and($payload['customers'][0]['orders_count'])->toBe(2);
});

it('returns only pending orders from the tool', function () {
    $pendingCustomer = Customer::factory()->create(['name' => 'Ahmed Ali']);
    $processingCustomer = Customer::factory()->create(['name' => 'Sara Omar']);
    $completedCustomer = Customer::factory()->create(['name' => 'Done Customer']);

    $pendingOrder = Order::factory()->create([
        'customer_id' => $pendingCustomer->id,
        'status' => 'قيد التنفيذ',
        'total_price' => 250,
    ]);

    $processingOrder = Order::factory()->create([
        'customer_id' => $processingCustomer->id,
        'status' => 'processing',
        'total_price' => 180,
    ]);

    Order::factory()->create([
        'customer_id' => $completedCustomer->id,
        'status' => 'تم التوصيل',
        'total_price' => 500,
    ]);

    $payload = json_decode((string) (new GetPendingOrdersTool)->handle(
        new ToolRequest(['limit' => 10])
    ), true, flags: JSON_THROW_ON_ERROR);

    expect($payload['count'])->toBe(2)
        ->and($payload['orders'])->toHaveCount(2)
        ->and(collect($payload['orders'])->pluck('id')->all())->toContain($pendingOrder->id, $processingOrder->id)
        ->and(collect($payload['orders'])->pluck('customer')->all())->toContain('Ahmed Ali', 'Sara Omar');
});

it('excludes completed orders from pending orders', function () {
    $customer = Customer::factory()->create(['name' => 'Completed Customer']);

    Order::factory()->create([
        'customer_id' => $customer->id,
        'status' => 'delivered',
        'total_price' => 300,
    ]);

    Order::factory()->create([
        'customer_id' => $customer->id,
        'status' => 'completed',
        'total_price' => 400,
    ]);

    Order::factory()->create([
        'customer_id' => $customer->id,
        'status' => 'مكتمل',
        'total_price' => 500,
    ]);

    $payload = json_decode((string) (new GetPendingOrdersTool)->handle(
        new ToolRequest
    ), true, flags: JSON_THROW_ON_ERROR);

    expect($payload['count'])->toBe(0)
        ->and($payload['orders'])->toBe([]);
});

it('returns zero pending orders for an empty database', function () {
    $payload = json_decode((string) (new GetPendingOrdersTool)->handle(
        new ToolRequest
    ), true, flags: JSON_THROW_ON_ERROR);

    expect($payload['count'])->toBe(0)
        ->and($payload['orders'])->toBe([])
        ->and($payload['note'])->toContain('Do not invent');
});

it('handles Arabic pending order prompts through the pending orders tool', function () {
    actingAsDashboardUser();
    config([
        'ai.default' => 'gemini',
        'ai.providers.gemini.key' => 'testing-key',
        'ai.conversations.generate_title' => false,
    ]);

    $customer = Customer::factory()->create(['name' => 'Ahmed Ali']);
    Order::factory()->create([
        'customer_id' => $customer->id,
        'status' => 'قيد التنفيذ',
        'total_price' => 250,
    ]);

    Event::fake([ToolInvoked::class]);

    StoreAssistantAgent::fake([
        new ToolCall('call_1', 'GetPendingOrdersTool', ['limit' => 10]),
        'لديك 1 طلب قيد التنفيذ. 1. Ahmed Ali — 250 د.ل.',
    ]);

    $this->postJson('/api/ai/assistant', [
        'message' => 'أعطني الطلبات قيد التنفيذ',
    ])->assertOk()
        ->assertJsonPath('reply', 'لديك 1 طلب قيد التنفيذ. 1. Ahmed Ali — 250 د.ل.');

    Event::assertDispatched(
        ToolInvoked::class,
        fn (ToolInvoked $event): bool => $event->tool instanceof GetPendingOrdersTool
    );

    StoreAssistantAgent::assertPrompted(
        fn ($prompt) => str_contains($prompt->prompt, 'الطلبات قيد التنفيذ')
    );
});

it('does not modify the database when fetching pending orders', function () {
    $customer = Customer::factory()->create(['name' => 'Readonly Customer']);

    Order::factory()->create([
        'customer_id' => $customer->id,
        'status' => 'قيد التنفيذ',
        'total_price' => 250,
    ]);

    Order::factory()->create([
        'customer_id' => $customer->id,
        'status' => 'تم التوصيل',
        'total_price' => 500,
    ]);

    $before = Order::query()
        ->orderBy('id')
        ->get(['id', 'customer_id', 'status', 'total_price', 'created_at', 'updated_at'])
        ->toArray();

    (new GetPendingOrdersTool)->handle(new ToolRequest(['limit' => 10]));

    $after = Order::query()
        ->orderBy('id')
        ->get(['id', 'customer_id', 'status', 'total_price', 'created_at', 'updated_at'])
        ->toArray();

    expect($after)->toBe($before);
});

it('returns no fabricated customers when there are no orders', function () {
    Customer::factory()->create(['name' => 'No Orders Customer']);

    $payload = json_decode((string) (new GetTopCustomersTool)->handle(
        new ToolRequest
    ), true, flags: JSON_THROW_ON_ERROR);

    expect($payload['metric'])->toBe('total_spent')
        ->and($payload['customers'])->toBe([])
        ->and($payload['note'])->toContain('Do not invent');
});

it('handles Arabic top customer prompts through the top customers tool', function () {
    actingAsDashboardUser();
    config([
        'ai.default' => 'gemini',
        'ai.providers.gemini.key' => 'testing-key',
        'ai.conversations.generate_title' => false,
    ]);

    $customer = Customer::factory()->create(['name' => 'Ahmed']);
    Order::factory()->create([
        'customer_id' => $customer->id,
        'status' => 'تم التوصيل',
        'total_price' => 4500,
    ]);

    Event::fake([ToolInvoked::class]);

    StoreAssistantAgent::fake([
        new ToolCall('call_1', 'GetTopCustomersTool', ['limit' => 1]),
        'أفضل زبون لديك هو Ahmed. إجمالي مشترياته: 4500 د.ل. عدد طلباته: 1 طلب.',
    ]);

    $this->postJson('/api/ai/assistant', [
        'message' => 'من أكثر زبون اشترى مني؟',
    ])->assertOk()
        ->assertJsonPath('reply', 'أفضل زبون لديك هو Ahmed. إجمالي مشترياته: 4500 د.ل. عدد طلباته: 1 طلب.');

    Event::assertDispatched(
        ToolInvoked::class,
        fn (ToolInvoked $event): bool => $event->tool instanceof GetTopCustomersTool
    );

    StoreAssistantAgent::assertPrompted(
        fn ($prompt) => str_contains($prompt->prompt, 'أكثر زبون')
    );
});
