<?php

namespace App\Domain\Animals\Models;

use App\Domain\Organization\Models\Farm;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AnimalGroup extends Model
{
    use HasFactory;

    protected $fillable = [
        'farm_id',
        'name',
        'code',
        'group_type',
        'criteria_rules',
        'is_dynamic',
        'description',
    ];

    protected $casts = [
        'criteria_rules' => 'array',
        'is_dynamic' => 'boolean',
    ];

    public function farm(): BelongsTo
    {
        return $this->belongsTo(Farm::class);
    }

    public function memberships(): HasMany
    {
        return $this->hasMany(AnimalGroupMembership::class, 'group_id');
    }

    public function currentMembers(): BelongsToMany
    {
        return $this->belongsToMany(Animal::class, 'animal_group_memberships', 'group_id', 'animal_id')
            ->wherePivot('is_current', true)
            ->withTimestamps();
    }

    public function allMembers(): BelongsToMany
    {
        return $this->belongsToMany(Animal::class, 'animal_group_memberships', 'group_id', 'animal_id')
            ->withPivot(['joined_at', 'left_at', 'is_current', 'reason'])
            ->withTimestamps();
    }
}
