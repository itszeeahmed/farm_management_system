<?php

namespace App\Domain\Animals\Models;

use App\Domain\Organization\Models\Farm;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FleeceRecord extends Model
{
    use HasFactory;

    protected $fillable = [
        'farm_id',
        'animal_id',
        'shearing_date',
        'fleece_type',
        'grease_fleece_weight_kg',
        'clean_fleece_weight_kg',
        'clean_yield_percentage',
        'micron_grade',
        'quality_tier',
        'staple_length_mm',
        'shearer_name',
        'notes',
    ];

    protected $casts = [
        'shearing_date' => 'date',
        'grease_fleece_weight_kg' => 'decimal:2',
        'clean_fleece_weight_kg' => 'decimal:2',
        'clean_yield_percentage' => 'decimal:2',
        'micron_grade' => 'decimal:1',
        'staple_length_mm' => 'decimal:1',
    ];

    public function farm(): BelongsTo
    {
        return $this->belongsTo(Farm::class);
    }

    public function animal(): BelongsTo
    {
        return $this->belongsTo(Animal::class);
    }
}
