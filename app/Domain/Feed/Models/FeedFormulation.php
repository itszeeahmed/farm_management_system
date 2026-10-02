<?php

namespace App\Domain\Feed\Models;

use App\Domain\Organization\Models\Farm;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class FeedFormulation extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'farm_id',
        'name',
        'code',
        'species_type',
        'target_stage',
        'target_dmi_kg',
        'calculated_cp_percent',
        'calculated_nel_mcal',
        'calculated_cost_per_head_day',
        'ingredients',
        'is_active',
        'notes',
    ];

    protected $casts = [
        'target_dmi_kg' => 'decimal:2',
        'calculated_cp_percent' => 'decimal:2',
        'calculated_nel_mcal' => 'decimal:2',
        'calculated_cost_per_head_day' => 'decimal:2',
        'ingredients' => 'array',
        'is_active' => 'boolean',
    ];

    public function farm(): BelongsTo
    {
        return $this->belongsTo(Farm::class);
    }

    public function tmrBatches(): HasMany
    {
        return $this->hasMany(TmrBatch::class);
    }
}
