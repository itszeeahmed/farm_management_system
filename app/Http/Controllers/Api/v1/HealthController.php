<?php

namespace App\Http\Controllers\Api\v1;

use App\Domain\Animals\Models\Animal;
use App\Domain\Health\Models\HealthCase;
use App\Domain\Health\Models\Medicine;
use App\Domain\Health\Models\Treatment;
use App\Domain\Organization\Models\Farm;
use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class HealthController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $farm = Farm::first();
        if (! $farm) {
            return response()->json(['cases' => [], 'active_withdrawals' => []]);
        }

        $cases = HealthCase::where('farm_id', $farm->id)
            ->with(['animal.species', 'animal.breed', 'treatments.medicine'])
            ->latest('symptom_observed_at')
            ->get();

        $activeWithdrawals = Treatment::where('farm_id', $farm->id)
            ->with(['animal', 'medicine'])
            ->where('milk_withdrawal_until', '>', Carbon::now())
            ->get();

        return response()->json([
            'cases' => $cases,
            'active_withdrawals' => $activeWithdrawals,
        ]);
    }

    public function medicines(): JsonResponse
    {
        $medicines = Medicine::all();

        return response()->json(['data' => $medicines]);
    }

    public function storeCase(Request $request): JsonResponse
    {
        $farm = Farm::first();
        if (! $farm) {
            return response()->json(['message' => 'Farm not configured'], 422);
        }

        $validated = $request->validate([
            'animal_id' => 'required|exists:animals,id',
            'diagnosis' => 'required|string|max:150',
            'symptom_observed_at' => 'required|date',
            'symptoms_description' => 'nullable|string',
            'severity' => 'required|in:mild,moderate,severe,critical',
            'attending_vet_name' => 'nullable|string|max:100',
        ]);

        $animal = Animal::findOrFail($validated['animal_id']);
        $animal->update(['status' => 'sick']);

        $caseNumber = 'HC-'.date('Y').'-'.str_pad(HealthCase::count() + 1, 4, '0', STR_PAD_LEFT);

        $case = HealthCase::create(array_merge($validated, [
            'farm_id' => $farm->id,
            'case_number' => $caseNumber,
            'status' => 'under_treatment',
        ]));

        return response()->json([
            'message' => 'Health case recorded successfully',
            'data' => $case->load('animal'),
        ], 201);
    }

    public function storeTreatment(Request $request): JsonResponse
    {
        $farm = Farm::first();
        if (! $farm) {
            return response()->json(['message' => 'Farm not configured'], 422);
        }

        $validated = $request->validate([
            'animal_id' => 'required|exists:animals,id',
            'health_case_id' => 'nullable|exists:health_cases,id',
            'medicine_id' => 'required|exists:medicines,id',
            'dosage' => 'required|numeric|min:0.1',
            'dosage_unit' => 'required|string',
            'route' => 'nullable|string',
            'cost' => 'nullable|numeric|min:0',
            'administered_by' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        $medicine = Medicine::findOrFail($validated['medicine_id']);
        $adminTime = Carbon::now();

        // Calculate withdrawal periods based on medicine spec (HLT-013, HLT-018)
        $milkWithdrawalUntil = $medicine->milk_withdrawal_days > 0
            ? $adminTime->copy()->addDays($medicine->milk_withdrawal_days)
            : null;

        $meatWithdrawalUntil = $medicine->meat_withdrawal_days > 0
            ? $adminTime->copy()->addDays($medicine->meat_withdrawal_days)
            : null;

        $treatment = Treatment::create(array_merge($validated, [
            'farm_id' => $farm->id,
            'administered_at' => $adminTime,
            'milk_withdrawal_until' => $milkWithdrawalUntil,
            'meat_withdrawal_until' => $meatWithdrawalUntil,
        ]));

        return response()->json([
            'message' => 'Treatment administered successfully',
            'milk_withdrawal_until' => $milkWithdrawalUntil ? $milkWithdrawalUntil->toIso8601String() : null,
            'data' => $treatment->load(['animal', 'medicine']),
        ], 201);
    }
}
