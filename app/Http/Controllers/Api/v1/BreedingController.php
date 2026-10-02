<?php

namespace App\Http\Controllers\Api\v1;

use App\Domain\Animals\Models\Animal;
use App\Domain\Breeding\Models\BreedingEvent;
use App\Domain\Breeding\Models\PostpartumCheck;
use App\Domain\Breeding\Models\Pregnancy;
use App\Domain\Breeding\Models\SemenStrawInventory;
use App\Domain\Breeding\Services\BreedingReproductiveAnalyticsService;
use App\Domain\Breeding\Services\CalvingLifecycleEngine;
use App\Domain\Organization\Models\Farm;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

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
            ->with(['animal', 'sire', 'semenStraw'])
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
            'sire_id' => 'nullable|exists:animals,id',
            'semen_straw_inventory_id' => 'nullable|exists:semen_straw_inventories,id',
            'method' => 'required|in:artificial_insemination,natural_service,embryo_transfer',
            'insemination_datetime' => 'required|date',
            'semen_straw_code' => 'nullable|string',
            'sire_breed_code' => 'nullable|string',
            'technician_name' => 'nullable|string',
            'cost' => 'nullable|numeric|min:0',
            'cycle_number' => 'nullable|integer|min:1',
            'heat_intensity_score' => 'nullable|integer|between:1,5',
            'notes' => 'nullable|string',
        ]);

        // Deduct semen straw if AI used and inventory selected
        if (! empty($validated['semen_straw_inventory_id'])) {
            $straw = SemenStrawInventory::findOrFail($validated['semen_straw_inventory_id']);
            if ($straw->straws_in_stock < 1) {
                throw ValidationException::withMessages([
                    'semen_straw_inventory_id' => ["Semen straw {$straw->straw_code} is out of stock in tank!"],
                ]);
            }
            $straw->decrement('straws_in_stock', 1);
            $validated['semen_straw_code'] = $straw->straw_code;
            $validated['sire_breed_code'] = $straw->sire_breed;
        }

        $event = BreedingEvent::create(array_merge($validated, [
            'farm_id' => $farm->id,
            'status' => 'pending_check',
        ]));

        return response()->json([
            'message' => 'Breeding event logged and semen inventory updated',
            'data' => $event->load(['animal', 'semenStraw']),
        ], 201);
    }

    public function semenInventory(): JsonResponse
    {
        $farm = Farm::first();
        if (! $farm) {
            return response()->json(['data' => []]);
        }

        $straws = SemenStrawInventory::where('farm_id', $farm->id)->get();

        return response()->json(['data' => $straws]);
    }

    public function storeSemenStraw(Request $request): JsonResponse
    {
        $farm = Farm::first();
        if (! $farm) {
            return response()->json(['message' => 'Farm not configured'], 422);
        }

        $validated = $request->validate([
            'straw_code' => 'required|string|max:50|unique:semen_straw_inventories,straw_code',
            'sire_name' => 'required|string|max:150',
            'sire_breed' => 'required|string|max:100',
            'naab_code' => 'nullable|string|max:50',
            'semen_type' => 'required|in:conventional,sexed_female,sexed_male',
            'canister_location' => 'required|string|max:50',
            'cane_number' => 'required|string|max:50',
            'straws_in_stock' => 'required|integer|min:1',
            'unit_cost_pkr' => 'required|numeric|min:0',
        ]);

        $straw = SemenStrawInventory::create(array_merge($validated, [
            'farm_id' => $farm->id,
        ]));

        return response()->json([
            'message' => 'Semen straw inventory registered',
            'data' => $straw,
        ], 201);
    }

    public function recordCalving(
        Request $request,
        CalvingLifecycleEngine $calvingEngine
    ): JsonResponse {
        $validated = $request->validate([
            'dam_id' => 'required|exists:animals,id',
            'sire_id' => 'nullable|exists:animals,id',
            'pregnancy_id' => 'nullable|exists:pregnancies,id',
            'calving_datetime' => 'required|date',
            'calving_ease' => 'required|in:easy_unassisted,easy_slight_assistance,difficult_traction,surgical_caesarean,fetotomy',
            'birth_weight_kg' => 'required|numeric|min:1',
            'offspring_sex' => 'required|in:male,female,mixed',
            'colostrum_fed' => 'nullable|boolean',
            'colostrum_liters' => 'nullable|numeric|min:0',
            'attendant_name' => 'nullable|string|max:100',
            'notes' => 'nullable|string',
        ]);

        $dam = Animal::findOrFail($validated['dam_id']);
        $sire = ! empty($validated['sire_id']) ? Animal::find($validated['sire_id']) : null;
        $pregnancy = ! empty($validated['pregnancy_id']) ? Pregnancy::find($validated['pregnancy_id']) : null;

        $result = $calvingEngine->registerCalvingEvent(
            dam: $dam,
            sire: $sire,
            pregnancy: $pregnancy,
            details: $validated
        );

        return response()->json([
            'message' => 'Calving event processed: newborn registered, dam status updated to lactating, and postpartum care scheduled',
            'data' => $result,
        ], 201);
    }

    public function postpartumChecks(): JsonResponse
    {
        $farm = Farm::first();
        if (! $farm) {
            return response()->json(['data' => []]);
        }

        $checks = PostpartumCheck::where('farm_id', $farm->id)
            ->with(['animal', 'birth'])
            ->latest('check_date')
            ->get();

        return response()->json(['data' => $checks]);
    }

    public function kpis(BreedingReproductiveAnalyticsService $analyticsService): JsonResponse
    {
        $farm = Farm::first();
        if (! $farm) {
            return response()->json(['data' => []]);
        }

        $kpis = $analyticsService->calculateHerdReproductiveKpis($farm);

        return response()->json(['data' => $kpis]);
    }
}
