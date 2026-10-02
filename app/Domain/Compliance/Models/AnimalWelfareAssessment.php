<?php

namespace App\Domain\Compliance\Models;

use App\Domain\Organization\Models\Farm;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AnimalWelfareAssessment extends Model
{
    use HasFactory;

    protected $fillable = [
        'farm_id',
        'audit_date',
        'auditor_name',
        'water_access_score',
        'thermal_comfort_score',
        'bedding_cleanliness_score',
        'lameness_prevalence_percent',
        'space_allowance_score',
        'overall_welfare_grade',
        'corrective_actions',
    ];

    protected $casts = [
        'audit_date' => 'date',
        'water_access_score' => 'integer',
        'thermal_comfort_score' => 'integer',
        'bedding_cleanliness_score' => 'integer',
        'lameness_prevalence_percent' => 'decimal:2',
        'space_allowance_score' => 'integer',
    ];

    public function farm(): BelongsTo
    {
        return $this->belongsTo(Farm::class);
    }
}
