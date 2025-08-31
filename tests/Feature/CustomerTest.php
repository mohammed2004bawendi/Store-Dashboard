<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class CustomerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @var \App\Models\User
     */
    protected $user;

    protected function setUp(): void
    {
        parent::setUp();

        Gate::shouldReceive('authorize')->andReturn(true); // تجاوز Gate
        $this->withoutExceptionHandling();
        $this->withoutMiddleware(); // تجاوز Middleware

        $this->user = User::factory()->create();
        $this->actingAs($this->user);
    }

    public function test_can_create_a_customer(): void
    {
        $data = [
            'name' => 'أحمد علي',
            'phone' => '0912345678',
            'address' => 'طرابلس',
        ];

        $response = $this->postJson('/api/customers', $data);

        $response->assertCreated()
            ->assertJsonPath('data.name', 'أحمد علي');

        $this->assertDatabaseHas('customers', ['phone' => '0912345678']);
    }

    public function test_can_list_customers(): void
    {
        Customer::factory()->count(5)->create();

        $response = $this->getJson('/api/customers');

        $response->assertOk()
            ->assertJsonStructure(['data']);
    }

    public function test_can_show_a_customer(): void
    {
        $customer = Customer::factory()->create();

        $response = $this->getJson("/api/customers/{$customer->id}");

        $response->assertOk()
            ->assertJsonPath('data.id', $customer->id)
            ->assertJsonPath('data.name', $customer->name);
    }

    public function test_can_update_a_customer(): void
    {
        $customer = Customer::factory()->create();

        $updated = [
            'name' => 'محدث',
            'phone' => '09999999',
            'address' => 'مصراتة',
        ];

        $response = $this->putJson("/api/customers/{$customer->id}", $updated);

        $response->assertOk()
            ->assertJsonPath('data.name', 'محدث');

        $this->assertDatabaseHas('customers', ['phone' => '09999999']);
    }

    public function test_can_delete_a_customer(): void
    {
        $customer = Customer::factory()->create();

        $response = $this->deleteJson("/api/customers/{$customer->id}");

        $response->assertOk()
            ->assertJsonFragment(['message' => 'Customer deleted successfully!']);

        $this->assertDatabaseMissing('customers', ['id' => $customer->id]);
    }
}
