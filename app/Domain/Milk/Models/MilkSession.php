<?php

namespace App\Domain\Milk\Models;

use App\Domain\Organization\Models\Farm;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MilkSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'farm_id',
        'session_date',
        'shift',
        'total_yield_liters',
        'bulk_tank_temperature_c',
        'milker_id',
        'notes',
    ];

    protected $casts = [
        'session_date' => 'date',
        'total_yield_liters' => 'decimal:2',
        'bulk_tank_temperature_c' => 'decimal:1',
    ];

    public function farm(): BelongsTo
    {
        return $this->belongsTo(Farm::class);
    }

    public function milker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'milker_id');
    }

    public function records(): HasMany
    {
        return $this->hasMany(MilkRecord::class);
    }
}
