<?php

namespace Tests\Feature;

use App\Domain\Customers\Actions\CreateCustomerAction;
use App\Domain\Customers\Actions\DeleteCustomerAction;
use App\Domain\Customers\Actions\ListCustomersAction;
use App\Domain\Customers\Actions\UpdateCustomerAction;
use App\Domain\Customers\Data\CreateCustomerData;
use App\Domain\Customers\Data\CustomerFiltersData;
use App\Domain\Customers\Data\UpdateCustomerData;
use App\Models\Customer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class CustomerActionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_customer_action_persists_customer(): void
    {
        $customer = app(CreateCustomerAction::class)->execute(
            CreateCustomerData::fromArray([
                'name' => 'Ahmed Ali',
                'phone' => '0912345678',
                'address' => 'Tripoli',
            ])
        );

        $this->assertDatabaseHas('customers', [
            'id' => $customer->id,
            'phone' => '0912345678',
        ]);
    }

    public function test_update_customer_action_updates_only_provided_fields(): void
    {
        $customer = Customer::factory()->create([
            'name' => 'Original',
            'phone' => '0911111111',
            'address' => 'Tripoli',
        ]);

        $updated = app(UpdateCustomerAction::class)->execute(
            $customer,
            UpdateCustomerData::fromArray([
                'name' => 'Updated',
            ])
        );

        $this->assertSame('Updated', $updated->fresh()->name);
        $this->assertSame('0911111111', $updated->fresh()->phone);
    }

    public function test_delete_customer_action_removes_customer(): void
    {
        $customer = Customer::factory()->create();

        app(DeleteCustomerAction::class)->execute($customer);

        $this->assertDatabaseMissing('customers', [
            'id' => $customer->id,
        ]);
    }

    public function test_list_customers_action_applies_filters(): void
    {
        Cache::flush();

        Customer::factory()->create([
            'name' => 'Ahmed Ali',
            'phone' => '0912345678',
        ]);

        Customer::factory()->create([
            'name' => 'Sara Omar',
            'phone' => '0922222222',
        ]);

        $customers = app(ListCustomersAction::class)->execute(
            CustomerFiltersData::fromArray([
                'search' => 'Ahmed',
                'phone' => '091',
            ])
        );

        $this->assertSame(1, $customers->total());
        $this->assertSame('Ahmed Ali', $customers->items()[0]->name);
    }
}
