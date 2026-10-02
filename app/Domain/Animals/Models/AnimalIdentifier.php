<?php

namespace App\Domain\Animals\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AnimalIdentifier extends Model
{
    use HasFactory;

    protected $fillable = [
        'animal_id',
        'id_type',
        'id_value',
        'tag_color',
        'tag_placement',
        'is_primary',
        'applied_date',
        'notes',
    ];

    protected $casts = [
        'is_primary' => 'boolean',
        'applied_date' => 'date',
    ];

    public function animal(): BelongsTo
    {
        return $this->belongsTo(Animal::class);
    }
}
