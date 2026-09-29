<?php

namespace App\Domain\Animals\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WeightRecord extends Model
{
    use HasFactory;

    protected $fillable = [
        'animal_id',
        'weight_kg',
        'recorded_at',
        'recorded_by_name',
        'body_condition_score',
        'notes',
    ];

    protected $casts = [
        'weight_kg' => 'decimal:2',
        'body_condition_score' => 'decimal:1',
        'recorded_at' => 'date',
    ];

    public function animal(): BelongsTo
    {
        return $this->belongsTo(Animal::class);
    }
}
