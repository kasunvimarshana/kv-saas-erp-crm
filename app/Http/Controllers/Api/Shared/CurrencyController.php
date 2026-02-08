<?php

namespace App\Http\Controllers\Api\Shared;

use App\Http\Controllers\Controller;
use App\Domains\Shared\Models\Currency;
use App\Http\Resources\CurrencyResource;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class CurrencyController extends Controller
{
    /**
     * Display a listing of currencies.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $currencies = Currency::query()
            ->when($request->search, fn($q, $search) => 
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%")
            )
            ->when($request->is_active !== null, fn($q) => 
                $q->where('is_active', $request->boolean('is_active'))
            )
            ->paginate($request->per_page ?? 15);

        return CurrencyResource::collection($currencies);
    }

    /**
     * Store a newly created currency.
     */
    public function store(Request $request): CurrencyResource
    {
        $validated = $request->validate([
            'code' => 'required|string|max:3|unique:currencies,code',
            'name' => 'required|string|max:100',
            'symbol' => 'required|string|max:10',
            'decimal_places' => 'nullable|integer|min:0|max:10',
            'exchange_rate' => 'nullable|numeric|min:0',
            'is_active' => 'nullable|boolean',
        ]);

        $currency = Currency::create($validated);

        return new CurrencyResource($currency);
    }

    /**
     * Display the specified currency.
     */
    public function show(Currency $currency): CurrencyResource
    {
        return new CurrencyResource($currency);
    }

    /**
     * Update the specified currency.
     */
    public function update(Request $request, Currency $currency): CurrencyResource
    {
        $validated = $request->validate([
            'code' => 'sometimes|string|max:3|unique:currencies,code,' . $currency->id,
            'name' => 'sometimes|string|max:100',
            'symbol' => 'sometimes|string|max:10',
            'decimal_places' => 'nullable|integer|min:0|max:10',
            'exchange_rate' => 'nullable|numeric|min:0',
            'is_active' => 'nullable|boolean',
        ]);

        $currency->update($validated);

        return new CurrencyResource($currency);
    }

    /**
     * Remove the specified currency.
     */
    public function destroy(Currency $currency): JsonResponse
    {
        $currency->delete();

        return response()->json(['message' => 'Currency deleted successfully'], 204);
    }
}
