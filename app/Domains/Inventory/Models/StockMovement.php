<?php

namespace App\Domains\Inventory\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockMovement extends Model
{
    use HasFactory;

    protected $fillable = [
        'organization_id',
        'product_id',
        'location_id',
        'movement_type',
        'reference_type',
        'reference_id',
        'quantity',
        'unit_cost',
        'movement_date',
        'notes',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'unit_cost' => 'decimal:2',
        'movement_date' => 'datetime',
    ];

    public const TYPE_IN = 'in';
    public const TYPE_OUT = 'out';
    public const TYPE_ADJUSTMENT = 'adjustment';
    public const TYPE_TRANSFER = 'transfer';

    /**
     * Get the organization that owns the stock movement.
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(\App\Domains\Tenant\Models\Organization::class);
    }

    /**
     * Get the product for the stock movement.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the location for the stock movement.
     */
    public function location(): BelongsTo
    {
        return $this->belongsTo(\App\Domains\Tenant\Models\Branch::class, 'location_id');
    }

    /**
     * Get signed quantity based on movement type.
     */
    public function getSignedQuantity(): float
    {
        return $this->movement_type === self::TYPE_IN ? 
            $this->quantity : -$this->quantity;
    }
}
