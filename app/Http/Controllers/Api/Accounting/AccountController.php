<?php

namespace App\Http\Controllers\Api\Accounting;

use App\Http\Controllers\Controller;
use App\Domains\Accounting\Models\Account;
use App\Http\Resources\AccountResource;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class AccountController extends Controller
{
    /**
     * Display a listing of accounts.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $tenant = app('tenant');
        
        $accounts = Account::query()
            ->whereHas('organization', fn($q) => $q->where('tenant_id', $tenant->id))
            ->when($request->organization_id, fn($q, $orgId) => $q->where('organization_id', $orgId))
            ->when($request->account_type, fn($q, $type) => $q->where('account_type', $type))
            ->when($request->is_active !== null, fn($q) => 
                $q->where('is_active', $request->boolean('is_active'))
            )
            ->when($request->search, fn($q, $search) => 
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%")
            )
            ->paginate($request->per_page ?? 15);

        return AccountResource::collection($accounts);
    }

    /**
     * Store a newly created account.
     */
    public function store(Request $request): AccountResource
    {
        $validated = $request->validate([
            'organization_id' => 'required|exists:organizations,id',
            'parent_id' => 'nullable|exists:accounts,id',
            'code' => 'required|string|max:50|unique:accounts,code',
            'name' => 'required|string|max:255',
            'account_type' => 'required|string|in:asset,liability,equity,revenue,expense',
            'currency_code' => 'nullable|string|max:3',
            'description' => 'nullable|string',
            'is_active' => 'nullable|boolean',
        ]);

        $account = Account::create($validated);

        return new AccountResource($account);
    }

    /**
     * Display the specified account.
     */
    public function show(Account $account): AccountResource
    {
        $tenant = app('tenant');
        
        if ($account->organization->tenant_id !== $tenant->id) {
            abort(403, 'Unauthorized access to account');
        }

        return new AccountResource($account);
    }

    /**
     * Update the specified account.
     */
    public function update(Request $request, Account $account): AccountResource
    {
        $tenant = app('tenant');
        
        if ($account->organization->tenant_id !== $tenant->id) {
            abort(403, 'Unauthorized access to account');
        }

        $validated = $request->validate([
            'organization_id' => 'sometimes|exists:organizations,id',
            'parent_id' => 'nullable|exists:accounts,id',
            'code' => 'sometimes|string|max:50|unique:accounts,code,' . $account->id,
            'name' => 'sometimes|string|max:255',
            'account_type' => 'sometimes|string|in:asset,liability,equity,revenue,expense',
            'currency_code' => 'nullable|string|max:3',
            'description' => 'nullable|string',
            'is_active' => 'nullable|boolean',
        ]);

        $account->update($validated);

        return new AccountResource($account);
    }

    /**
     * Remove the specified account.
     */
    public function destroy(Account $account): JsonResponse
    {
        $tenant = app('tenant');
        
        if ($account->organization->tenant_id !== $tenant->id) {
            abort(403, 'Unauthorized access to account');
        }

        $account->delete();

        return response()->json(['message' => 'Account deleted successfully'], 204);
    }
}
