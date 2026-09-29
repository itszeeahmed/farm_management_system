<?php

namespace App\Domain\Health\Models;

use App\Domain\Animals\Models\Animal;
use App\Domain\Organization\Models\Farm;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Vaccination extends Model
{
    use HasFactory;

    protected $fillable = [
        'farm_id',
        'animal_id',
        'vaccine_name',
        'batch_lot_number',
        'administered_date',
        'booster_due_date',
        'disease_targeted',
        'administered_by',
        'notes',
    ];

    protected $casts = [
        'administered_date' => 'date',
        'booster_due_date' => 'date',
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
