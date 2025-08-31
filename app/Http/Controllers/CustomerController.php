<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCustomerRequest;
use App\Http\Requests\UpdateCustomerRequest;
use App\Http\Resources\CustomerResource;
use App\Models\Customer;
use App\Traits\ApiResponseTrait;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Gate;

class CustomerController extends Controller
{
    use ApiResponseTrait, AuthorizesRequests;

    private function customersCacheKey(Request $request): string
    {
        // Create/keep the cache version with initial value = 1
        $version = Cache::rememberForever('customers_cache_version', fn () => 1);

        // Sort query parameters and exclude 'page' from hash
        $params = $request->except('page');
        ksort($params);
        $hash = md5(json_encode($params));
        $page = (int) $request->get('page', 1);

        return "customers.v{$version}.page.{$page}.{$hash}";
    }

    /**
     * Increment customers cache version (old keys become obsolete automatically).
     */
    private function bumpCustomersCacheVersion(): void
    {
        // If version key does not exist, create it and then increment
        Cache::add('customers_cache_version', 1);
        Cache::increment('customers_cache_version');
    }

    /**
     * List customers with filters.
     */
    public function index(Request $request)
    {
        Gate::authorize('view-customers');

        $key = $this->customersCacheKey($request);

        $customers = Cache::remember($key, 60, function () use ($request) {
            $query = $this->applyFilters(Customer::query(), $request);

            return $query->paginate();
        });

        return CustomerResource::collection($customers);
    }

    /**
     * Filter by name or phone.
     */
    private function applyFilters($query, Request $request)
    {
        return $query
            ->when($request->search, fn ($q, $val) => $q->where('name', 'like', "%{$val}%"))
            ->when($request->phone, fn ($q, $val) => $q->where('phone', 'like', "%{$val}%"));
    }

    /**
     * Create customer.
     */
    public function store(StoreCustomerRequest $request)
    {
        $this->authorize('create', Customer::class);

        $customer = Customer::create($request->validated());

        // Any change in customers should bump cache version
        $this->bumpCustomersCacheVersion();

        return new CustomerResource($customer);
    }

    /**
     * Show customer.
     */
    public function show(Customer $customer)
    {
        $this->authorize('view', $customer);

        return new CustomerResource($customer);
    }

    /**
     * Update customer.
     */
    public function update(UpdateCustomerRequest $request, Customer $customer)
    {
        $this->authorize('update', $customer);

        $customer->update($request->validated());

        // Bump cache version after update
        $this->bumpCustomersCacheVersion();

        return new CustomerResource($customer);
    }

    /**
     * Delete customer.
     */
    public function destroy(Customer $customer)
    {
        $this->authorize('delete', $customer);

        $customer->delete();

        // Bump cache version after delete
        $this->bumpCustomersCacheVersion();

        return $this->success([], 'تم حذف العميل');
    }
}
