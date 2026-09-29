<?php

namespace App\Http\Controllers\Api\v1;

use App\Domain\Breeding\Models\BreedingEvent;
use App\Domain\Breeding\Models\Pregnancy;
use App\Domain\Organization\Models\Farm;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BreedingController extends Controller
{
    public function index(): JsonResponse
    {
        $farm = Farm::first();
        if (! $farm) {
            return response()->json(['pregnancies' => [], 'events' => []]);
        }

        $pregnancies = Pregnancy::where('farm_id', $farm->id)
            ->with(['animal.species', 'animal.breed'])
            ->whereIn('status', ['confirmed_pregnant', 'doubtful'])
            ->orderBy('expected_delivery_date')
            ->get();

        $recentEvents = BreedingEvent::where('farm_id', $farm->id)
            ->with(['animal', 'sire'])
            ->latest('insemination_datetime')
            ->take(15)
            ->get();

        return response()->json([
            'active_pregnancies' => $pregnancies,
            'recent_breeding_events' => $recentEvents,
        ]);
    }

    public function storeEvent(Request $request): JsonResponse
    {
        $farm = Farm::first();
        if (! $farm) {
            return response()->json(['message' => 'Farm not configured'], 422);
        }

        $validated = $request->validate([
            'animal_id' => 'required|exists:animals,id',
            'method' => 'required|in:artificial_insemination,natural_service,embryo_transfer',
            'insemination_datetime' => 'required|date',
            'semen_straw_code' => 'nullable|string',
            'sire_breed_code' => 'nullable|string',
            'technician_name' => 'nullable|string',
            'cost' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        $event = BreedingEvent::create(array_merge($validated, [
            'farm_id' => $farm->id,
            'status' => 'pending_check',
        ]));

        return response()->json([
            'message' => 'Breeding event logged successfully',
            'data' => $event->load('animal'),
        ], 201);
    }
}
