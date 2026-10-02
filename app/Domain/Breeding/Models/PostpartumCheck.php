<?php

namespace App\Domain\Breeding\Models;

use App\Domain\Animals\Models\Animal;
use App\Domain\Organization\Models\Farm;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PostpartumCheck extends Model
{
    use HasFactory;

    protected $fillable = [
        'farm_id',
        'animal_id',
        'birth_id',
        'check_date',
        'days_in_milk',
        'rectal_temperature_c',
        'lochia_score',
        'ketosis_test_bhb_mmol_l',
        'uterine_involution_status',
        'checked_by',
        'clinical_notes',
    ];

    protected $casts = [
        'check_date' => 'date',
        'days_in_milk' => 'integer',
        'rectal_temperature_c' => 'decimal:1',
        'lochia_score' => 'integer',
        'ketosis_test_bhb_mmol_l' => 'decimal:2',
    ];

    public function farm(): BelongsTo
    {
        return $this->belongsTo(Farm::class);
    }

    public function animal(): BelongsTo
    {
        return $this->belongsTo(Animal::class);
    }

    public function birth(): BelongsTo
    {
        return $this->belongsTo(Birth::class);
    }

    public function getHasSubclinicalKetosisAttribute(): bool
    {
        return (float) $this->ketosis_test_bhb_mmol_l >= 1.20;
    }

    public function getHasMetritisRiskAttribute(): bool
    {
        return $this->lochia_score >= 2 || (float) $this->rectal_temperature_c >= 39.5;
    }
}
