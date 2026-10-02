<?php

namespace App\Domain\Animals\Services;

use App\Domain\Animals\Models\Animal;
use App\Domain\Animals\Models\AnimalWeight;
use Carbon\Carbon;
use InvalidArgumentException;

class AverageDailyGainCalculator
{
    /**
     * Calculate ADG and record a new weight for an animal.
     */
    public function recordWeight(
        Animal $animal,
        float $weightKg,
        Carbon|string $recordedAt,
        string $weighingMethod = 'scale',
        ?float $heartGirthCm = null,
        ?float $bodyLengthCm = null,
        ?int $recordedByUserId = null,
        ?string $notes = null
    ): AnimalWeight {
        $recordedAtDate = is_string($recordedAt) ? Carbon::parse($recordedAt) : $recordedAt;

        // Find the most recent weight before this recording date
        $previousRecord = AnimalWeight::where('animal_id', $animal->id)
            ->where('recorded_at', '<', $recordedAtDate->toDateString())
            ->orderByDesc('recorded_at')
            ->first();

        $previousWeight = null;
        $daysSince = null;
        $adg = null;

        if ($previousRecord) {
            $previousWeight = (float) $previousRecord->weight_kg;
            $prevDate = Carbon::parse($previousRecord->recorded_at);
            $daysSince = max(1, (int) $prevDate->diffInDays($recordedAtDate));
            $weightDiff = $weightKg - $previousWeight;
            $adg = round($weightDiff / $daysSince, 3);
        } elseif ($animal->birth_weight_kg && $animal->birth_date) {
            $birthDate = Carbon::parse($animal->birth_date);
            $daysSince = max(1, (int) $birthDate->diffInDays($recordedAtDate));
            $weightDiff = $weightKg - (float) $animal->birth_weight_kg;
            $previousWeight = (float) $animal->birth_weight_kg;
            $adg = round($weightDiff / $daysSince, 3);
        }

        $weightRecord = AnimalWeight::create([
            'animal_id' => $animal->id,
            'weight_kg' => $weightKg,
            'weighing_method' => $weighingMethod,
            'heart_girth_cm' => $heartGirthCm,
            'body_length_cm' => $bodyLengthCm,
            'previous_weight_kg' => $previousWeight,
            'days_since_previous' => $daysSince,
            'average_daily_gain_kg' => $adg,
            'recorded_at' => $recordedAtDate->toDateString(),
            'recorded_by' => $recordedByUserId,
            'notes' => $notes,
        ]);

        // Update animal's current_weight_kg if this is the newest record
        $latestRecord = AnimalWeight::where('animal_id', $animal->id)
            ->orderByDesc('recorded_at')
            ->first();

        if ($latestRecord && $latestRecord->id === $weightRecord->id) {
            $animal->update(['current_weight_kg' => $weightKg]);
        }

        return $weightRecord;
    }

    /**
     * Estimate body weight from heart girth and body length using Schaeffer's formula:
     * Weight (lbs) = (Girth (inches)^2 * Length (inches)) / 300
     * Converted to kg.
     */
    public function estimateWeightFromMorphometry(float $heartGirthCm, float $bodyLengthCm): float
    {
        if ($heartGirthCm <= 0 || $bodyLengthCm <= 0) {
            throw new InvalidArgumentException('Heart girth and body length must be positive.');
        }

        $girthInches = $heartGirthCm / 2.54;
        $lengthInches = $bodyLengthCm / 2.54;

        $weightLbs = (pow($girthInches, 2) * $lengthInches) / 300;
        $weightKg = $weightLbs * 0.45359237;

        return round($weightKg, 2);
    }
}
