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


class CustomerController extends Controller
{
    use AuthorizesRequests, ApiResponseTrait;

    public function index(Request $request)
    {
        Gate::authorize('view-customers');

        $query = $this->applyFilters(Customer::query(), $request);

        return CustomerResource::collection($query->paginate());
    }

    private function applyFilters($query, Request $request)
    {
        return $query
            ->when($request->search, fn($q, $val) => $q->where('name', 'like', "%$val%"))
            ->when($request->phone, fn($q, $val) => $q->where('phone', 'like', "%$val%"));
    }

    public function store(StoreCustomerRequest $request)
    {
        $this->authorize('create', Customer::class);

        $customer = Customer::create($request->validated());

        return new CustomerResource($customer);
    }

    public function show(Customer $customer)
    {
        $this->authorize('view', $customer);

        return new CustomerResource($customer);
    }

    public function update(UpdateCustomerRequest $request, Customer $customer)
    {
        $this->authorize('update', $customer);

        $customer->update($request->validated());

        return new CustomerResource($customer);
    }

    public function destroy(Customer $customer)
    {
        $this->authorize('delete', $customer);

        $customer->delete();

        return $this->success([], 'تم حذف العميل');
    }
}

