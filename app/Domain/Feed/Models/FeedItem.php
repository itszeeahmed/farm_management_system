<?php

namespace App\Domain\Feed\Models;

use App\Domain\Organization\Models\Farm;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FeedItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'farm_id',
        'name',
        'code',
        'category',
        'unit',
        'current_stock',
        'minimum_stock_alert',
        'cost_per_unit',
        'dry_matter_percentage',
        'crude_protein_percentage',
        'ndf_percentage',
        'adf_percentage',
        'nel_mcal_per_kg',
        'tdn_percentage',
        'calcium_percentage',
        'phosphorus_percentage',
        'ash_percentage',
        'is_active',
        'notes',
    ];

    protected $casts = [
        'current_stock' => 'decimal:2',
        'minimum_stock_alert' => 'decimal:2',
        'cost_per_unit' => 'decimal:2',
        'dry_matter_percentage' => 'decimal:2',
        'crude_protein_percentage' => 'decimal:2',
        'ndf_percentage' => 'decimal:2',
        'adf_percentage' => 'decimal:2',
        'nel_mcal_per_kg' => 'decimal:2',
        'tdn_percentage' => 'decimal:2',
        'calcium_percentage' => 'decimal:2',
        'phosphorus_percentage' => 'decimal:2',
        'ash_percentage' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function farm(): BelongsTo
    {
        return $this->belongsTo(Farm::class);
    }

    public function consumptions(): HasMany
    {
        return $this->hasMany(FeedConsumption::class);
    }

    public function getIsLowStockAttribute(): bool
    {
        return $this->current_stock <= $this->minimum_stock_alert;
    }
}
