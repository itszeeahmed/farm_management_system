<?php

namespace App\Domain\Inventory\Models;

use App\Domain\Organization\Models\Farm;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FarmAsset extends Model
{
    use HasFactory;

    protected $fillable = [
        'farm_id',
        'name',
        'asset_code',
        'category',
        'make',
        'model',
        'serial_number',
        'purchase_date',
        'purchase_cost',
        'meter_type',
        'current_meter_reading',
        'status',
    ];

    protected $casts = [
        'purchase_date' => 'date',
        'purchase_cost' => 'decimal:2',
        'current_meter_reading' => 'decimal:1',
    ];

    public function farm(): BelongsTo
    {
        return $this->belongsTo(Farm::class);
    }

    public function maintenanceLogs(): HasMany
    {
        return $this->hasMany(MaintenanceLog::class);
    }
}
