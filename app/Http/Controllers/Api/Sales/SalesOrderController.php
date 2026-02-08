<?php

namespace App\Http\Controllers\Api\Sales;

use App\Http\Controllers\Controller;
use App\Domains\Sales\Models\SalesOrder;
use App\Http\Resources\SalesOrderResource;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class SalesOrderController extends Controller
{
    /**
     * Display a listing of sales orders.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $tenant = app('tenant');
        
        $orders = SalesOrder::query()
            ->whereHas('organization', fn($q) => $q->where('tenant_id', $tenant->id))
            ->when($request->organization_id, fn($q, $orgId) => $q->where('organization_id', $orgId))
            ->when($request->branch_id, fn($q, $branchId) => $q->where('branch_id', $branchId))
            ->when($request->customer_id, fn($q, $customerId) => $q->where('customer_id', $customerId))
            ->when($request->status, fn($q, $status) => $q->where('status', $status))
            ->when($request->from_date, fn($q, $date) => $q->where('order_date', '>=', $date))
            ->when($request->to_date, fn($q, $date) => $q->where('order_date', '<=', $date))
            ->when($request->search, fn($q, $search) => 
                $q->where('order_number', 'like', "%{$search}%")
                  ->orWhere('reference', 'like', "%{$search}%")
            )
            ->with('lines')
            ->orderBy('order_date', 'desc')
            ->paginate($request->per_page ?? 15);

        return SalesOrderResource::collection($orders);
    }

    /**
     * Store a newly created sales order.
     */
    public function store(Request $request): SalesOrderResource
    {
        $validated = $request->validate([
            'organization_id' => 'required|exists:organizations,id',
            'branch_id' => 'nullable|exists:branches,id',
            'customer_id' => 'required|exists:customers,id',
            'order_number' => 'required|string|max:50|unique:sales_orders,order_number',
            'order_date' => 'required|date',
            'delivery_date' => 'nullable|date',
            'reference' => 'nullable|string|max:255',
            'currency_code' => 'nullable|string|max:3',
            'subtotal' => 'nullable|numeric|min:0',
            'tax_amount' => 'nullable|numeric|min:0',
            'discount_amount' => 'nullable|numeric|min:0',
            'total_amount' => 'nullable|numeric|min:0',
            'status' => 'nullable|string|in:draft,confirmed,processing,completed,cancelled',
            'notes' => 'nullable|string',
            'lines' => 'sometimes|array',
            'lines.*.product_id' => 'required|exists:products,id',
            'lines.*.quantity' => 'required|numeric|min:0',
            'lines.*.unit_price' => 'required|numeric|min:0',
            'lines.*.discount_amount' => 'nullable|numeric|min:0',
            'lines.*.tax_amount' => 'nullable|numeric|min:0',
            'lines.*.line_total' => 'required|numeric|min:0',
            'lines.*.description' => 'nullable|string',
        ]);

        $lines = $validated['lines'] ?? [];
        unset($validated['lines']);

        $order = SalesOrder::create($validated);

        foreach ($lines as $line) {
            $order->lines()->create($line);
        }

        return new SalesOrderResource($order->load('lines'));
    }

    /**
     * Display the specified sales order.
     */
    public function show(SalesOrder $salesOrder): SalesOrderResource
    {
        $tenant = app('tenant');
        
        if ($salesOrder->organization->tenant_id !== $tenant->id) {
            abort(403, 'Unauthorized access to sales order');
        }

        return new SalesOrderResource($salesOrder->load('lines'));
    }

    /**
     * Update the specified sales order.
     */
    public function update(Request $request, SalesOrder $salesOrder): SalesOrderResource
    {
        $tenant = app('tenant');
        
        if ($salesOrder->organization->tenant_id !== $tenant->id) {
            abort(403, 'Unauthorized access to sales order');
        }

        $validated = $request->validate([
            'organization_id' => 'sometimes|exists:organizations,id',
            'branch_id' => 'nullable|exists:branches,id',
            'customer_id' => 'sometimes|exists:customers,id',
            'order_number' => 'sometimes|string|max:50|unique:sales_orders,order_number,' . $salesOrder->id,
            'order_date' => 'sometimes|date',
            'delivery_date' => 'nullable|date',
            'reference' => 'nullable|string|max:255',
            'currency_code' => 'nullable|string|max:3',
            'subtotal' => 'nullable|numeric|min:0',
            'tax_amount' => 'nullable|numeric|min:0',
            'discount_amount' => 'nullable|numeric|min:0',
            'total_amount' => 'nullable|numeric|min:0',
            'status' => 'sometimes|string|in:draft,confirmed,processing,completed,cancelled',
            'notes' => 'nullable|string',
            'lines' => 'sometimes|array',
            'lines.*.product_id' => 'required|exists:products,id',
            'lines.*.quantity' => 'required|numeric|min:0',
            'lines.*.unit_price' => 'required|numeric|min:0',
            'lines.*.discount_amount' => 'nullable|numeric|min:0',
            'lines.*.tax_amount' => 'nullable|numeric|min:0',
            'lines.*.line_total' => 'required|numeric|min:0',
            'lines.*.description' => 'nullable|string',
        ]);

        if (isset($validated['lines'])) {
            $lines = $validated['lines'];
            unset($validated['lines']);
            
            $salesOrder->lines()->delete();
            foreach ($lines as $line) {
                $salesOrder->lines()->create($line);
            }
        }

        $salesOrder->update($validated);

        return new SalesOrderResource($salesOrder->load('lines'));
    }

    /**
     * Remove the specified sales order.
     */
    public function destroy(SalesOrder $salesOrder): JsonResponse
    {
        $tenant = app('tenant');
        
        if ($salesOrder->organization->tenant_id !== $tenant->id) {
            abort(403, 'Unauthorized access to sales order');
        }

        $salesOrder->delete();

        return response()->json(['message' => 'Sales order deleted successfully'], 204);
    }
}
