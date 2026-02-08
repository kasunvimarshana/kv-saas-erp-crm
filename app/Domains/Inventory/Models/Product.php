<?php

namespace App\Domains\Inventory\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'organization_id',
        'code',
        'name',
        'description',
        'product_type',
        'category_id',
        'unit_of_measure_id',
        'cost_price',
        'selling_price',
        'barcode',
        'sku',
        'track_inventory',
        'reorder_level',
        'status',
    ];

    protected $casts = [
        'cost_price' => 'decimal:2',
        'selling_price' => 'decimal:2',
        'reorder_level' => 'decimal:2',
        'track_inventory' => 'boolean',
    ];

    public const TYPE_GOODS = 'goods';
    public const TYPE_SERVICE = 'service';
    public const TYPE_CONSUMABLE = 'consumable';

    /**
     * Get the organization that owns the product.
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(\App\Domains\Tenant\Models\Organization::class);
    }

    /**
     * Get the unit of measure for the product.
     */
    public function unitOfMeasure(): BelongsTo
    {
        return $this->belongsTo(\App\Domains\Shared\Models\UnitOfMeasure::class);
    }

    /**
     * Get the stock movements for the product.
     */
    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    /**
     * Get current stock quantity for a specific location.
     */
    public function getStockQuantity(?int $locationId = null): float
    {
        $query = $this->stockMovements();
        
        if ($locationId) {
            $query->where('location_id', $locationId);
        }

        return $query->sum('quantity');
    }

    /**
     * Scope a query to only include active products.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
}
