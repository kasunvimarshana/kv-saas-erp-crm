<?php

namespace App\Http\Controllers\Api\Tenant;

use App\Http\Controllers\Controller;
use App\Domains\Tenant\Models\Organization;
use App\Http\Resources\OrganizationResource;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class OrganizationController extends Controller
{
    /**
     * Display a listing of organizations.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $tenant = app('tenant');
        
        $organizations = Organization::query()
            ->where('tenant_id', $tenant->id)
            ->when($request->status, fn($q, $status) => $q->where('status', $status))
            ->when($request->search, fn($q, $search) => 
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%")
            )
            ->paginate($request->per_page ?? 15);

        return OrganizationResource::collection($organizations);
    }

    /**
     * Store a newly created organization.
     */
    public function store(Request $request): OrganizationResource
    {
        $tenant = app('tenant');
        
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:organizations,code',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:50',
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'country' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:20',
            'tax_id' => 'nullable|string|max:50',
            'currency_code' => 'nullable|string|max:3',
            'timezone' => 'nullable|string|max:50',
            'locale' => 'nullable|string|max:10',
            'settings' => 'nullable|array',
            'status' => 'nullable|string|in:active,inactive',
        ]);

        $validated['tenant_id'] = $tenant->id;
        $organization = Organization::create($validated);

        return new OrganizationResource($organization);
    }

    /**
     * Display the specified organization.
     */
    public function show(Organization $organization): OrganizationResource
    {
        $tenant = app('tenant');
        
        if ($organization->tenant_id !== $tenant->id) {
            abort(403, 'Unauthorized access to organization');
        }

        return new OrganizationResource($organization);
    }

    /**
     * Update the specified organization.
     */
    public function update(Request $request, Organization $organization): OrganizationResource
    {
        $tenant = app('tenant');
        
        if ($organization->tenant_id !== $tenant->id) {
            abort(403, 'Unauthorized access to organization');
        }

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'code' => 'sometimes|string|max:50|unique:organizations,code,' . $organization->id,
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:50',
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'country' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:20',
            'tax_id' => 'nullable|string|max:50',
            'currency_code' => 'nullable|string|max:3',
            'timezone' => 'nullable|string|max:50',
            'locale' => 'nullable|string|max:10',
            'settings' => 'nullable|array',
            'status' => 'sometimes|string|in:active,inactive',
        ]);

        $organization->update($validated);

        return new OrganizationResource($organization);
    }

    /**
     * Remove the specified organization.
     */
    public function destroy(Organization $organization): JsonResponse
    {
        $tenant = app('tenant');
        
        if ($organization->tenant_id !== $tenant->id) {
            abort(403, 'Unauthorized access to organization');
        }

        $organization->delete();

        return response()->json(['message' => 'Organization deleted successfully'], 204);
    }
}
