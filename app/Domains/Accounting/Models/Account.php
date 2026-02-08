<?php

namespace App\Domains\Accounting\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Account extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'organization_id',
        'parent_id',
        'code',
        'name',
        'account_type',
        'currency_code',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    // Account types based on accounting standards
    public const TYPE_ASSET = 'asset';
    public const TYPE_LIABILITY = 'liability';
    public const TYPE_EQUITY = 'equity';
    public const TYPE_REVENUE = 'revenue';
    public const TYPE_EXPENSE = 'expense';

    /**
     * Get the organization that owns the account.
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(\App\Domains\Tenant\Models\Organization::class);
    }

    /**
     * Get the parent account.
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'parent_id');
    }

    /**
     * Get the child accounts.
     */
    public function children(): HasMany
    {
        return $this->hasMany(Account::class, 'parent_id');
    }

    /**
     * Get all journal entry lines for this account.
     */
    public function journalLines(): HasMany
    {
        return $this->hasMany(JournalEntryLine::class);
    }

    /**
     * Scope a query to only include active accounts.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Get account balance.
     */
    public function getBalance(): float
    {
        $debits = $this->journalLines()->sum('debit');
        $credits = $this->journalLines()->sum('credit');

        // For asset and expense accounts, debit increases balance
        if (in_array($this->account_type, [self::TYPE_ASSET, self::TYPE_EXPENSE])) {
            return $debits - $credits;
        }

        // For liability, equity, and revenue accounts, credit increases balance
        return $credits - $debits;
    }
}
