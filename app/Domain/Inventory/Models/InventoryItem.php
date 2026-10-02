<?php

namespace App\Domain\Inventory\Models;

use App\Domain\Organization\Models\Farm;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InventoryItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'farm_id',
        'warehouse_id',
        'category',
        'sku',
        'name',
        'unit_of_measure',
        'current_stock_quantity',
        'reorder_level_quantity',
        'safety_stock_quantity',
        'unit_cost',
        'is_active',
    ];

    protected $casts = [
        'current_stock_quantity' => 'decimal:2',
        'reorder_level_quantity' => 'decimal:2',
        'safety_stock_quantity' => 'decimal:2',
        'unit_cost' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function farm(): BelongsTo
    {
        return $this->belongsTo(Farm::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(InventoryTransaction::class);
    }

    public function isLowStock(): bool
    {
        return (float) $this->current_stock_quantity <= (float) $this->reorder_level_quantity;
    }
}
