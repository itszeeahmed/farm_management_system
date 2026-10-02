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
        'bulk_tank_id',
        'parlor_identifier',
        'session_date',
        'shift',
        'total_yield_liters',
        'bulk_tank_temperature_c',
        'ambient_temp_c',
        'chiller_temp_c',
        'milker_id',
        'notes',
        'started_at',
        'ended_at',
    ];

    protected $casts = [
        'session_date' => 'date',
        'total_yield_liters' => 'decimal:2',
        'bulk_tank_temperature_c' => 'decimal:1',
        'ambient_temp_c' => 'decimal:1',
        'chiller_temp_c' => 'decimal:1',
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
    ];

    public function farm(): BelongsTo
    {
        return $this->belongsTo(Farm::class);
    }

    public function bulkTank(): BelongsTo
    {
        return $this->belongsTo(BulkTank::class);
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
