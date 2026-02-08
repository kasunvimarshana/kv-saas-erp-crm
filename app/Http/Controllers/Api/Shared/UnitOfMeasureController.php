<?php

namespace App\Http\Controllers\Api\Shared;

use App\Http\Controllers\Controller;
use App\Domains\Shared\Models\UnitOfMeasure;
use App\Http\Resources\UnitOfMeasureResource;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class UnitOfMeasureController extends Controller
{
    /**
     * Display a listing of units of measure.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $units = UnitOfMeasure::query()
            ->when($request->search, fn($q, $search) => 
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%")
            )
            ->when($request->is_active !== null, fn($q) => 
                $q->where('is_active', $request->boolean('is_active'))
            )
            ->paginate($request->per_page ?? 15);

        return UnitOfMeasureResource::collection($units);
    }

    /**
     * Store a newly created unit of measure.
     */
    public function store(Request $request): UnitOfMeasureResource
    {
        $validated = $request->validate([
            'code' => 'required|string|max:20|unique:unit_of_measures,code',
            'name' => 'required|string|max:100',
            'description' => 'nullable|string',
            'is_active' => 'nullable|boolean',
        ]);

        $unit = UnitOfMeasure::create($validated);

        return new UnitOfMeasureResource($unit);
    }

    /**
     * Display the specified unit of measure.
     */
    public function show(UnitOfMeasure $unitOfMeasure): UnitOfMeasureResource
    {
        return new UnitOfMeasureResource($unitOfMeasure);
    }

    /**
     * Update the specified unit of measure.
     */
    public function update(Request $request, UnitOfMeasure $unitOfMeasure): UnitOfMeasureResource
    {
        $validated = $request->validate([
            'code' => 'sometimes|string|max:20|unique:unit_of_measures,code,' . $unitOfMeasure->id,
            'name' => 'sometimes|string|max:100',
            'description' => 'nullable|string',
            'is_active' => 'nullable|boolean',
        ]);

        $unitOfMeasure->update($validated);

        return new UnitOfMeasureResource($unitOfMeasure);
    }

    /**
     * Remove the specified unit of measure.
     */
    public function destroy(UnitOfMeasure $unitOfMeasure): JsonResponse
    {
        $unitOfMeasure->delete();

        return response()->json(['message' => 'Unit of measure deleted successfully'], 204);
    }
}
