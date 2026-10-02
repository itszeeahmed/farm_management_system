<?php

namespace App\Domain\Inventory\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MaintenanceLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'farm_asset_id',
        'maintenance_type',
        'service_date',
        'technician_name',
        'meter_reading',
        'downtime_hours',
        'parts_cost',
        'labor_cost',
        'total_cost',
        'next_service_due_date',
        'next_service_due_meter',
        'notes',
    ];

    protected $casts = [
        'service_date' => 'date',
        'meter_reading' => 'decimal:1',
        'downtime_hours' => 'decimal:2',
        'parts_cost' => 'decimal:2',
        'labor_cost' => 'decimal:2',
        'total_cost' => 'decimal:2',
        'next_service_due_date' => 'date',
        'next_service_due_meter' => 'decimal:1',
    ];

    public function farmAsset(): BelongsTo
    {
        return $this->belongsTo(FarmAsset::class);
    }
}
