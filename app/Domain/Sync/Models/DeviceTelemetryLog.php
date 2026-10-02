<?php

namespace App\Domain\Sync\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeviceTelemetryLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'device_registry_id',
        'recorded_at',
        'metric_name',
        'metric_value',
        'unit_of_measure',
        'raw_payload_json',
    ];

    protected $casts = [
        'recorded_at' => 'datetime',
        'metric_value' => 'decimal:4',
        'raw_payload_json' => 'array',
    ];

    public function device(): BelongsTo
    {
        return $this->belongsTo(DeviceRegistry::class, 'device_registry_id');
    }
}
