<?php

namespace App\Domain\Sync\Models;

use App\Domain\Organization\Models\Farm;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DeviceRegistry extends Model
{
    use HasFactory;

    protected $fillable = [
        'farm_id',
        'device_identifier',
        'device_name',
        'device_type',
        'api_key',
        'firmware_version',
        'ip_address',
        'battery_percentage',
        'last_heartbeat_at',
        'status',
    ];

    protected $casts = [
        'battery_percentage' => 'decimal:2',
        'last_heartbeat_at' => 'datetime',
    ];

    public function farm(): BelongsTo
    {
        return $this->belongsTo(Farm::class);
    }

    public function telemetryLogs(): HasMany
    {
        return $this->hasMany(DeviceTelemetryLog::class);
    }
}
