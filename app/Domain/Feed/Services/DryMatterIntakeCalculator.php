<?php

namespace App\Domain\Feed\Services;

class DryMatterIntakeCalculator
{
    /**
     * Compute 4% Fat-Corrected Milk (FCM) in kg.
     * Gainsborough formula: 4% FCM = (0.4 * MilkYield) + (15 * (Fat% / 100) * MilkYield)
     */
    public function calculateFatCorrectedMilk(float $milkYieldKg, float $fatPercentage): float
    {
        $fcm = (0.4 * $milkYieldKg) + (15.0 * ($fatPercentage / 100.0) * $milkYieldKg);

        return round(max(0.0, $fcm), 2);
    }

    /**
     * Predict Daily Dry Matter Intake (DMI in kg/day) using NRC (2001) Dairy Cattle Equation.
     * DMI (kg/d) = (0.0118 * BW) + (0.303 * 4%FCM) * (1 - e^(-0.00124 * (DIM + 80)))
     *
     * @return array{
     *     predicted_dmi_kg: float,
     *     fcm_4_percent_kg: float,
     *     dmi_percent_body_weight: float
     * }
     */
    public function predictDairyCattleDmi(
        float $bodyWeightKg,
        float $milkYieldKg,
        float $fatPercentage = 3.8,
        int $daysInMilk = 90
    ): array {
        $fcm = $this->calculateFatCorrectedMilk($milkYieldKg, $fatPercentage);

        // NRC 2001 Dairy Cattle Equation:
        // DMI (kg/d) = (0.372 * FCM + 0.0968 * BW^0.75) * (1 - e^(-0.192 * (WOL + 3.67)))
        $wol = max(1.0, $daysInMilk / 7.0);
        $lagFactor = 1.0 - exp(-0.192 * ($wol + 3.67));
        $dmi = (0.372 * $fcm + 0.0968 * pow($bodyWeightKg, 0.75)) * $lagFactor;

        $predictedDmi = round(max(5.0, $dmi), 2);
        $percentBw = round(($predictedDmi / max(1.0, $bodyWeightKg)) * 100.0, 2);

        return [
            'predicted_dmi_kg' => $predictedDmi,
            'fcm_4_percent_kg' => $fcm,
            'dmi_percent_body_weight' => $percentBw,
        ];
    }

    /**
     * Predict Daily Dry Matter Intake for Small Ruminants (Goat / Sheep) using NRC Small Ruminant standard.
     * Maintenance + Lactation: DMI = (0.032 * BW) + (0.12 * MilkYieldKg)
     *
     * @return array{
     *     predicted_dmi_kg: float,
     *     dmi_percent_body_weight: float
     * }
     */
    public function predictSmallRuminantDmi(float $bodyWeightKg, float $milkYieldKg): array
    {
        $dmi = (0.032 * $bodyWeightKg) + (0.12 * $milkYieldKg);
        $predictedDmi = round(max(0.8, $dmi), 2);
        $percentBw = round(($predictedDmi / max(1.0, $bodyWeightKg)) * 100.0, 2);

        return [
            'predicted_dmi_kg' => $predictedDmi,
            'dmi_percent_body_weight' => $percentBw,
        ];
    }
}
