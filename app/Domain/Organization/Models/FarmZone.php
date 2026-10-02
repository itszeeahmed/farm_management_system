<?php

namespace App\Domain\Organization\Models;

use App\Domain\Animals\Models\Animal;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class FarmZone extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'farm_id',
        'name',
        'code',
        'type',
        'area_size',
        'area_unit',
        'soil_type',
        'irrigation_type',
        'status',
        'geojson',
        'notes',
    ];

    protected $casts = [
        'area_size' => 'decimal:2',
        'geojson' => 'array',
    ];

    public function farm(): BelongsTo
    {
        return $this->belongsTo(Farm::class);
    }

    public function structures(): HasMany
    {
        return $this->hasMany(FarmStructure::class, 'zone_id');
    }

    public function animals(): HasMany
    {
        return $this->hasMany(Animal::class, 'zone_id');
    }
}
