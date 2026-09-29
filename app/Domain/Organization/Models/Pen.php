<?php

namespace App\Domain\Organization\Models;

use App\Domain\Animals\Models\Animal;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Pen extends Model
{
    use HasFactory;

    protected $fillable = [
        'barn_id',
        'name',
        'code',
        'capacity',
    ];

    public function barn(): BelongsTo
    {
        return $this->belongsTo(Barn::class);
    }

    public function animals(): HasMany
    {
        return $this->hasMany(Animal::class);
    }
}
