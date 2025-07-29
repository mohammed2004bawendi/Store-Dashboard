<?php

namespace App\Http\Controllers;
use App\Http\Resources\CustomerResource;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use App\Traits\ApiResponseTrait;
use Pest\ArchPresets\Custom;

class CustomerController extends Controller
{
     use AuthorizesRequests;
    use ApiResponseTrait;

    public function index(Request $request)
    {

        Gate::authorize('view-customers');

        $query = Customer::query();
        $query->when($request->search, fn($q, $search) => $q->where('name', 'like', '%'. $search . '%' ));
        $query->when($request->phone, fn($q, $search) => $q->where('phone', 'like', '%'. $search . '%' ));
        return CustomerResource::collection($query->paginate());
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {

    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {

        $this->authorize('create', Customer::class);

        $customer = Customer::create(
            $request->validate([
                'name' => 'required',
                'phone' => 'required|string',
                'address' => 'required',
            ])
            );

            return new CustomerResource($customer);
    }

    /**
     * Display the specified resource.
     */
    public function show(Customer $customer)
    {
           $this->authorize('view', $customer);
             return new CustomerResource($customer);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Customer $customer)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Customer $customer)
    {




       $this->authorize('update', $customer);

        $customer->update(
            $request->validate([
                'name' => 'required',
                'phone' => 'required|string',
                'address' => 'required',
            ])
            );

        return new CustomerResource($customer);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Customer $customer)
    {
       $this->authorize('delete', $customer);

        $customer->delete();
        return response()->json(['message'=>'Customer deleted successfully!'], 200);
    }
}
