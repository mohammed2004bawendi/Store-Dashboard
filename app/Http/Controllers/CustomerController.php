<?php

namespace App\Http\Controllers;

use App\Http\Resources\CustomerResource;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use App\Traits\ApiResponseTrait;
use Pest\ArchPresets\Custom;
use App\Http\Requests\StoreCustomerRequest;
use App\Http\Requests\UpdateCustomerRequest;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class CustomerController extends Controller
{
    use AuthorizesRequests, ApiResponseTrait;

    // List customers with filters
    public function index(Request $request)
    {

        
        Gate::authorize('view-customers');

        $key = 'customers.page.' . $request->get('page', 1) . '.' . md5(json_encode($request->all()));

        $customers = Cache::remember($key, 60, function () use ($request) {
            $query = $this->applyFilters(Customer::query(), $request);
            return $query->paginate();
        });


        return CustomerResource::collection($customers);
    }

    // Filter by name or phone
    private function applyFilters($query, Request $request)
    {
        return $query
            ->when($request->search, fn($q, $val) => $q->where('name', 'like', "%$val%"))
            ->when($request->phone, fn($q, $val) => $q->where('phone', 'like', "%$val%"));
    }

    // Create customer
    public function store(StoreCustomerRequest $request)
    {
        $this->authorize('create', Customer::class);

        $customer = Customer::create($request->validated());

        return new CustomerResource($customer);
    }

    // Show customer
    public function show(Customer $customer)
    {
        $this->authorize('view', $customer);

        return new CustomerResource($customer);
    }

    // Update customer
    public function update(UpdateCustomerRequest $request, Customer $customer)
    {
        $this->authorize('update', $customer);

        $customer->update($request->validated());

        return new CustomerResource($customer);
    }

    // Delete customer
    public function destroy(Customer $customer)
    {
        $this->authorize('delete', $customer);

        $customer->delete();

        return $this->success([], 'تم حذف العميل');
    }
}
