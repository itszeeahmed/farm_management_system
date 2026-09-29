<?php

namespace App\Domain\Climate\Models;

use App\Domain\Organization\Models\Barn;
use App\Domain\Organization\Models\Farm;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClimateReading extends Model
{
    use HasFactory;

    protected $fillable = [
        'farm_id',
        'barn_id',
        'recorded_at',
        'temperature_c',
        'relative_humidity_percent',
        'thi_index',
        'heat_stress_level',
        'sensor_device_id',
        'mitigation_action_taken',
    ];

    protected $casts = [
        'recorded_at' => 'datetime',
        'temperature_c' => 'decimal:1',
        'relative_humidity_percent' => 'decimal:2',
        'thi_index' => 'decimal:2',
    ];

    public function farm(): BelongsTo
    {
        return $this->belongsTo(Farm::class);
    }

    public function barn(): BelongsTo
    {
        return $this->belongsTo(Barn::class);
    }

    /**
     * Calculate NRC Temperature-Humidity Index (THI)
     */
    public static function calculateTHI(float $tempC, float $rhPercent): float
    {
        // THI = (1.8 * T + 32) - ((0.55 - 0.0055 * RH) * (1.8 * T - 26))
        $thi = (1.8 * $tempC + 32) - ((0.55 - 0.0055 * $rhPercent) * (1.8 * $tempC - 26));

        return round($thi, 2);
    }

    /**
     * Determine heat stress risk classification
     */
    public static function determineHeatStressLevel(float $thi): string
    {
        if ($thi < 72) {
            return 'comfortable';
        }
        if ($thi < 79) {
            return 'mild_stress';
        }
        if ($thi < 89) {
            return 'moderate_stress';
        }
        if ($thi < 98) {
            return 'severe_stress';
        }

        return 'emergency_danger';
    }
}
