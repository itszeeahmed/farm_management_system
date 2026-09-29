<?php

namespace App\Domain\Animals\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Breed extends Model
{
    use HasFactory;

    protected $fillable = [
        'species_id',
        'code',
        'name',
        'origin_country',
        'standard_mature_weight_kg',
        'target_daily_yield_liters',
    ];

    protected $casts = [
        'standard_mature_weight_kg' => 'decimal:2',
        'target_daily_yield_liters' => 'decimal:2',
    ];

    public function species(): BelongsTo
    {
        return $this->belongsTo(Species::class);
    }

    public function animals(): HasMany
    {
        return $this->hasMany(Animal::class);
    }
}
