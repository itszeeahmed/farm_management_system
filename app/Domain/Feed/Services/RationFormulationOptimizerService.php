<?php

namespace App\Domain\Feed\Services;

use App\Domain\Feed\Models\FeedItem;

class RationFormulationOptimizerService
{
    /**
     * Evaluate nutrient composition and costing of a composite ration formulation.
     *
     * @param  array<array{feed_item_id: int, inclusion_kg_as_fed: float}>  $ingredients
     * @param  float  $milkProductionLiters  (for feed cost per liter calculation)
     * @return array{
     *     total_as_fed_kg: float,
     *     total_dry_matter_kg: float,
     *     crude_protein_percent: float,
     *     ndf_percent: float,
     *     adf_percent: float,
     *     nel_mcal_total: float,
     *     nel_mcal_per_kg_dm: float,
     *     total_cost_per_head_day: float,
     *     feed_cost_per_liter: float,
     *     ingredients_breakdown: array<int, array<string, mixed>>
     * }
     */
    public function evaluateRationNutrients(array $ingredients, float $milkProductionLiters = 20.0): array
    {
        $totalAsFedKg = 0.0;
        $totalDmKg = 0.0;
        $totalCpKg = 0.0;
        $totalNdfKg = 0.0;
        $totalAdfKg = 0.0;
        $totalNelMcal = 0.0;
        $totalCost = 0.0;
        $breakdown = [];

        $feedItemIds = array_column($ingredients, 'feed_item_id');
        $feedItems = FeedItem::whereIn('id', $feedItemIds)->get()->keyBy('id');

        foreach ($ingredients as $ing) {
            $feedItem = $feedItems->get($ing['feed_item_id']);
            if (! $feedItem) {
                continue;
            }

            $asFedKg = (float) $ing['inclusion_kg_as_fed'];
            $dmFraction = ((float) ($feedItem->dry_matter_percentage ?? 90.0)) / 100.0;
            $dmKg = $asFedKg * $dmFraction;

            $cpKg = $dmKg * (((float) ($feedItem->crude_protein_percentage ?? 0.0)) / 100.0);
            $ndfKg = $dmKg * (((float) ($feedItem->ndf_percentage ?? 0.0)) / 100.0);
            $adfKg = $dmKg * (((float) ($feedItem->adf_percentage ?? 0.0)) / 100.0);
            $nel = $dmKg * ((float) ($feedItem->nel_mcal_per_kg ?? 1.4));
            $cost = $asFedKg * ((float) ($feedItem->cost_per_unit ?? 0.0));

            $totalAsFedKg += $asFedKg;
            $totalDmKg += $dmKg;
            $totalCpKg += $cpKg;
            $totalNdfKg += $ndfKg;
            $totalAdfKg += $adfKg;
            $totalNelMcal += $nel;
            $totalCost += $cost;

            $breakdown[] = [
                'feed_item_id' => $feedItem->id,
                'name' => $feedItem->name,
                'category' => $feedItem->category,
                'as_fed_kg' => round($asFedKg, 2),
                'dry_matter_kg' => round($dmKg, 2),
                'cost_pkr' => round($cost, 2),
            ];
        }

        $safeDm = max(0.1, $totalDmKg);
        $cpPercent = round(($totalCpKg / $safeDm) * 100.0, 2);
        $ndfPercent = round(($totalNdfKg / $safeDm) * 100.0, 2);
        $adfPercent = round(($totalAdfKg / $safeDm) * 100.0, 2);
        $nelPerKgDm = round($totalNelMcal / $safeDm, 2);

        $costPerLiter = $milkProductionLiters > 0
            ? round($totalCost / $milkProductionLiters, 2)
            : 0.0;

        return [
            'total_as_fed_kg' => round($totalAsFedKg, 2),
            'total_dry_matter_kg' => round($totalDmKg, 2),
            'crude_protein_percent' => $cpPercent,
            'ndf_percent' => $ndfPercent,
            'adf_percent' => $adfPercent,
            'nel_mcal_total' => round($totalNelMcal, 2),
            'nel_mcal_per_kg_dm' => $nelPerKgDm,
            'total_cost_per_head_day' => round($totalCost, 2),
            'feed_cost_per_liter' => $costPerLiter,
            'ingredients_breakdown' => $breakdown,
        ];
    }
}
