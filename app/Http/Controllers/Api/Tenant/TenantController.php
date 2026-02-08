<?php

namespace App\Http\Controllers\Api\Tenant;

use App\Http\Controllers\Controller;
use App\Domains\Tenant\Models\Tenant;
use App\Http\Resources\TenantResource;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class TenantController extends Controller
{
    /**
     * Display a listing of tenants.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $tenants = Tenant::query()
            ->when($request->status, fn($q, $status) => $q->where('status', $status))
            ->when($request->search, fn($q, $search) => 
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('subdomain', 'like', "%{$search}%")
            )
            ->paginate($request->per_page ?? 15);

        return TenantResource::collection($tenants);
    }

    /**
     * Store a newly created tenant.
     */
    public function store(Request $request): TenantResource|JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'subdomain' => 'required|string|max:255|unique:tenants,subdomain',
            'domain' => 'nullable|string|max:255|unique:tenants,domain',
            'database_name' => 'nullable|string|max:255',
            'database_host' => 'nullable|string|max:255',
            'database_port' => 'nullable|integer',
            'database_username' => 'nullable|string|max:255',
            'status' => 'nullable|string|in:active,inactive,suspended',
            'settings' => 'nullable|array',
            'expires_at' => 'nullable|date',
        ]);

        $tenant = Tenant::create($validated);

        return new TenantResource($tenant);
    }

    /**
     * Display the specified tenant.
     */
    public function show(Tenant $tenant): TenantResource
    {
        return new TenantResource($tenant);
    }

    /**
     * Update the specified tenant.
     */
    public function update(Request $request, Tenant $tenant): TenantResource
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'subdomain' => 'sometimes|string|max:255|unique:tenants,subdomain,' . $tenant->id,
            'domain' => 'nullable|string|max:255|unique:tenants,domain,' . $tenant->id,
            'database_name' => 'nullable|string|max:255',
            'database_host' => 'nullable|string|max:255',
            'database_port' => 'nullable|integer',
            'database_username' => 'nullable|string|max:255',
            'status' => 'sometimes|string|in:active,inactive,suspended',
            'settings' => 'nullable|array',
            'expires_at' => 'nullable|date',
        ]);

        $tenant->update($validated);

        return new TenantResource($tenant);
    }

    /**
     * Remove the specified tenant.
     */
    public function destroy(Tenant $tenant): JsonResponse
    {
        $tenant->delete();

        return response()->json(['message' => 'Tenant deleted successfully'], 204);
    }
}
