<?php

namespace App\Domain\Health\Models;

use App\Domain\Animals\Models\Animal;
use App\Domain\Organization\Models\Farm;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class HealthCase extends Model
{
    use HasFactory;

    protected $fillable = [
        'farm_id',
        'animal_id',
        'case_number',
        'symptom_observed_at',
        'diagnosis',
        'symptoms_description',
        'subjective_notes',
        'objective_temp_c',
        'objective_heart_rate',
        'objective_respiration_rate',
        'objective_rumen_motility_per_2min',
        'assessment_notes',
        'plan_notes',
        'severity',
        'status',
        'attending_vet_name',
        'resolved_at',
        'outcome_notes',
        'created_by',
    ];

    protected $casts = [
        'symptom_observed_at' => 'date',
        'resolved_at' => 'date',
        'objective_temp_c' => 'decimal:1',
        'objective_heart_rate' => 'integer',
        'objective_respiration_rate' => 'integer',
        'objective_rumen_motility_per_2min' => 'integer',
    ];

    public function farm(): BelongsTo
    {
        return $this->belongsTo(Farm::class);
    }

    public function animal(): BelongsTo
    {
        return $this->belongsTo(Animal::class);
    }

    public function treatments(): HasMany
    {
        return $this->hasMany(Treatment::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
