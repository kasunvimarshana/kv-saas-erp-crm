<?php

namespace App\Http\Controllers\Api\Accounting;

use App\Http\Controllers\Controller;
use App\Domains\Accounting\Models\JournalEntry;
use App\Http\Resources\JournalEntryResource;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class JournalEntryController extends Controller
{
    /**
     * Display a listing of journal entries.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $tenant = app('tenant');
        
        $entries = JournalEntry::query()
            ->whereHas('organization', fn($q) => $q->where('tenant_id', $tenant->id))
            ->when($request->organization_id, fn($q, $orgId) => $q->where('organization_id', $orgId))
            ->when($request->branch_id, fn($q, $branchId) => $q->where('branch_id', $branchId))
            ->when($request->status, fn($q, $status) => $q->where('status', $status))
            ->when($request->from_date, fn($q, $date) => $q->where('entry_date', '>=', $date))
            ->when($request->to_date, fn($q, $date) => $q->where('entry_date', '<=', $date))
            ->when($request->search, fn($q, $search) => 
                $q->where('entry_number', 'like', "%{$search}%")
                  ->orWhere('reference', 'like', "%{$search}%")
            )
            ->with('lines')
            ->orderBy('entry_date', 'desc')
            ->paginate($request->per_page ?? 15);

        return JournalEntryResource::collection($entries);
    }

    /**
     * Store a newly created journal entry.
     */
    public function store(Request $request): JournalEntryResource
    {
        $validated = $request->validate([
            'organization_id' => 'required|exists:organizations,id',
            'branch_id' => 'nullable|exists:branches,id',
            'entry_number' => 'required|string|max:50|unique:journal_entries,entry_number',
            'entry_date' => 'required|date',
            'reference' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'currency_code' => 'nullable|string|max:3',
            'status' => 'nullable|string|in:draft,posted,cancelled',
            'lines' => 'required|array|min:2',
            'lines.*.account_id' => 'required|exists:accounts,id',
            'lines.*.debit' => 'required|numeric|min:0',
            'lines.*.credit' => 'required|numeric|min:0',
            'lines.*.currency_code' => 'nullable|string|max:3',
            'lines.*.description' => 'nullable|string',
        ]);

        $lines = $validated['lines'];
        unset($validated['lines']);

        $entry = JournalEntry::create($validated);

        foreach ($lines as $line) {
            $entry->lines()->create($line);
        }

        return new JournalEntryResource($entry->load('lines'));
    }

    /**
     * Display the specified journal entry.
     */
    public function show(JournalEntry $journalEntry): JournalEntryResource
    {
        $tenant = app('tenant');
        
        if ($journalEntry->organization->tenant_id !== $tenant->id) {
            abort(403, 'Unauthorized access to journal entry');
        }

        return new JournalEntryResource($journalEntry->load('lines'));
    }

    /**
     * Update the specified journal entry.
     */
    public function update(Request $request, JournalEntry $journalEntry): JournalEntryResource
    {
        $tenant = app('tenant');
        
        if ($journalEntry->organization->tenant_id !== $tenant->id) {
            abort(403, 'Unauthorized access to journal entry');
        }

        if ($journalEntry->status === JournalEntry::STATUS_POSTED) {
            return response()->json(['message' => 'Cannot update posted journal entry'], 422);
        }

        $validated = $request->validate([
            'organization_id' => 'sometimes|exists:organizations,id',
            'branch_id' => 'nullable|exists:branches,id',
            'entry_number' => 'sometimes|string|max:50|unique:journal_entries,entry_number,' . $journalEntry->id,
            'entry_date' => 'sometimes|date',
            'reference' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'currency_code' => 'nullable|string|max:3',
            'status' => 'sometimes|string|in:draft,posted,cancelled',
            'lines' => 'sometimes|array|min:2',
            'lines.*.account_id' => 'required|exists:accounts,id',
            'lines.*.debit' => 'required|numeric|min:0',
            'lines.*.credit' => 'required|numeric|min:0',
            'lines.*.currency_code' => 'nullable|string|max:3',
            'lines.*.description' => 'nullable|string',
        ]);

        if (isset($validated['lines'])) {
            $lines = $validated['lines'];
            unset($validated['lines']);
            
            $journalEntry->lines()->delete();
            foreach ($lines as $line) {
                $journalEntry->lines()->create($line);
            }
        }

        $journalEntry->update($validated);

        return new JournalEntryResource($journalEntry->load('lines'));
    }

    /**
     * Remove the specified journal entry.
     */
    public function destroy(JournalEntry $journalEntry): JsonResponse
    {
        $tenant = app('tenant');
        
        if ($journalEntry->organization->tenant_id !== $tenant->id) {
            abort(403, 'Unauthorized access to journal entry');
        }

        if ($journalEntry->status === JournalEntry::STATUS_POSTED) {
            return response()->json(['message' => 'Cannot delete posted journal entry'], 422);
        }

        $journalEntry->delete();

        return response()->json(['message' => 'Journal entry deleted successfully'], 204);
    }
}
