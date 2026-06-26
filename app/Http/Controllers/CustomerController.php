<?php

namespace App\Http\Controllers;

use App\Domain\Customers\Actions\CreateCustomerAction;
use App\Domain\Customers\Actions\DeleteCustomerAction;
use App\Domain\Customers\Actions\ListCustomersAction;
use App\Domain\Customers\Actions\UpdateCustomerAction;
use App\Domain\Customers\Data\CreateCustomerData;
use App\Domain\Customers\Data\CustomerFiltersData;
use App\Domain\Customers\Data\UpdateCustomerData;
use App\Http\Requests\StoreCustomerRequest;
use App\Http\Requests\UpdateCustomerRequest;
use App\Http\Resources\CustomerResource;
use App\Models\Customer;
use App\Traits\ApiResponseTrait;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class CustomerController extends Controller
{
    use ApiResponseTrait, AuthorizesRequests;

    public function index(Request $request, ListCustomersAction $listCustomers)
    {
        Gate::authorize('view-customers');

        $customers = $listCustomers->execute(
            CustomerFiltersData::fromArray($request->all())
        );

        return CustomerResource::collection($customers);
    }

    public function store(StoreCustomerRequest $request, CreateCustomerAction $createCustomer)
    {
        $this->authorize('create', Customer::class);

        $customer = $createCustomer->execute(
            CreateCustomerData::fromArray($request->validated())
        );

        return new CustomerResource($customer);
    }

    public function show(Customer $customer)
    {
        $this->authorize('view', $customer);

        return new CustomerResource($customer);
    }

    public function update(UpdateCustomerRequest $request, Customer $customer, UpdateCustomerAction $updateCustomer)
    {
        $this->authorize('update', $customer);

        $customer = $updateCustomer->execute(
            $customer,
            UpdateCustomerData::fromArray($request->validated())
        );

        return new CustomerResource($customer);
    }

    public function destroy(Customer $customer, DeleteCustomerAction $deleteCustomer)
    {
        $this->authorize('delete', $customer);

        $deleteCustomer->execute($customer);

        return $this->success([], 'تم حذف العميل');
    }
}

//test