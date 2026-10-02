<?php

namespace App\Domain\Milk\Models;

use App\Domain\Organization\Models\Farm;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class BulkTank extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'farm_id',
        'tank_code',
        'model_name',
        'capacity_liters',
        'current_volume_liters',
        'target_temperature_c',
        'current_temperature_c',
        'cooling_status',
        'agitator_status',
        'last_cip_cleaned_at',
        'last_cip_cleaned_by',
        'is_sanitized',
        'status',
    ];

    protected $casts = [
        'capacity_liters' => 'decimal:2',
        'current_volume_liters' => 'decimal:2',
        'target_temperature_c' => 'decimal:1',
        'current_temperature_c' => 'decimal:1',
        'last_cip_cleaned_at' => 'datetime',
        'is_sanitized' => 'boolean',
    ];

    public function farm(): BelongsTo
    {
        return $this->belongsTo(Farm::class);
    }

    public function cipCleaner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'last_cip_cleaned_by');
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(MilkSession::class);
    }

    public function dispatches(): HasMany
    {
        return $this->hasMany(MilkDispatch::class);
    }

    public function getFillPercentageAttribute(): float
    {
        if ($this->capacity_liters <= 0) {
            return 0.0;
        }

        return round(((float) $this->current_volume_liters / (float) $this->capacity_liters) * 100, 1);
    }
}
