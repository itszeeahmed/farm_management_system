<?php

namespace App\Domain\Breeding\Models;

use App\Domain\Animals\Models\Animal;
use App\Domain\Organization\Models\Farm;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Pregnancy extends Model
{
    use HasFactory;

    protected $fillable = [
        'farm_id',
        'animal_id',
        'breeding_event_id',
        'check_date',
        'method',
        'status',
        'expected_delivery_date',
        'expected_dry_off_date',
        'checked_by',
        'notes',
    ];

    protected $casts = [
        'check_date' => 'date',
        'expected_delivery_date' => 'date',
        'expected_dry_off_date' => 'date',
    ];

    public function farm(): BelongsTo
    {
        return $this->belongsTo(Farm::class);
    }

    public function animal(): BelongsTo
    {
        return $this->belongsTo(Animal::class);
    }

    public function breedingEvent(): BelongsTo
    {
        return $this->belongsTo(BreedingEvent::class);
    }

    public function birth(): HasOne
    {
        return $this->hasOne(Birth::class);
    }
}
