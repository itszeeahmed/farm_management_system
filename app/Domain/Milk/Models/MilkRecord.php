<?php

namespace App\Domain\Milk\Models;

use App\Domain\Animals\Models\Animal;
use App\Domain\Health\Models\Treatment;
use App\Domain\Organization\Models\Farm;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MilkRecord extends Model
{
    use HasFactory;

    protected $fillable = [
        'farm_id',
        'animal_id',
        'milk_session_id',
        'recorded_date',
        'shift',
        'yield_liters',
        'flow_rate_kg_min',
        'milking_duration_seconds',
        'fat_percentage',
        'snf_percentage',
        'protein_percentage',
        'lactose_percentage',
        'scc',
        'temperature_c',
        'electrical_conductivity_ms_cm',
        'quality_status',
        'causative_treatment_id',
        'withholding_override_by',
        'withholding_override_reason',
        'discard_reason',
        'is_colostrum',
        'operator_notes',
        'recorded_by',
    ];

    protected $casts = [
        'recorded_date' => 'date',
        'yield_liters' => 'decimal:2',
        'flow_rate_kg_min' => 'decimal:2',
        'milking_duration_seconds' => 'integer',
        'fat_percentage' => 'decimal:2',
        'snf_percentage' => 'decimal:2',
        'protein_percentage' => 'decimal:2',
        'lactose_percentage' => 'decimal:2',
        'temperature_c' => 'decimal:1',
        'electrical_conductivity_ms_cm' => 'decimal:2',
        'scc' => 'integer',
        'is_colostrum' => 'boolean',
    ];

    public function farm(): BelongsTo
    {
        return $this->belongsTo(Farm::class);
    }

    public function animal(): BelongsTo
    {
        return $this->belongsTo(Animal::class);
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(MilkSession::class, 'milk_session_id');
    }

    public function treatment(): BelongsTo
    {
        return $this->belongsTo(Treatment::class, 'causative_treatment_id');
    }

    public function overrideAuthorizer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'withholding_override_by');
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function scopeSaleable(Builder $query): Builder
    {
        return $query->whereNotIn('quality_status', [
            'discarded_withdrawal',
            'discarded_mastitis',
            'rejected',
        ]);
    }

    public function scopeWithdrawn(Builder $query): Builder
    {
        return $query->where('quality_status', 'discarded_withdrawal');
    }
}
