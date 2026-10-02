<?php

namespace App\Domain\Feed\Models;

use App\Domain\Organization\Models\Farm;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SilageBunker extends Model
{
    use HasFactory;

    protected $fillable = [
        'farm_id',
        'bunker_code',
        'name',
        'crop_type',
        'initial_tonnage',
        'remaining_tonnage',
        'face_temperature_c',
        'ph_level',
        'compaction_density_kg_m3',
        'fermentation_score',
        'status',
    ];

    protected $casts = [
        'initial_tonnage' => 'decimal:2',
        'remaining_tonnage' => 'decimal:2',
        'face_temperature_c' => 'decimal:1',
        'ph_level' => 'decimal:1',
        'compaction_density_kg_m3' => 'decimal:1',
    ];

    public function farm(): BelongsTo
    {
        return $this->belongsTo(Farm::class);
    }
}
