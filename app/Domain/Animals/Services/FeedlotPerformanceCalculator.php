<?php

namespace App\Domain\Animals\Services;

use App\Domain\Animals\Models\FeedlotRecord;

class FeedlotPerformanceCalculator
{
    /**
     * Compute and update performance metrics on a FeedlotRecord.
     *
     * @return array{
     *     average_daily_gain_kg: float,
     *     total_gain_kg: float,
     *     feed_conversion_ratio: float,
     *     cost_per_kg_gain: float,
     *     estimated_days_to_slaughter: int
     * }
     */
    public function computeMetrics(
        FeedlotRecord $record,
        float $newCurrentWeightKg,
        float $feedConsumedKgDm = 0.0,
        float $rationCost = 0.0
    ): array {
        $intakeWeight = (float) $record->intake_weight_kg;
        $targetWeight = (float) $record->target_slaughter_weight_kg;
        $daysOnFeed = max(1, $record->days_on_feed + 1);

        $totalGain = round(max(0.0, $newCurrentWeightKg - $intakeWeight), 2);
        $adg = round($totalGain / $daysOnFeed, 3);

        $totalFeedDm = (float) $record->total_feed_consumed_kg_dm + $feedConsumedKgDm;
        $fcr = $totalGain > 0.0 ? round($totalFeedDm / $totalGain, 2) : 0.0;

        $totalCost = ((float) $record->daily_ration_cost * ($daysOnFeed - 1)) + $rationCost;
        $costPerKgGain = $totalGain > 0.0 ? round($totalCost / $totalGain, 2) : 0.0;

        // Estimated days remaining to reach target slaughter weight
        $remainingGain = max(0.0, $targetWeight - $newCurrentWeightKg);
        $estimatedDaysRemaining = ($adg > 0.0) ? (int) ceil($remainingGain / $adg) : 90;

        $record->update([
            'current_weight_kg' => $newCurrentWeightKg,
            'days_on_feed' => $daysOnFeed,
            'total_gain_kg' => $totalGain,
            'average_daily_gain_kg' => $adg,
            'total_feed_consumed_kg_dm' => $totalFeedDm,
            'feed_conversion_ratio' => $fcr,
            'daily_ration_cost' => $rationCost > 0 ? $rationCost : $record->daily_ration_cost,
            'cost_per_kg_gain' => $costPerKgGain,
            'status' => ($newCurrentWeightKg >= $targetWeight) ? 'ready_for_slaughter' : 'active',
        ]);

        return [
            'average_daily_gain_kg' => $adg,
            'total_gain_kg' => $totalGain,
            'feed_conversion_ratio' => $fcr,
            'cost_per_kg_gain' => $costPerKgGain,
            'estimated_days_to_slaughter' => $estimatedDaysRemaining,
        ];
    }
}
