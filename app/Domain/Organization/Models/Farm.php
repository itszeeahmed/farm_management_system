<?php

namespace App\Domain\Organization\Models;

use App\Domain\Animals\Models\Animal;
use App\Domain\Milk\Models\MilkRecord;
use App\Domain\Milk\Models\MilkSession;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Farm extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'organization_id',
        'name',
        'code',
        'type',
        'location',
        'latitude',
        'longitude',
        'total_area',
        'area_unit',
        'timezone',
        'climate_settings',
    ];

    protected $casts = [
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',
        'total_area' => 'decimal:2',
        'climate_settings' => 'array',
    ];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function barns(): HasMany
    {
        return $this->hasMany(Barn::class);
    }

    public function animals(): HasMany
    {
        return $this->hasMany(Animal::class);
    }

    public function milkSessions(): HasMany
    {
        return $this->hasMany(MilkSession::class);
    }

    public function milkRecords(): HasMany
    {
        return $this->hasMany(MilkRecord::class);
    }
}
