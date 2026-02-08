<?php

namespace App\Http\Controllers\Api\Tenant;

use App\Http\Controllers\Controller;
use App\Domains\Tenant\Models\Branch;
use App\Http\Resources\BranchResource;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class BranchController extends Controller
{
    /**
     * Display a listing of branches.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $tenant = app('tenant');
        
        $branches = Branch::query()
            ->whereHas('organization', fn($q) => $q->where('tenant_id', $tenant->id))
            ->when($request->organization_id, fn($q, $orgId) => $q->where('organization_id', $orgId))
            ->when($request->search, fn($q, $search) => 
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%")
            )
            ->paginate($request->per_page ?? 15);

        return BranchResource::collection($branches);
    }

    /**
     * Store a newly created branch.
     */
    public function store(Request $request): BranchResource
    {
        $validated = $request->validate([
            'organization_id' => 'required|exists:organizations,id',
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:branches,code',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:50',
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'country' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:20',
            'is_active' => 'nullable|boolean',
        ]);

        $branch = Branch::create($validated);

        return new BranchResource($branch);
    }

    /**
     * Display the specified branch.
     */
    public function show(Branch $branch): BranchResource
    {
        $tenant = app('tenant');
        
        if ($branch->organization->tenant_id !== $tenant->id) {
            abort(403, 'Unauthorized access to branch');
        }

        return new BranchResource($branch);
    }

    /**
     * Update the specified branch.
     */
    public function update(Request $request, Branch $branch): BranchResource
    {
        $tenant = app('tenant');
        
        if ($branch->organization->tenant_id !== $tenant->id) {
            abort(403, 'Unauthorized access to branch');
        }

        $validated = $request->validate([
            'organization_id' => 'sometimes|exists:organizations,id',
            'name' => 'sometimes|string|max:255',
            'code' => 'sometimes|string|max:50|unique:branches,code,' . $branch->id,
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:50',
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'country' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:20',
            'is_active' => 'nullable|boolean',
        ]);

        $branch->update($validated);

        return new BranchResource($branch);
    }

    /**
     * Remove the specified branch.
     */
    public function destroy(Branch $branch): JsonResponse
    {
        $tenant = app('tenant');
        
        if ($branch->organization->tenant_id !== $tenant->id) {
            abort(403, 'Unauthorized access to branch');
        }

        $branch->delete();

        return response()->json(['message' => 'Branch deleted successfully'], 204);
    }
}
