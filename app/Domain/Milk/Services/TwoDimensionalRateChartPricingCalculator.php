<?php

namespace App\Domain\Milk\Services;

use App\Domain\Milk\Models\MilkRateChart;

class TwoDimensionalRateChartPricingCalculator
{
    /**
     * Compute Solids-Not-Fat (SNF) from Corrected Lactometer Reading (CLR at 20C) and Fat %.
     * Richmond Formula: SNF = (CLR / 4) + (0.25 * Fat) + 0.35 (for cow)
     * For buffalo: SNF = (CLR / 4) + (0.20 * Fat) + 0.60
     */
    public function calculateSnfFromLactometer(float $clr, float $fat, string $speciesType = 'cow'): float
    {
        if ($speciesType === 'buffalo') {
            $snf = ($clr / 4.0) + (0.20 * $fat) + 0.60;
        } else {
            $snf = ($clr / 4.0) + (0.25 * $fat) + 0.35;
        }

        return round(max(0.0, $snf), 2);
    }

    /**
     * Calculate price per liter and evaluate adulteration.
     *
     * @param  array<string, mixed>  $adulterationTests
     * @return array{
     *     quality_accepted: bool,
     *     rejection_reason: string|null,
     *     price_per_liter: float,
     *     gross_amount: float,
     *     deductions_amount: float,
     *     net_payable_amount: float
     * }
     */
    public function calculateIntakePrice(
        MilkRateChart $chart,
        float $volumeLiters,
        float $fatPercentage,
        float $snfPercentage,
        array $adulterationTests = []
    ): array {
        // 1. Check Adulteration Battery
        $adulterants = [
            'alcohol_test_result' => 'Curdled/Positive in 68% Alcohol Test',
            'adulteration_starch' => 'Starch Adulterant Detected',
            'adulteration_urea' => 'Urea Adulterant Detected',
            'adulteration_detergent' => 'Detergent Contamination Detected',
            'adulteration_formalin' => 'Formalin Chemical Preservative Detected',
            'adulteration_hydrogen_peroxide' => 'Hydrogen Peroxide (H2O2) Detected',
        ];

        foreach ($adulterants as $key => $reason) {
            if ($key === 'alcohol_test_result') {
                if (isset($adulterationTests[$key]) && $adulterationTests[$key] === 'positive') {
                    return $this->rejectionResponse($volumeLiters, $reason);
                }
            } elseif (! empty($adulterationTests[$key])) {
                return $this->rejectionResponse($volumeLiters, $reason);
            }
        }

        $addedWater = (float) ($adulterationTests['added_water_percentage'] ?? 0.0);
        if ($addedWater > 5.0) {
            return $this->rejectionResponse($volumeLiters, "Added water ({$addedWater}%) exceeds allowable threshold.");
        }

        // 2. Minimum acceptance thresholds
        if ($fatPercentage < (float) $chart->min_fat_acceptance) {
            return $this->rejectionResponse($volumeLiters, "Fat ({$fatPercentage}%) below minimum acceptance threshold ({$chart->min_fat_acceptance}%).");
        }

        if ($snfPercentage < (float) $chart->min_snf_acceptance) {
            return $this->rejectionResponse($volumeLiters, "SNF ({$snfPercentage}%) below minimum acceptance threshold ({$chart->min_snf_acceptance}%).");
        }

        // 3. 2D Rate Calculation:
        // Price = Base + (Fat - StdFat) * FatRate + (SNF - StdSNF) * SNFRate + Premium
        $base = (float) $chart->base_price_per_liter;
        $fatDelta = $fatPercentage - (float) $chart->standard_fat_percentage;
        $snfDelta = $snfPercentage - (float) $chart->standard_snf_percentage;

        $fatAdj = $fatDelta * (float) $chart->fat_rate_per_unit;
        $snfAdj = $snfDelta * (float) $chart->snf_rate_per_unit;

        $calculatedPrice = $base + $fatAdj + $snfAdj;

        if ((float) $chart->premium_incentive_percent > 0) {
            $premium = $calculatedPrice * ((float) $chart->premium_incentive_percent / 100.0);
            $calculatedPrice += $premium;
        }

        $pricePerLiter = round(max(0.0, $calculatedPrice), 2);
        $grossAmount = round($volumeLiters * $pricePerLiter, 2);

        // Deductions (e.g. transport or chilling cess if applicable)
        $deductions = 0.0;
        $netPayable = round($grossAmount - $deductions, 2);

        return [
            'quality_accepted' => true,
            'rejection_reason' => null,
            'price_per_liter' => $pricePerLiter,
            'gross_amount' => $grossAmount,
            'deductions_amount' => $deductions,
            'net_payable_amount' => $netPayable,
        ];
    }

    private function rejectionResponse(float $volumeLiters, string $reason): array
    {
        return [
            'quality_accepted' => false,
            'rejection_reason' => $reason,
            'price_per_liter' => 0.0,
            'gross_amount' => 0.0,
            'deductions_amount' => 0.0,
            'net_payable_amount' => 0.0,
        ];
    }
}
