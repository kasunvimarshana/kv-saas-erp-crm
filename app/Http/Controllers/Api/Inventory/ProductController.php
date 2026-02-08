<?php

namespace App\Http\Controllers\Api\Inventory;

use App\Http\Controllers\Controller;
use App\Domains\Inventory\Models\Product;
use App\Http\Resources\ProductResource;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ProductController extends Controller
{
    /**
     * Display a listing of products.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $tenant = app('tenant');
        
        $products = Product::query()
            ->whereHas('organization', fn($q) => $q->where('tenant_id', $tenant->id))
            ->when($request->organization_id, fn($q, $orgId) => $q->where('organization_id', $orgId))
            ->when($request->product_type, fn($q, $type) => $q->where('product_type', $type))
            ->when($request->status, fn($q, $status) => $q->where('status', $status))
            ->when($request->search, fn($q, $search) => 
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%")
                  ->orWhere('sku', 'like', "%{$search}%")
            )
            ->paginate($request->per_page ?? 15);

        return ProductResource::collection($products);
    }

    /**
     * Store a newly created product.
     */
    public function store(Request $request): ProductResource
    {
        $validated = $request->validate([
            'organization_id' => 'required|exists:organizations,id',
            'code' => 'required|string|max:50|unique:products,code',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'product_type' => 'required|string|in:goods,service,consumable',
            'category_id' => 'nullable|integer',
            'unit_of_measure_id' => 'nullable|exists:unit_of_measures,id',
            'cost_price' => 'nullable|numeric|min:0',
            'selling_price' => 'nullable|numeric|min:0',
            'barcode' => 'nullable|string|max:100',
            'sku' => 'nullable|string|max:100',
            'track_inventory' => 'nullable|boolean',
            'reorder_level' => 'nullable|numeric|min:0',
            'status' => 'nullable|string|in:active,inactive',
        ]);

        $product = Product::create($validated);

        return new ProductResource($product);
    }

    /**
     * Display the specified product.
     */
    public function show(Product $product): ProductResource
    {
        $tenant = app('tenant');
        
        if ($product->organization->tenant_id !== $tenant->id) {
            abort(403, 'Unauthorized access to product');
        }

        return new ProductResource($product);
    }

    /**
     * Update the specified product.
     */
    public function update(Request $request, Product $product): ProductResource
    {
        $tenant = app('tenant');
        
        if ($product->organization->tenant_id !== $tenant->id) {
            abort(403, 'Unauthorized access to product');
        }

        $validated = $request->validate([
            'organization_id' => 'sometimes|exists:organizations,id',
            'code' => 'sometimes|string|max:50|unique:products,code,' . $product->id,
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'product_type' => 'sometimes|string|in:goods,service,consumable',
            'category_id' => 'nullable|integer',
            'unit_of_measure_id' => 'nullable|exists:unit_of_measures,id',
            'cost_price' => 'nullable|numeric|min:0',
            'selling_price' => 'nullable|numeric|min:0',
            'barcode' => 'nullable|string|max:100',
            'sku' => 'nullable|string|max:100',
            'track_inventory' => 'nullable|boolean',
            'reorder_level' => 'nullable|numeric|min:0',
            'status' => 'sometimes|string|in:active,inactive',
        ]);

        $product->update($validated);

        return new ProductResource($product);
    }

    /**
     * Remove the specified product.
     */
    public function destroy(Product $product): JsonResponse
    {
        $tenant = app('tenant');
        
        if ($product->organization->tenant_id !== $tenant->id) {
            abort(403, 'Unauthorized access to product');
        }

        $product->delete();

        return response()->json(['message' => 'Product deleted successfully'], 204);
    }
}
