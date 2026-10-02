<?php

namespace App\Domain\Animals\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AnimalWeight extends Model
{
    use HasFactory;

    protected $fillable = [
        'animal_id',
        'weight_kg',
        'weighing_method',
        'heart_girth_cm',
        'body_length_cm',
        'previous_weight_kg',
        'days_since_previous',
        'average_daily_gain_kg',
        'recorded_at',
        'recorded_by',
        'notes',
    ];

    protected $casts = [
        'weight_kg' => 'decimal:2',
        'heart_girth_cm' => 'decimal:2',
        'body_length_cm' => 'decimal:2',
        'previous_weight_kg' => 'decimal:2',
        'days_since_previous' => 'integer',
        'average_daily_gain_kg' => 'decimal:3',
        'recorded_at' => 'date',
    ];

    public function animal(): BelongsTo
    {
        return $this->belongsTo(Animal::class);
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}
