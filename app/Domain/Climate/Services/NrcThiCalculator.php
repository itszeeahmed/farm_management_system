<?php

namespace App\Domain\Climate\Services;

class NrcThiCalculator
{
    /**
     * Compute Temperature-Humidity Index using standard NRC equation:
     * THI = (1.8 * T + 32) - (0.55 - 0.0055 * RH) * (1.8 * T - 26)
     */
    public function calculateThi(float $tempC, float $rhPercent): float
    {
        $thi = (1.8 * $tempC + 32.0) - (0.55 - (0.0055 * $rhPercent)) * (1.8 * $tempC - 26.0);

        return round($thi, 2);
    }

    /**
     * Determine heat stress risk classification and recommended cooling action.
     *
     * @return array{
     *     thi_index: float,
     *     heat_stress_level: string,
     *     cooling_actuator_activated: bool,
     *     recommended_action: string
     * }
     */
    public function assessHeatStress(float $tempC, float $rhPercent, string $speciesType = 'cattle'): array
    {
        $thi = $this->calculateThi($tempC, $rhPercent);

        if ($speciesType === 'goat' || $speciesType === 'sheep') {
            // Small ruminants have higher thermal threshold (~78 vs 72)
            $level = match (true) {
                $thi < 78.0 => 'comfortable',
                $thi < 84.0 => 'mild_stress',
                $thi < 89.0 => 'moderate_stress',
                default => 'severe_stress',
            };
        } else {
            // Dairy & beef cattle NRC thresholds
            $level = match (true) {
                $thi < 72.0 => 'comfortable',
                $thi < 79.0 => 'mild_stress',
                $thi < 89.0 => 'moderate_stress',
                $thi < 98.0 => 'severe_stress',
                default => 'emergency_danger',
            };
        }

        $actuatorActivated = in_array($level, ['moderate_stress', 'severe_stress', 'emergency_danger'], true);

        $action = match ($level) {
            'comfortable' => 'Natural ventilation adequate. Normal feeding schedule.',
            'mild_stress' => 'Enable barn cross-ventilation fans. Ensure fresh ad-libitum water supply.',
            'moderate_stress' => 'Trigger high-velocity barn circulation fans and high-pressure evaporative foggers.',
            'severe_stress' => 'Activate feed bunk soakers, maximum cooling fans, shift TMR feeding to night hours (10 PM - 4 AM).',
            'emergency_danger' => 'EMERGENCY: Direct cold water shower, electrolyte drenches, halt animal transport immediately.',
        };

        return [
            'thi_index' => $thi,
            'heat_stress_level' => $level,
            'cooling_actuator_activated' => $actuatorActivated,
            'recommended_action' => $action,
        ];
    }
}
