<?php

namespace App\Http\Controllers\Api\v1;

use App\Domain\Animals\Models\Animal;
use App\Domain\Animals\Models\FeedlotRecord;
use App\Domain\Animals\Models\FleeceRecord;
use App\Domain\Animals\Models\SlaughterRecord;
use App\Domain\Animals\Models\SpecializedSpeciesAttribute;
use App\Domain\Animals\Services\FeedlotPerformanceCalculator;
use App\Domain\Animals\Services\FleeceGradingClassifier;
use App\Domain\Animals\Services\SlaughterClearanceGuard;
use App\Domain\Organization\Models\Farm;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MeatAndSpeciesController extends Controller
{
    public function feedlotRecords(Request $request): JsonResponse
    {
        $farm = Farm::first();
        if (! $farm) {
            return response()->json(['data' => []]);
        }

        $records = FeedlotRecord::where('farm_id', $farm->id)
            ->with(['animal', 'pen'])
            ->latest()
            ->paginate($request->integer('per_page', 20));

        return response()->json($records);
    }

    public function recordFeedlotIntake(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'animal_id' => 'required|exists:animals,id',
            'pen_id' => 'nullable|exists:pens,id',
            'intake_date' => 'required|date',
            'intake_weight_kg' => 'required|numeric|min:1',
            'target_slaughter_weight_kg' => 'nullable|numeric|min:1',
            'daily_ration_cost' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        $animal = Animal::findOrFail($validated['animal_id']);

        $record = FeedlotRecord::create([
            'farm_id' => $animal->farm_id,
            'animal_id' => $animal->id,
            'pen_id' => $validated['pen_id'] ?? $animal->pen_id,
            'intake_date' => $validated['intake_date'],
            'intake_weight_kg' => $validated['intake_weight_kg'],
            'current_weight_kg' => $validated['intake_weight_kg'],
            'target_slaughter_weight_kg' => $validated['target_slaughter_weight_kg'] ?? 550.00,
            'days_on_feed' => 0,
            'average_daily_gain_kg' => 0.000,
            'total_gain_kg' => 0.00,
            'total_feed_consumed_kg_dm' => 0.00,
            'feed_conversion_ratio' => 0.00,
            'daily_ration_cost' => $validated['daily_ration_cost'] ?? 0.00,
            'cost_per_kg_gain' => 0.00,
            'status' => 'active',
            'notes' => $validated['notes'] ?? null,
        ]);

        return response()->json([
            'message' => 'Feedlot intake recorded successfully',
            'data' => $record->load(['animal', 'pen']),
        ], 201);
    }

    public function updateFeedlotGain(
        Request $request,
        int $id,
        FeedlotPerformanceCalculator $calculator
    ): JsonResponse {
        $validated = $request->validate([
            'current_weight_kg' => 'required|numeric|min:1',
            'feed_consumed_kg_dm' => 'nullable|numeric|min:0',
            'ration_cost' => 'nullable|numeric|min:0',
        ]);

        $record = FeedlotRecord::findOrFail($id);

        $metrics = $calculator->computeMetrics(
            record: $record,
            newCurrentWeightKg: (float) $validated['current_weight_kg'],
            feedConsumedKgDm: (float) ($validated['feed_consumed_kg_dm'] ?? 0.0),
            rationCost: (float) ($validated['ration_cost'] ?? 0.0)
        );

        return response()->json([
            'message' => 'Feedlot weight and gain metrics updated',
            'data' => [
                'record' => $record->fresh(['animal', 'pen']),
                'metrics' => $metrics,
            ],
        ]);
    }

    public function checkSlaughterClearance(int $animalId, SlaughterClearanceGuard $guard): JsonResponse
    {
        $animal = Animal::findOrFail($animalId);
        $check = $guard->checkClearance($animal);

        return response()->json([
            'data' => array_merge(['animal_id' => $animal->id, 'tag_number' => $animal->tag_number], $check),
        ]);
    }

    public function recordSlaughter(Request $request, SlaughterClearanceGuard $guard): JsonResponse
    {
        $validated = $request->validate([
            'animal_id' => 'required|exists:animals,id',
            'slaughterhouse_name' => 'required|string|max:150',
            'slaughter_date' => 'required|date',
            'live_weight_kg' => 'required|numeric|min:1',
            'hot_carcass_weight_kg' => 'required|numeric|min:1',
            'cold_carcass_weight_kg' => 'nullable|numeric|min:1',
            'conformation_grade' => 'nullable|string|max:50',
            'fat_score' => 'nullable|integer|between:1,5',
            'carcass_bar_code' => 'nullable|string|max:100',
            'technician_name' => 'nullable|string|max:100',
            'notes' => 'nullable|string',
        ]);

        $animal = Animal::findOrFail($validated['animal_id']);

        // Enforce meat withdrawal safety
        $guard->enforceSlaughterClearance($animal);

        $liveWeight = (float) $validated['live_weight_kg'];
        $hcw = (float) $validated['hot_carcass_weight_kg'];
        $dressingPercent = round(($hcw / max(1.0, $liveWeight)) * 100.0, 2);

        $slaughter = SlaughterRecord::create([
            'farm_id' => $animal->farm_id,
            'animal_id' => $animal->id,
            'slaughterhouse_name' => $validated['slaughterhouse_name'],
            'slaughter_date' => $validated['slaughter_date'],
            'live_weight_kg' => $liveWeight,
            'hot_carcass_weight_kg' => $hcw,
            'cold_carcass_weight_kg' => $validated['cold_carcass_weight_kg'] ?? null,
            'dressing_percentage' => $dressingPercent,
            'conformation_grade' => $validated['conformation_grade'] ?? 'prime',
            'fat_score' => $validated['fat_score'] ?? 3,
            'meat_withdrawal_cleared' => true,
            'carcass_bar_code' => $validated['carcass_bar_code'] ?? null,
            'technician_name' => $validated['technician_name'] ?? null,
            'notes' => $validated['notes'] ?? null,
        ]);

        // Transition animal to culled/slaughtered
        $animal->update(['status' => 'culled', 'lifecycle_stage' => 'culled']);

        return response()->json([
            'message' => 'Slaughter and carcass grading recorded with verified meat withdrawal clearance',
            'data' => $slaughter,
        ], 201);
    }

    public function fleeceRecords(Request $request): JsonResponse
    {
        $farm = Farm::first();
        if (! $farm) {
            return response()->json(['data' => []]);
        }

        $records = FleeceRecord::where('farm_id', $farm->id)
            ->with('animal')
            ->latest()
            ->paginate($request->integer('per_page', 20));

        return response()->json($records);
    }

    public function recordFleeceShearing(
        Request $request,
        FleeceGradingClassifier $classifier
    ): JsonResponse {
        $validated = $request->validate([
            'animal_id' => 'required|exists:animals,id',
            'shearing_date' => 'required|date',
            'fleece_type' => 'nullable|string|in:wool,mohair,cashmere,camel_hair',
            'grease_fleece_weight_kg' => 'required|numeric|min:0.1',
            'micron_grade' => 'required|numeric|min:10|max:50',
            'dirt_yield_deduction_percent' => 'nullable|numeric|between:0,80',
            'staple_length_mm' => 'nullable|numeric|min:1',
            'shearer_name' => 'nullable|string|max:100',
            'notes' => 'nullable|string',
        ]);

        $animal = Animal::findOrFail($validated['animal_id']);

        $grading = $classifier->gradeFleece(
            greaseWeightKg: (float) $validated['grease_fleece_weight_kg'],
            micronGrade: (float) $validated['micron_grade'],
            dirtYieldDeductionPercent: (float) ($validated['dirt_yield_deduction_percent'] ?? 35.0)
        );

        $fleece = FleeceRecord::create([
            'farm_id' => $animal->farm_id,
            'animal_id' => $animal->id,
            'shearing_date' => $validated['shearing_date'],
            'fleece_type' => $validated['fleece_type'] ?? 'wool',
            'grease_fleece_weight_kg' => $validated['grease_fleece_weight_kg'],
            'clean_fleece_weight_kg' => $grading['clean_fleece_weight_kg'],
            'clean_yield_percentage' => $grading['clean_yield_percentage'],
            'micron_grade' => $validated['micron_grade'],
            'quality_tier' => $grading['quality_tier'],
            'staple_length_mm' => $validated['staple_length_mm'] ?? null,
            'shearer_name' => $validated['shearer_name'] ?? null,
            'notes' => $validated['notes'] ?? null,
        ]);

        return response()->json([
            'message' => 'Fleece shearing and micron grading recorded',
            'data' => $fleece->load('animal'),
        ], 201);
    }

    public function specializedSpecies(Request $request): JsonResponse
    {
        $attributes = SpecializedSpeciesAttribute::with('animal')->paginate($request->integer('per_page', 20));

        return response()->json($attributes);
    }

    public function updateSpecializedSpecies(Request $request, int $animalId): JsonResponse
    {
        $animal = Animal::findOrFail($animalId);

        $validated = $request->validate([
            'species_type' => 'required|string|in:camel,buffalo,sheep,goat',
            'hump_condition_score' => 'nullable|numeric|between:1,5',
            'draft_work_type' => 'nullable|string|max:50',
            'racing_eligibility_status' => 'nullable|boolean',
            'veterinary_passport_number' => 'nullable|string|max:100',
            'microchip_transponder_rfid' => 'nullable|string|max:100',
        ]);

        $attribute = SpecializedSpeciesAttribute::updateOrCreate(
            ['animal_id' => $animal->id],
            $validated
        );

        return response()->json([
            'message' => 'Specialized species attributes saved',
            'data' => $attribute->load('animal'),
        ]);
    }
}
