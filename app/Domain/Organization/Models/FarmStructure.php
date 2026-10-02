<?php

namespace App\Domain\Organization\Models;

use App\Domain\Animals\Models\Animal;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class FarmStructure extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'farm_id',
        'zone_id',
        'parent_structure_id',
        'name',
        'code',
        'structure_type',
        'target_species',
        'capacity',
        'area_sq_meters',
        'ventilation_type',
        'has_automated_feeders',
        'has_automated_waterers',
        'has_misting_cooling',
        'current_headcount',
        'notes',
    ];

    protected $casts = [
        'capacity' => 'integer',
        'area_sq_meters' => 'decimal:2',
        'has_automated_feeders' => 'boolean',
        'has_automated_waterers' => 'boolean',
        'has_misting_cooling' => 'boolean',
        'current_headcount' => 'integer',
    ];

    public function farm(): BelongsTo
    {
        return $this->belongsTo(Farm::class);
    }

    public function zone(): BelongsTo
    {
        return $this->belongsTo(FarmZone::class, 'zone_id');
    }

    public function parentStructure(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_structure_id');
    }

    public function subStructures(): HasMany
    {
        return $this->hasMany(self::class, 'parent_structure_id');
    }

    public function animals(): HasMany
    {
        return $this->hasMany(Animal::class, 'structure_id');
    }

    public function getStockingRateAttribute(): float
    {
        if ($this->capacity <= 0) {
            return 0.0;
        }

        return round(($this->current_headcount / $this->capacity) * 100, 1);
    }
}
