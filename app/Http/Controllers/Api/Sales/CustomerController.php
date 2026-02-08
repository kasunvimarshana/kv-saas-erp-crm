<?php

namespace App\Http\Controllers\Api\Sales;

use App\Http\Controllers\Controller;
use App\Domains\Sales\Models\Customer;
use App\Http\Resources\CustomerResource;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class CustomerController extends Controller
{
    /**
     * Display a listing of customers.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $tenant = app('tenant');
        
        $customers = Customer::query()
            ->whereHas('organization', fn($q) => $q->where('tenant_id', $tenant->id))
            ->when($request->organization_id, fn($q, $orgId) => $q->where('organization_id', $orgId))
            ->when($request->status, fn($q, $status) => $q->where('status', $status))
            ->when($request->search, fn($q, $search) => 
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
            )
            ->paginate($request->per_page ?? 15);

        return CustomerResource::collection($customers);
    }

    /**
     * Store a newly created customer.
     */
    public function store(Request $request): CustomerResource
    {
        $validated = $request->validate([
            'organization_id' => 'required|exists:organizations,id',
            'code' => 'required|string|max:50|unique:customers,code',
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:50',
            'mobile' => 'nullable|string|max:50',
            'website' => 'nullable|string|max:255',
            'tax_id' => 'nullable|string|max:50',
            'billing_address' => 'nullable|string',
            'billing_city' => 'nullable|string|max:100',
            'billing_state' => 'nullable|string|max:100',
            'billing_country' => 'nullable|string|max:100',
            'billing_postal_code' => 'nullable|string|max:20',
            'shipping_address' => 'nullable|string',
            'shipping_city' => 'nullable|string|max:100',
            'shipping_state' => 'nullable|string|max:100',
            'shipping_country' => 'nullable|string|max:100',
            'shipping_postal_code' => 'nullable|string|max:20',
            'payment_terms' => 'nullable|integer|min:0',
            'credit_limit' => 'nullable|numeric|min:0',
            'currency_code' => 'nullable|string|max:3',
            'status' => 'nullable|string|in:active,inactive',
        ]);

        $customer = Customer::create($validated);

        return new CustomerResource($customer);
    }

    /**
     * Display the specified customer.
     */
    public function show(Customer $customer): CustomerResource
    {
        $tenant = app('tenant');
        
        if ($customer->organization->tenant_id !== $tenant->id) {
            abort(403, 'Unauthorized access to customer');
        }

        return new CustomerResource($customer);
    }

    /**
     * Update the specified customer.
     */
    public function update(Request $request, Customer $customer): CustomerResource
    {
        $tenant = app('tenant');
        
        if ($customer->organization->tenant_id !== $tenant->id) {
            abort(403, 'Unauthorized access to customer');
        }

        $validated = $request->validate([
            'organization_id' => 'sometimes|exists:organizations,id',
            'code' => 'sometimes|string|max:50|unique:customers,code,' . $customer->id,
            'name' => 'sometimes|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:50',
            'mobile' => 'nullable|string|max:50',
            'website' => 'nullable|string|max:255',
            'tax_id' => 'nullable|string|max:50',
            'billing_address' => 'nullable|string',
            'billing_city' => 'nullable|string|max:100',
            'billing_state' => 'nullable|string|max:100',
            'billing_country' => 'nullable|string|max:100',
            'billing_postal_code' => 'nullable|string|max:20',
            'shipping_address' => 'nullable|string',
            'shipping_city' => 'nullable|string|max:100',
            'shipping_state' => 'nullable|string|max:100',
            'shipping_country' => 'nullable|string|max:100',
            'shipping_postal_code' => 'nullable|string|max:20',
            'payment_terms' => 'nullable|integer|min:0',
            'credit_limit' => 'nullable|numeric|min:0',
            'currency_code' => 'nullable|string|max:3',
            'status' => 'sometimes|string|in:active,inactive',
        ]);

        $customer->update($validated);

        return new CustomerResource($customer);
    }

    /**
     * Remove the specified customer.
     */
    public function destroy(Customer $customer): JsonResponse
    {
        $tenant = app('tenant');
        
        if ($customer->organization->tenant_id !== $tenant->id) {
            abort(403, 'Unauthorized access to customer');
        }

        $customer->delete();

        return response()->json(['message' => 'Customer deleted successfully'], 204);
    }
}
