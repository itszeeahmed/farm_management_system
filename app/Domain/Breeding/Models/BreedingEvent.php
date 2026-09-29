<?php

namespace App\Domain\Breeding\Models;

use App\Domain\Animals\Models\Animal;
use App\Domain\Organization\Models\Farm;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class BreedingEvent extends Model
{
    use HasFactory;

    protected $fillable = [
        'farm_id',
        'animal_id',
        'sire_id',
        'method',
        'semen_straw_code',
        'sire_breed_code',
        'insemination_datetime',
        'technician_name',
        'cost',
        'status',
        'notes',
    ];

    protected $casts = [
        'insemination_datetime' => 'datetime',
        'cost' => 'decimal:2',
    ];

    public function farm(): BelongsTo
    {
        return $this->belongsTo(Farm::class);
    }

    public function animal(): BelongsTo
    {
        return $this->belongsTo(Animal::class);
    }

    public function sire(): BelongsTo
    {
        return $this->belongsTo(Animal::class, 'sire_id');
    }

    public function pregnancy(): HasOne
    {
        return $this->hasOne(Pregnancy::class);
    }
}
