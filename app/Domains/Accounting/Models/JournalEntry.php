<?php

namespace App\Domains\Accounting\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class JournalEntry extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'organization_id',
        'branch_id',
        'entry_number',
        'entry_date',
        'reference',
        'description',
        'currency_code',
        'status',
        'posted_at',
        'posted_by',
    ];

    protected $casts = [
        'entry_date' => 'date',
        'posted_at' => 'datetime',
    ];

    public const STATUS_DRAFT = 'draft';
    public const STATUS_POSTED = 'posted';
    public const STATUS_CANCELLED = 'cancelled';

    /**
     * Get the organization that owns the journal entry.
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(\App\Domains\Tenant\Models\Organization::class);
    }

    /**
     * Get the branch that owns the journal entry.
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(\App\Domains\Tenant\Models\Branch::class);
    }

    /**
     * Get the lines for the journal entry.
     */
    public function lines(): HasMany
    {
        return $this->hasMany(JournalEntryLine::class);
    }

    /**
     * Check if journal entry is balanced.
     */
    public function isBalanced(): bool
    {
        $totalDebits = $this->lines()->sum('debit');
        $totalCredits = $this->lines()->sum('credit');

        return bccomp($totalDebits, $totalCredits, 2) === 0;
    }

    /**
     * Post the journal entry.
     */
    public function post(): bool
    {
        if (!$this->isBalanced()) {
            return false;
        }

        $this->update([
            'status' => self::STATUS_POSTED,
            'posted_at' => now(),
            'posted_by' => auth()->id(),
        ]);

        return true;
    }

    /**
     * Scope a query to only include posted entries.
     */
    public function scopePosted($query)
    {
        return $query->where('status', self::STATUS_POSTED);
    }
}
