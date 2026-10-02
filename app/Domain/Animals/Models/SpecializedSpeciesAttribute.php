<?php

namespace App\Domain\Animals\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SpecializedSpeciesAttribute extends Model
{
    use HasFactory;

    protected $fillable = [
        'animal_id',
        'species_type',
        'hump_condition_score',
        'draft_work_type',
        'racing_eligibility_status',
        'veterinary_passport_number',
        'microchip_transponder_rfid',
    ];

    protected $casts = [
        'hump_condition_score' => 'decimal:1',
        'racing_eligibility_status' => 'boolean',
    ];

    public function animal(): BelongsTo
    {
        return $this->belongsTo(Animal::class);
    }
}
