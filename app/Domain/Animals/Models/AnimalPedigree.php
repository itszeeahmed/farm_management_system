<?php

namespace App\Domain\Animals\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AnimalPedigree extends Model
{
    use HasFactory;

    protected $fillable = [
        'animal_id',
        'sire_id',
        'sire_name',
        'sire_code',
        'sire_breed',
        'dam_id',
        'dam_name',
        'dam_code',
        'maternal_grandsire_code',
        'paternal_grandsire_code',
        'generation_depth',
        'inbreeding_coefficient',
        'pedigree_tree_json',
    ];

    protected $casts = [
        'generation_depth' => 'integer',
        'inbreeding_coefficient' => 'decimal:4',
        'pedigree_tree_json' => 'array',
    ];

    public function animal(): BelongsTo
    {
        return $this->belongsTo(Animal::class);
    }

    public function sire(): BelongsTo
    {
        return $this->belongsTo(Animal::class, 'sire_id');
    }

    public function dam(): BelongsTo
    {
        return $this->belongsTo(Animal::class, 'dam_id');
    }
}
