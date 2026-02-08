<?php

namespace App\Http\Controllers\Api\Inventory;

use App\Http\Controllers\Controller;
use App\Domains\Inventory\Models\StockMovement;
use App\Http\Resources\StockMovementResource;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class StockMovementController extends Controller
{
    /**
     * Display a listing of stock movements.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $tenant = app('tenant');
        
        $movements = StockMovement::query()
            ->whereHas('organization', fn($q) => $q->where('tenant_id', $tenant->id))
            ->when($request->organization_id, fn($q, $orgId) => $q->where('organization_id', $orgId))
            ->when($request->product_id, fn($q, $productId) => $q->where('product_id', $productId))
            ->when($request->location_id, fn($q, $locationId) => $q->where('location_id', $locationId))
            ->when($request->movement_type, fn($q, $type) => $q->where('movement_type', $type))
            ->when($request->from_date, fn($q, $date) => $q->where('movement_date', '>=', $date))
            ->when($request->to_date, fn($q, $date) => $q->where('movement_date', '<=', $date))
            ->orderBy('movement_date', 'desc')
            ->paginate($request->per_page ?? 15);

        return StockMovementResource::collection($movements);
    }

    /**
     * Store a newly created stock movement.
     */
    public function store(Request $request): StockMovementResource
    {
        $validated = $request->validate([
            'organization_id' => 'required|exists:organizations,id',
            'product_id' => 'required|exists:products,id',
            'location_id' => 'nullable|exists:branches,id',
            'movement_type' => 'required|string|in:in,out,adjustment,transfer',
            'reference_type' => 'nullable|string|max:100',
            'reference_id' => 'nullable|integer',
            'quantity' => 'required|numeric',
            'unit_cost' => 'nullable|numeric|min:0',
            'movement_date' => 'required|date',
            'notes' => 'nullable|string',
        ]);

        $movement = StockMovement::create($validated);

        return new StockMovementResource($movement);
    }

    /**
     * Display the specified stock movement.
     */
    public function show(StockMovement $stockMovement): StockMovementResource
    {
        $tenant = app('tenant');
        
        if ($stockMovement->organization->tenant_id !== $tenant->id) {
            abort(403, 'Unauthorized access to stock movement');
        }

        return new StockMovementResource($stockMovement);
    }

    /**
     * Update the specified stock movement.
     */
    public function update(Request $request, StockMovement $stockMovement): StockMovementResource
    {
        $tenant = app('tenant');
        
        if ($stockMovement->organization->tenant_id !== $tenant->id) {
            abort(403, 'Unauthorized access to stock movement');
        }

        $validated = $request->validate([
            'organization_id' => 'sometimes|exists:organizations,id',
            'product_id' => 'sometimes|exists:products,id',
            'location_id' => 'nullable|exists:branches,id',
            'movement_type' => 'sometimes|string|in:in,out,adjustment,transfer',
            'reference_type' => 'nullable|string|max:100',
            'reference_id' => 'nullable|integer',
            'quantity' => 'sometimes|numeric',
            'unit_cost' => 'nullable|numeric|min:0',
            'movement_date' => 'sometimes|date',
            'notes' => 'nullable|string',
        ]);

        $stockMovement->update($validated);

        return new StockMovementResource($stockMovement);
    }

    /**
     * Remove the specified stock movement.
     */
    public function destroy(StockMovement $stockMovement): JsonResponse
    {
        $tenant = app('tenant');
        
        if ($stockMovement->organization->tenant_id !== $tenant->id) {
            abort(403, 'Unauthorized access to stock movement');
        }

        $stockMovement->delete();

        return response()->json(['message' => 'Stock movement deleted successfully'], 204);
    }
}
