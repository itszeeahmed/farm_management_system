<?php

namespace App\Http\Controllers\Api\v1;

use App\Domain\Climate\Models\ClimateReading;
use App\Domain\Organization\Models\Farm;
use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ClimateController extends Controller
{
    public function current(): JsonResponse
    {
        $farm = Farm::first();
        if (! $farm) {
            return response()->json(['reading' => null]);
        }

        $latest = ClimateReading::where('farm_id', $farm->id)
            ->latest('recorded_at')
            ->first();

        $history = ClimateReading::where('farm_id', $farm->id)
            ->latest('recorded_at')
            ->take(24)
            ->get();

        return response()->json([
            'current' => $latest,
            'history' => $history,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $farm = Farm::first();
        if (! $farm) {
            return response()->json(['message' => 'Farm not configured'], 422);
        }

        $validated = $request->validate([
            'temperature_c' => 'required|numeric|between:-10,60',
            'relative_humidity_percent' => 'required|numeric|between:0,100',
            'barn_id' => 'nullable|exists:barns,id',
            'sensor_device_id' => 'nullable|string',
            'mitigation_action_taken' => 'nullable|string',
        ]);

        $thi = ClimateReading::calculateTHI(
            (float) $validated['temperature_c'],
            (float) $validated['relative_humidity_percent']
        );
        $level = ClimateReading::determineHeatStressLevel($thi);

        $reading = ClimateReading::create([
            'farm_id' => $farm->id,
            'barn_id' => $validated['barn_id'] ?? null,
            'recorded_at' => Carbon::now(),
            'temperature_c' => $validated['temperature_c'],
            'relative_humidity_percent' => $validated['relative_humidity_percent'],
            'thi_index' => $thi,
            'heat_stress_level' => $level,
            'sensor_device_id' => $validated['sensor_device_id'] ?? null,
            'mitigation_action_taken' => $validated['mitigation_action_taken'] ?? null,
        ]);

        return response()->json([
            'message' => 'Climate reading recorded',
            'data' => $reading,
        ], 201);
    }
}
