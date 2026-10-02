<?php

namespace App\Domain\Feed\Models;

// Intentionally left blank for namespace reference

namespace App\Http\Controllers\Api\v1;

use App\Domain\Feed\Models\FeedBunkScore;
use App\Domain\Feed\Models\FeedConsumption;
use App\Domain\Feed\Models\FeedFormulation;
use App\Domain\Feed\Models\FeedItem;
use App\Domain\Feed\Models\SilageBunker;
use App\Domain\Feed\Models\TmrBatch;
use App\Domain\Feed\Services\DryMatterIntakeCalculator;
use App\Domain\Feed\Services\RationFormulationOptimizerService;
use App\Domain\Feed\Services\TmrMixerWagonBatchService;
use App\Domain\Organization\Models\Farm;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FeedController extends Controller
{
    public function index(): JsonResponse
    {
        $farm = Farm::first();
        if (! $farm) {
            return response()->json(['items' => [], 'recent_consumption' => []]);
        }

        $items = FeedItem::where('farm_id', $farm->id)->get();

        $recentConsumption = FeedConsumption::where('farm_id', $farm->id)
            ->with(['feedItem', 'pen'])
            ->latest('consumption_date')
            ->take(20)
            ->get();

        return response()->json([
            'items' => $items,
            'recent_consumption' => $recentConsumption,
        ]);
    }

    public function storeConsumption(Request $request): JsonResponse
    {
        $farm = Farm::first();
        if (! $farm) {
            return response()->json(['message' => 'Farm not configured'], 422);
        }

        $validated = $request->validate([
            'feed_item_id' => 'required|exists:feed_items,id',
            'pen_id' => 'nullable|exists:pens,id',
            'animal_id' => 'nullable|exists:animals,id',
            'consumption_date' => 'required|date',
            'quantity_consumed' => 'required|numeric|min:0.5',
            'notes' => 'nullable|string',
        ]);

        $feedItem = FeedItem::findOrFail($validated['feed_item_id']);

        if ($feedItem->current_stock < $validated['quantity_consumed']) {
            return response()->json([
                'message' => "Insufficient feed stock! Available: {$feedItem->current_stock} {$feedItem->unit}",
            ], 422);
        }

        // Deduct inventory
        $feedItem->decrement('current_stock', $validated['quantity_consumed']);

        $totalCost = $validated['quantity_consumed'] * $feedItem->cost_per_unit;

        $consumption = FeedConsumption::create(array_merge($validated, [
            'farm_id' => $farm->id,
            'unit_cost' => $feedItem->cost_per_unit,
            'total_cost' => $totalCost,
        ]));

        return response()->json([
            'message' => 'Feed consumption logged and inventory deducted',
            'data' => $consumption->load('feedItem'),
        ], 201);
    }

    public function formulations(): JsonResponse
    {
        $farm = Farm::first();
        if (! $farm) {
            return response()->json(['data' => []]);
        }

        $formulations = FeedFormulation::where('farm_id', $farm->id)
            ->withCount('tmrBatches')
            ->get();

        return response()->json(['data' => $formulations]);
    }

    public function storeFormulation(
        Request $request,
        RationFormulationOptimizerService $optimizer
    ): JsonResponse {
        $farm = Farm::first();
        if (! $farm) {
            return response()->json(['message' => 'Farm not configured'], 422);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:150',
            'code' => 'nullable|string|max:50',
            'species_type' => 'required|in:cattle,buffalo,goat,sheep',
            'target_stage' => 'required|string|max:100',
            'target_dmi_kg' => 'required|numeric|min:1',
            'ingredients' => 'required|array|min:1',
            'ingredients.*.feed_item_id' => 'required|exists:feed_items,id',
            'ingredients.*.inclusion_kg_as_fed' => 'required|numeric|min:0.1',
            'notes' => 'nullable|string',
        ]);

        $evaluation = $optimizer->evaluateRationNutrients($validated['ingredients']);

        $formulation = FeedFormulation::create(array_merge($validated, [
            'farm_id' => $farm->id,
            'calculated_cp_percent' => $evaluation['crude_protein_percent'],
            'calculated_nel_mcal' => $evaluation['nel_mcal_total'],
            'calculated_cost_per_head_day' => $evaluation['total_cost_per_head_day'],
            'is_active' => true,
        ]));

        return response()->json([
            'message' => 'Ration formulation created and nutritionally balanced',
            'nutritional_evaluation' => $evaluation,
            'data' => $formulation,
        ], 201);
    }

    public function tmrBatches(): JsonResponse
    {
        $farm = Farm::first();
        if (! $farm) {
            return response()->json(['data' => []]);
        }

        $batches = TmrBatch::where('farm_id', $farm->id)
            ->with(['formulation', 'pen', 'operator'])
            ->latest('batch_timestamp')
            ->paginate(20);

        return response()->json($batches);
    }

    public function storeTmrBatch(
        Request $request,
        TmrMixerWagonBatchService $tmrBatchService
    ): JsonResponse {
        $validated = $request->validate([
            'feed_formulation_id' => 'required|exists:feed_formulations,id',
            'pen_id' => 'nullable|exists:pens,id',
            'headcount' => 'required|integer|min:1',
            'actual_weight_kg' => 'required|numeric|min:1',
            'mixer_wagon_id' => 'nullable|string|max:100',
            'mixing_duration_minutes' => 'nullable|integer|min:1',
        ]);

        $formulation = FeedFormulation::findOrFail($validated['feed_formulation_id']);

        $batch = $tmrBatchService->recordCompletedBatch(
            formulation: $formulation,
            actualWeightKg: (float) $validated['actual_weight_kg'],
            headcount: (int) $validated['headcount'],
            penId: $validated['pen_id'] ?? null,
            mixerWagonId: $validated['mixer_wagon_id'] ?? 'Keenan MechFiber 360',
            mixingMinutes: (int) ($validated['mixing_duration_minutes'] ?? 15),
            operatorId: auth()->id()
        );

        return response()->json([
            'message' => 'TMR mixer wagon batch completed and feed stocks deducted',
            'data' => $batch->load(['formulation', 'pen']),
        ], 201);
    }

    public function bunkScores(): JsonResponse
    {
        $farm = Farm::first();
        if (! $farm) {
            return response()->json(['data' => []]);
        }

        $scores = FeedBunkScore::where('farm_id', $farm->id)
            ->with(['pen', 'assessor'])
            ->latest('assessed_at')
            ->take(25)
            ->get();

        return response()->json(['data' => $scores]);
    }

    public function storeBunkScore(Request $request): JsonResponse
    {
        $farm = Farm::first();
        if (! $farm) {
            return response()->json(['message' => 'Farm not configured'], 422);
        }

        $validated = $request->validate([
            'pen_id' => 'required|exists:pens,id',
            'assessed_at' => 'required|date',
            'score' => 'required|integer|between:0,4',
            'refusal_estimated_kg' => 'nullable|numeric|min:0',
            'adjustment_action' => 'required|in:increase_5_percent,increase_10_percent,maintain,decrease_5_percent,decrease_10_percent,clean_bunk',
            'notes' => 'nullable|string',
        ]);

        $score = FeedBunkScore::create(array_merge($validated, [
            'farm_id' => $farm->id,
            'assessed_by' => auth()->id(),
        ]));

        return response()->json([
            'message' => 'Feed bunk score recorded',
            'data' => $score->load('pen'),
        ], 201);
    }

    public function silageBunkers(): JsonResponse
    {
        $farm = Farm::first();
        if (! $farm) {
            return response()->json(['data' => []]);
        }

        $bunkers = SilageBunker::where('farm_id', $farm->id)->get();

        return response()->json(['data' => $bunkers]);
    }

    public function predictDmi(Request $request, DryMatterIntakeCalculator $dmiCalculator): JsonResponse
    {
        $validated = $request->validate([
            'species_type' => 'required|in:cattle,goat,sheep',
            'body_weight_kg' => 'required|numeric|min:20',
            'milk_yield_kg' => 'required|numeric|min:0',
            'fat_percentage' => 'nullable|numeric|between:1,10',
            'days_in_milk' => 'nullable|integer|min:1',
        ]);

        if ($validated['species_type'] === 'cattle') {
            $result = $dmiCalculator->predictDairyCattleDmi(
                bodyWeightKg: (float) $validated['body_weight_kg'],
                milkYieldKg: (float) $validated['milk_yield_kg'],
                fatPercentage: (float) ($validated['fat_percentage'] ?? 3.8),
                daysInMilk: (int) ($validated['days_in_milk'] ?? 90)
            );
        } else {
            $result = $dmiCalculator->predictSmallRuminantDmi(
                bodyWeightKg: (float) $validated['body_weight_kg'],
                milkYieldKg: (float) $validated['milk_yield_kg']
            );
        }

        return response()->json([
            'message' => 'DMI prediction computed via NRC models',
            'data' => $result,
        ]);
    }
}
