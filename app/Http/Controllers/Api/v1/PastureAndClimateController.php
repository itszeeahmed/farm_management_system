<?php

namespace App\Http\Controllers\Api\v1;

use App\Domain\Animals\Models\AnimalGroup;
use App\Domain\Climate\Models\ClimateReading;
use App\Domain\Climate\Models\GrazingLog;
use App\Domain\Climate\Models\PasturePlot;
use App\Domain\Climate\Services\NrcThiCalculator;
use App\Domain\Climate\Services\PastureRotationalGrazingService;
use App\Domain\Organization\Models\Farm;
use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PastureAndClimateController extends Controller
{
    public function paddocks(Request $request): JsonResponse
    {
        $farm = Farm::first();
        if (! $farm) {
            return response()->json(['data' => []]);
        }

        $plots = PasturePlot::where('farm_id', $farm->id)
            ->with(['grazingLogs' => fn ($q) => $q->latest()->limit(5)])
            ->get();

        return response()->json(['data' => $plots]);
    }

    public function createPaddock(Request $request): JsonResponse
    {
        $farm = Farm::firstOrFail();

        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'code' => 'required|string|max:50',
            'area_hectares' => 'required|numeric|min:0.1',
            'forage_type' => 'nullable|string|max:100',
            'soil_ph' => 'nullable|numeric|between:4,9',
            'target_rest_days' => 'nullable|integer|min:7',
            'current_biomass_kg_dm_per_ha' => 'nullable|numeric|min:0',
        ]);

        $plot = PasturePlot::create(array_merge($validated, [
            'farm_id' => $farm->id,
            'status' => 'resting',
        ]));

        return response()->json([
            'message' => 'Pasture paddock created',
            'data' => $plot,
        ], 201);
    }

    public function enterPaddock(
        Request $request,
        PastureRotationalGrazingService $service
    ): JsonResponse {
        $validated = $request->validate([
            'pasture_plot_id' => 'required|exists:pasture_plots,id',
            'animal_group_id' => 'nullable|exists:animal_groups,id',
            'stocking_density_heads' => 'required|integer|min:1',
            'pre_graze_height_cm' => 'required|numeric|min:5',
            'entry_date' => 'required|date',
        ]);

        $plot = PasturePlot::findOrFail($validated['pasture_plot_id']);
        $group = ! empty($validated['animal_group_id']) ? AnimalGroup::find($validated['animal_group_id']) : null;

        $log = $service->enterPaddock(
            plot: $plot,
            group: $group,
            headCount: (int) $validated['stocking_density_heads'],
            preGrazeHeightCm: (float) $validated['pre_graze_height_cm'],
            entryDate: $validated['entry_date']
        );

        return response()->json([
            'message' => "Herd entered paddock '{$plot->name}'. Status changed to grazing.",
            'data' => $log->load(['pasturePlot', 'animalGroup']),
        ], 201);
    }

    public function exitPaddock(
        Request $request,
        int $grazingLogId,
        PastureRotationalGrazingService $service
    ): JsonResponse {
        $validated = $request->validate([
            'post_graze_residual_height_cm' => 'required|numeric|min:1',
            'exit_date' => 'required|date',
        ]);

        $log = GrazingLog::findOrFail($grazingLogId);

        $updatedLog = $service->exitPaddock(
            log: $log,
            postGrazeResidualHeightCm: (float) $validated['post_graze_residual_height_cm'],
            exitDate: $validated['exit_date']
        );

        return response()->json([
            'message' => 'Grazing cycle completed for paddock. Dry matter utilized recorded.',
            'data' => $updatedLog->load(['pasturePlot', 'animalGroup']),
        ]);
    }

    public function recordClimateReading(
        Request $request,
        NrcThiCalculator $thiCalculator
    ): JsonResponse {
        $farm = Farm::firstOrFail();

        $validated = $request->validate([
            'barn_id' => 'nullable|exists:barns,id',
            'zone_id' => 'nullable|exists:farm_zones,id',
            'recorded_at' => 'nullable|date',
            'temperature_c' => 'required|numeric|between:-10,60',
            'relative_humidity_percent' => 'required|numeric|between:0,100',
            'air_velocity_m_s' => 'nullable|numeric|min:0',
            'solar_radiation_w_m2' => 'nullable|numeric|min:0',
            'species_type' => 'nullable|string|in:cattle,goat,sheep',
            'sensor_device_id' => 'nullable|string|max:50',
        ]);

        $temp = (float) $validated['temperature_c'];
        $rh = (float) $validated['relative_humidity_percent'];
        $species = $validated['species_type'] ?? 'cattle';

        $assessment = $thiCalculator->assessHeatStress($temp, $rh, $species);

        $reading = ClimateReading::create([
            'farm_id' => $farm->id,
            'barn_id' => $validated['barn_id'] ?? null,
            'zone_id' => $validated['zone_id'] ?? null,
            'recorded_at' => $validated['recorded_at'] ?? Carbon::now(),
            'temperature_c' => $temp,
            'relative_humidity_percent' => $rh,
            'air_velocity_m_s' => $validated['air_velocity_m_s'] ?? null,
            'solar_radiation_w_m2' => $validated['solar_radiation_w_m2'] ?? null,
            'thi_index' => $assessment['thi_index'],
            'heat_stress_level' => $assessment['heat_stress_level'],
            'cooling_actuator_activated' => $assessment['cooling_actuator_activated'],
            'sensor_device_id' => $validated['sensor_device_id'] ?? 'BARN-IOT-TH01',
            'mitigation_action_taken' => $assessment['recommended_action'],
        ]);

        return response()->json([
            'message' => 'Climate sensor reading ingested and evaluated',
            'data' => [
                'reading' => $reading,
                'assessment' => $assessment,
            ],
        ], 201);
    }

    public function climateHistory(Request $request): JsonResponse
    {
        $farm = Farm::first();
        if (! $farm) {
            return response()->json(['data' => []]);
        }

        $readings = ClimateReading::where('farm_id', $farm->id)
            ->with(['barn', 'zone'])
            ->latest('recorded_at')
            ->paginate($request->integer('per_page', 25));

        return response()->json($readings);
    }
}
