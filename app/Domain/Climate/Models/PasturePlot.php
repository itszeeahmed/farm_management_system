<?php

namespace App\Domain\Climate\Models;

use App\Domain\Organization\Models\Farm;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PasturePlot extends Model
{
    use HasFactory;

    protected $fillable = [
        'farm_id',
        'name',
        'code',
        'area_hectares',
        'forage_type',
        'soil_ph',
        'target_rest_days',
        'current_biomass_kg_dm_per_ha',
        'status',
        'last_grazed_at',
    ];

    protected $casts = [
        'area_hectares' => 'decimal:2',
        'soil_ph' => 'decimal:1',
        'target_rest_days' => 'integer',
        'current_biomass_kg_dm_per_ha' => 'decimal:2',
        'last_grazed_at' => 'datetime',
    ];

    public function farm(): BelongsTo
    {
        return $this->belongsTo(Farm::class);
    }

    public function grazingLogs(): HasMany
    {
        return $this->hasMany(GrazingLog::class);
    }
}
