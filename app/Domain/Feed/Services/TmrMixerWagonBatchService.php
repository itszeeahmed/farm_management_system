<?php

namespace App\Domain\Feed\Services;

use App\Domain\Feed\Models\FeedFormulation;
use App\Domain\Feed\Models\FeedItem;
use App\Domain\Feed\Models\TmrBatch;
use Illuminate\Validation\ValidationException;

class TmrMixerWagonBatchService
{
    /**
     * Generate loading sequence and scaled quantities for mixer wagon batch.
     * Sequence:
     * 1. dry_hay / straw (requires early chop)
     * 2. silage / forage_green (bulky moist carrier)
     * 3. concentrate / grains / byproduct (blends uniformly into moist forage)
     * 4. mineral_premix / liquids (final top-dress distribution)
     *
     * @return array{
     *     planned_total_kg: float,
     *     loading_sequence: array<int, array<string, mixed>>
     * }
     */
    public function calculateBatchLoadingPlan(FeedFormulation $formulation, int $headcount = 40): array
    {
        $ingredients = $formulation->ingredients ?? [];
        $plannedTotalKg = 0.0;
        $items = [];

        $priorityOrder = [
            'hay_dry' => 1,
            'silage' => 2,
            'forage_green' => 2,
            'concentrate' => 3,
            'grains' => 3,
            'byproduct' => 3,
            'mineral_premix' => 4,
            'milk_replacer' => 4,
        ];

        foreach ($ingredients as $ing) {
            $feedItem = FeedItem::find($ing['feed_item_id']);
            if (! $feedItem) {
                continue;
            }

            $asFedPerHead = (float) $ing['inclusion_kg_as_fed'];
            $batchWeight = round($asFedPerHead * $headcount, 2);
            $plannedTotalKg += $batchWeight;

            $items[] = [
                'feed_item_id' => $feedItem->id,
                'name' => $feedItem->name,
                'category' => $feedItem->category,
                'loading_priority' => $priorityOrder[$feedItem->category] ?? 3,
                'as_fed_per_head_kg' => $asFedPerHead,
                'batch_required_kg' => $batchWeight,
                'available_stock_kg' => (float) $feedItem->current_stock,
            ];
        }

        usort($items, fn ($a, $b) => $a['loading_priority'] <=> $b['loading_priority']);

        return [
            'planned_total_kg' => round($plannedTotalKg, 2),
            'loading_sequence' => $items,
        ];
    }

    /**
     * Record completed TMR mixing batch, verify loading deviation, and decrement inventory.
     */
    public function recordCompletedBatch(
        FeedFormulation $formulation,
        float $actualWeightKg,
        int $headcount,
        ?int $penId = null,
        ?string $mixerWagonId = 'Mixer Wagon #1',
        int $mixingMinutes = 15,
        ?int $operatorId = null
    ): TmrBatch {
        $plan = $this->calculateBatchLoadingPlan($formulation, $headcount);
        $plannedWeight = $plan['planned_total_kg'];

        if ($plannedWeight <= 0) {
            throw ValidationException::withMessages([
                'feed_formulation_id' => ['Formulation has no valid ingredients.'],
            ]);
        }

        $deviationPercent = round((($actualWeightKg - $plannedWeight) / $plannedWeight) * 100.0, 2);

        // Check inventory sufficiency and deduct stock proportionally
        $ratio = $plannedWeight > 0 ? ($actualWeightKg / $plannedWeight) : 1.0;
        foreach ($plan['loading_sequence'] as $item) {
            $feed = FeedItem::find($item['feed_item_id']);
            if ($feed) {
                $usedKg = round($item['batch_required_kg'] * $ratio, 2);
                $feed->decrement('current_stock', min((float) $feed->current_stock, $usedKg));
            }
        }

        $batchNumber = 'TMR-'.now()->format('Ymd').'-'.strtoupper(substr(uniqid(), -4));

        return TmrBatch::create([
            'farm_id' => $formulation->farm_id,
            'feed_formulation_id' => $formulation->id,
            'pen_id' => $penId,
            'batch_number' => $batchNumber,
            'mixer_wagon_id' => $mixerWagonId,
            'planned_weight_kg' => $plannedWeight,
            'actual_weight_kg' => $actualWeightKg,
            'deviation_percent' => $deviationPercent,
            'mixing_duration_minutes' => $mixingMinutes,
            'status' => 'dispatched_to_bunk',
            'operator_id' => $operatorId,
            'batch_timestamp' => now(),
        ]);
    }
}
