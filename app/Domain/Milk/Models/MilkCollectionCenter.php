<?php

namespace App\Domain\Milk\Models;

use App\Domain\Organization\Models\Organization;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class MilkCollectionCenter extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'organization_id',
        'center_code',
        'name',
        'location',
        'latitude',
        'longitude',
        'chilling_capacity_liters',
        'current_volume_liters',
        'route_code',
        'status',
    ];

    protected $casts = [
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',
        'chilling_capacity_liters' => 'decimal:2',
        'current_volume_liters' => 'decimal:2',
    ];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function suppliers(): HasMany
    {
        return $this->hasMany(FarmerSupplier::class, 'collection_center_id');
    }

    public function intakes(): HasMany
    {
        return $this->hasMany(MilkCollectionIntake::class, 'collection_center_id');
    }

    public function getCapacityUtilizationPercentAttribute(): float
    {
        if ($this->chilling_capacity_liters <= 0) {
            return 0.0;
        }

        return round(((float) $this->current_volume_liters / (float) $this->chilling_capacity_liters) * 100, 1);
    }
}
