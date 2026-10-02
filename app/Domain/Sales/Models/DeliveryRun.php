<?php

namespace App\Domain\Sales\Models;

use App\Domain\Organization\Models\Farm;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DeliveryRun extends Model
{
    use HasFactory;

    protected $fillable = [
        'farm_id',
        'run_date',
        'route_name',
        'driver_name',
        'vehicle_plate_number',
        'vehicle_departure_temp_c',
        'total_liters_planned',
        'total_liters_delivered',
        'status',
    ];

    protected $casts = [
        'run_date' => 'date',
        'vehicle_departure_temp_c' => 'decimal:1',
        'total_liters_planned' => 'decimal:2',
        'total_liters_delivered' => 'decimal:2',
    ];

    public function farm(): BelongsTo
    {
        return $this->belongsTo(Farm::class);
    }

    public function stops(): HasMany
    {
        return $this->hasMany(DeliveryRunStop::class)->orderBy('stop_sequence');
    }
}
