<?php

namespace App\Domain\Breeding\Models;

use App\Domain\Animals\Models\Animal;
use App\Domain\Organization\Models\Farm;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Birth extends Model
{
    use HasFactory;

    protected $fillable = [
        'farm_id',
        'dam_id',
        'sire_id',
        'pregnancy_id',
        'calving_datetime',
        'calving_ease',
        'offspring_count',
        'live_count',
        'stillborn_count',
        'colostrum_fed',
        'colostrum_liters',
        'attendant_name',
        'notes',
    ];

    protected $casts = [
        'calving_datetime' => 'datetime',
        'colostrum_fed' => 'boolean',
        'colostrum_liters' => 'decimal:2',
        'offspring_count' => 'integer',
        'live_count' => 'integer',
        'stillborn_count' => 'integer',
    ];

    public function farm(): BelongsTo
    {
        return $this->belongsTo(Farm::class);
    }

    public function dam(): BelongsTo
    {
        return $this->belongsTo(Animal::class, 'dam_id');
    }

    public function sire(): BelongsTo
    {
        return $this->belongsTo(Animal::class, 'sire_id');
    }

    public function pregnancy(): BelongsTo
    {
        return $this->belongsTo(Pregnancy::class);
    }
}
