<?php

namespace App\Domain\Animals\Services;

class FleeceGradingClassifier
{
    /**
     * Classify fleece into international quality tier based on fiber micron diameter.
     */
    public function classifyTier(float $micronGrade): string
    {
        return match (true) {
            $micronGrade < 17.5 => 'ultrafine',
            $micronGrade <= 18.5 => 'superfine',
            $micronGrade <= 19.5 => 'fine',
            $micronGrade <= 22.5 => 'medium',
            default => 'coarse',
        };
    }

    /**
     * Compute clean yield percentage and net clean weight.
     *
     * @return array{
     *     clean_yield_percentage: float,
     *     clean_fleece_weight_kg: float,
     *     quality_tier: string
     * }
     */
    public function gradeFleece(
        float $greaseWeightKg,
        float $micronGrade,
        float $dirtYieldDeductionPercent = 35.0
    ): array {
        $yieldPercent = max(20.0, round(100.0 - $dirtYieldDeductionPercent, 2));
        $cleanWeight = round($greaseWeightKg * ($yieldPercent / 100.0), 2);
        $tier = $this->classifyTier($micronGrade);

        return [
            'clean_yield_percentage' => $yieldPercent,
            'clean_fleece_weight_kg' => $cleanWeight,
            'quality_tier' => $tier,
        ];
    }
}
