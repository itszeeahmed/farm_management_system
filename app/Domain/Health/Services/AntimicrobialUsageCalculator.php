<?php

namespace App\Domain\Health\Services;

use App\Domain\Health\Models\Medicine;
use App\Domain\Health\Models\Treatment;

class AntimicrobialUsageCalculator
{
    /**
     * Calculate Defined Daily Dose Animal (DDDA) consumed for a treatment event.
     * Formula: DDDA = (Active Substance Administered in mg) / (Standard DDDA in mg/kg * Animal Body Weight in kg)
     */
    public function calculateTreatmentDdda(
        Medicine $medicine,
        float $activeSubstanceMg,
        float $animalWeightKg
    ): float {
        if (! $medicine->is_antimicrobial) {
            return 0.0;
        }

        $stdDdda = (float) ($medicine->standard_ddda_mg_per_kg ?? 10.0);
        if ($stdDdda <= 0 || $animalWeightKg <= 0) {
            return 0.0;
        }

        $ddda = $activeSubstanceMg / ($stdDdda * $animalWeightKg);

        return round(max(0.0, $ddda), 3);
    }

    /**
     * Evaluate antimicrobial compliance before treatment administration.
     * Enforces that WHO Critically Important Antimicrobials require a prescription and vet license.
     *
     * @return array{
     *     allowed: bool,
     *     warning: string|null,
     *     requires_justification: bool
     * }
     */
    public function evaluateAntimicrobialCompliance(
        Medicine $medicine,
        ?string $prescriptionNumber,
        ?string $vetLicenseNumber
    ): array {
        if (! $medicine->is_antimicrobial) {
            return [
                'allowed' => true,
                'warning' => null,
                'requires_justification' => false,
            ];
        }

        if ($medicine->who_classification === 'critically_important') {
            if (empty($prescriptionNumber) || empty($vetLicenseNumber)) {
                return [
                    'allowed' => false,
                    'warning' => "Medicine {$medicine->name} is a WHO Highest Priority Critically Important Antimicrobial (CIA). Valid veterinarian prescription number and license number are mandatory.",
                    'requires_justification' => true,
                ];
            }

            return [
                'allowed' => true,
                'warning' => "NOTICE: Administering WHO Critically Important Antimicrobial ({$medicine->name}). Treatment logged in AMU national compliance registry.",
                'requires_justification' => true,
            ];
        }

        return [
            'allowed' => true,
            'warning' => null,
            'requires_justification' => false,
        ];
    }
}
