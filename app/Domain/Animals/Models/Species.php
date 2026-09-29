<?php

namespace App\Domain\Animals\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Species extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'scientific_name',
        'default_gestation_days',
        'default_lactation_days',
        'typical_birth_weight_kg',
        'typical_adult_weight_kg',
        'is_milk_producing',
        'is_meat_producing',
    ];

    protected $casts = [
        'is_milk_producing' => 'boolean',
        'is_meat_producing' => 'boolean',
        'typical_birth_weight_kg' => 'decimal:2',
        'typical_adult_weight_kg' => 'decimal:2',
    ];

    public function breeds(): HasMany
    {
        return $this->hasMany(Breed::class);
    }

    public function animals(): HasMany
    {
        return $this->hasMany(Animal::class);
    }
}
