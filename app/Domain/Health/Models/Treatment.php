<?php

namespace App\Domain\Health\Models;

use App\Domain\Animals\Models\Animal;
use App\Domain\Organization\Models\Farm;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Treatment extends Model
{
    use HasFactory;

    protected $fillable = [
        'farm_id',
        'animal_id',
        'health_case_id',
        'medicine_id',
        'administered_at',
        'dosage',
        'dosage_unit',
        'route',
        'milk_withdrawal_until',
        'meat_withdrawal_until',
        'administered_by',
        'veterinarian_license_number',
        'prescription_number',
        'batch_lot_number',
        'active_substance_administered_mg',
        'ddda_units_consumed',
        'cost',
        'notes',
    ];

    protected $casts = [
        'administered_at' => 'datetime',
        'milk_withdrawal_until' => 'datetime',
        'meat_withdrawal_until' => 'datetime',
        'dosage' => 'decimal:2',
        'active_substance_administered_mg' => 'decimal:2',
        'ddda_units_consumed' => 'decimal:3',
        'cost' => 'decimal:2',
    ];

    protected $appends = [
        'is_milk_withdrawn',
    ];

    public function farm(): BelongsTo
    {
        return $this->belongsTo(Farm::class);
    }

    public function animal(): BelongsTo
    {
        return $this->belongsTo(Animal::class);
    }

    public function healthCase(): BelongsTo
    {
        return $this->belongsTo(HealthCase::class);
    }

    public function medicine(): BelongsTo
    {
        return $this->belongsTo(Medicine::class);
    }

    public function getIsMilkWithdrawnAttribute(): bool
    {
        return $this->milk_withdrawal_until && Carbon::now()->lt($this->milk_withdrawal_until);
    }
}
