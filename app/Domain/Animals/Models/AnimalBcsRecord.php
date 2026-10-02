<?php

namespace App\Domain\Animals\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AnimalBcsRecord extends Model
{
    use HasFactory;

    protected $fillable = [
        'animal_id',
        'bcs_score',
        'locomotion_score',
        'rumen_fill_score',
        'cleanliness_score',
        'assessed_at',
        'assessed_by',
        'notes',
    ];

    protected $casts = [
        'bcs_score' => 'decimal:2',
        'locomotion_score' => 'integer',
        'rumen_fill_score' => 'integer',
        'cleanliness_score' => 'integer',
        'assessed_at' => 'date',
    ];

    public function animal(): BelongsTo
    {
        return $this->belongsTo(Animal::class);
    }

    public function assessor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assessed_by');
    }
}
