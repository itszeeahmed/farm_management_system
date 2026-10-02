<?php

namespace App\Domain\Finance\Services;

use App\Domain\Feed\Models\FeedConsumption;
use App\Domain\Health\Models\Treatment;
use App\Domain\Milk\Models\MilkRecord;
use App\Domain\Organization\Models\Farm;
use Carbon\Carbon;

class CostPerLiterEngine
{
    /**
     * Compute True Net Cost-Per-Liter (CPL) for a farm across a date range.
     *
     * @return array{
     *     total_milk_liters: float,
     *     feed_cost_total: float,
     *     feed_cost_per_liter: float,
     *     health_cost_total: float,
     *     health_cost_per_liter: float,
     *     labor_energy_overhead_total: float,
     *     labor_energy_overhead_per_liter: float,
     *     total_cost_per_liter: float,
     *     average_selling_price_per_liter: float,
     *     net_margin_per_liter: float
     * }
     */
    public function computeCostPerLiter(
        Farm $farm,
        ?string $startDate = null,
        ?string $endDate = null,
        float $laborEnergyOverheadAmount = 15000.00,
        float $benchmarkSellingPrice = 220.00
    ): array {
        $start = $startDate ? Carbon::parse($startDate)->startOfDay() : Carbon::now()->subDays(30)->startOfDay();
        $end = $endDate ? Carbon::parse($endDate)->endOfDay() : Carbon::now()->endOfDay();

        // 1. Total Marketable Milk Volume
        $totalMilkLiters = (float) MilkRecord::where('farm_id', $farm->id)
            ->whereBetween('recorded_date', [$start->toDateString(), $end->toDateString()])
            ->sum('yield_liters');

        $volume = max(1.0, $totalMilkLiters);

        // 2. Feed Costs
        $feedCostTotal = (float) FeedConsumption::where('farm_id', $farm->id)
            ->whereBetween('consumption_date', [$start->toDateString(), $end->toDateString()])
            ->sum('total_cost');

        // Fallback default if period had no explicit consumption logs
        if ($feedCostTotal <= 0.0) {
            $feedCostTotal = 85.0 * $volume; // Baseline ~85 PKR/L feed cost
        }

        // 3. Veterinary & Medicine Costs
        $healthCostTotal = (float) Treatment::where('farm_id', $farm->id)
            ->whereBetween('administered_at', [$start, $end])
            ->sum('cost');

        if ($healthCostTotal <= 0.0) {
            $healthCostTotal = 6.50 * $volume; // Baseline ~6.50 PKR/L health cost
        }

        // 4. Overheads, Labor & Energy
        $overheadTotal = $laborEnergyOverheadAmount;

        // 5. Per-Liter Breakdowns
        $feedCpl = round($feedCostTotal / $volume, 2);
        $healthCpl = round($healthCostTotal / $volume, 2);
        $overheadCpl = round($overheadTotal / $volume, 2);
        $totalCpl = round($feedCpl + $healthCpl + $overheadCpl, 2);

        $marginPerLiter = round($benchmarkSellingPrice - $totalCpl, 2);

        return [
            'total_milk_liters' => round($totalMilkLiters, 2),
            'feed_cost_total' => round($feedCostTotal, 2),
            'feed_cost_per_liter' => $feedCpl,
            'health_cost_total' => round($healthCostTotal, 2),
            'health_cost_per_liter' => $healthCpl,
            'labor_energy_overhead_total' => round($overheadTotal, 2),
            'labor_energy_overhead_per_liter' => $overheadCpl,
            'total_cost_per_liter' => $totalCpl,
            'average_selling_price_per_liter' => $benchmarkSellingPrice,
            'net_margin_per_liter' => $marginPerLiter,
        ];
    }
}
