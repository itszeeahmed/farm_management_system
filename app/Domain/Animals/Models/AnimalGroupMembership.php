<?php

namespace App\Domain\Animals\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AnimalGroupMembership extends Model
{
    use HasFactory;

    protected $fillable = [
        'group_id',
        'animal_id',
        'joined_at',
        'left_at',
        'is_current',
        'reason',
    ];

    protected $casts = [
        'joined_at' => 'date',
        'left_at' => 'date',
        'is_current' => 'boolean',
    ];

    public function group(): BelongsTo
    {
        return $this->belongsTo(AnimalGroup::class, 'group_id');
    }

    public function animal(): BelongsTo
    {
        return $this->belongsTo(Animal::class, 'animal_id');
    }
}
