<?php

namespace App\Http\Controllers\Api\v1;

use App\Domain\Animals\Models\Animal;
use App\Domain\Health\Models\BiosecurityAudit;
use App\Domain\Health\Models\HealthCase;
use App\Domain\Health\Models\Medicine;
use App\Domain\Health\Models\Treatment;
use App\Domain\Health\Services\AntimicrobialUsageCalculator;
use App\Domain\Organization\Models\Farm;
use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

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
            'subjective_notes' => 'nullable|string',
            'objective_temp_c' => 'nullable|numeric|between:35,43',
            'objective_heart_rate' => 'nullable|integer|min:30',
            'objective_respiration_rate' => 'nullable|integer|min:10',
            'objective_rumen_motility_per_2min' => 'nullable|integer|between:0,6',
            'assessment_notes' => 'nullable|string',
            'plan_notes' => 'nullable|string',
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
            'created_by' => auth()->id(),
        ]));

        return response()->json([
            'message' => 'Clinical SOAP health case recorded successfully',
            'data' => $case->load('animal'),
        ], 201);
    }

    public function storeTreatment(
        Request $request,
        AntimicrobialUsageCalculator $amuCalculator
    ): JsonResponse {
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
            'veterinarian_license_number' => 'nullable|string|max:50',
            'prescription_number' => 'nullable|string|max:50',
            'batch_lot_number' => 'nullable|string|max:50',
            'active_substance_administered_mg' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        $medicine = Medicine::findOrFail($validated['medicine_id']);
        $animal = Animal::findOrFail($validated['animal_id']);

        // Check WHO Critically Important Antimicrobial (CIA) Compliance
        $compliance = $amuCalculator->evaluateAntimicrobialCompliance(
            medicine: $medicine,
            prescriptionNumber: $validated['prescription_number'] ?? null,
            vetLicenseNumber: $validated['veterinarian_license_number'] ?? null
        );

        if (! $compliance['allowed']) {
            throw ValidationException::withMessages([
                'medicine_id' => [$compliance['warning']],
            ]);
        }

        // Calculate DDDA units consumed
        $activeMg = (float) ($validated['active_substance_administered_mg'] ?? ($validated['dosage'] * 100.0));
        $animalWeight = (float) ($animal->current_weight_kg ?? 450.0);
        $ddda = $amuCalculator->calculateTreatmentDdda($medicine, $activeMg, $animalWeight);

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
            'active_substance_administered_mg' => $activeMg,
            'ddda_units_consumed' => $ddda,
            'milk_withdrawal_until' => $milkWithdrawalUntil,
            'meat_withdrawal_until' => $meatWithdrawalUntil,
        ]));

        return response()->json([
            'message' => 'Treatment administered and AMU DDDA tracked',
            'antimicrobial_warning' => $compliance['warning'],
            'ddda_units_consumed' => $ddda,
            'milk_withdrawal_until' => $milkWithdrawalUntil ? $milkWithdrawalUntil->toIso8601String() : null,
            'data' => $treatment->load(['animal', 'medicine']),
        ], 201);
    }

    public function amuSummary(): JsonResponse
    {
        $farm = Farm::first();
        if (! $farm) {
            return response()->json(['data' => []]);
        }

        $totalTreatments = Treatment::where('farm_id', $farm->id)->count();
        $totalDdda = (float) Treatment::where('farm_id', $farm->id)->sum('ddda_units_consumed');
        $antimicrobialTreatments = Treatment::where('farm_id', $farm->id)
            ->whereHas('medicine', fn ($q) => $q->where('is_antimicrobial', true))
            ->count();

        $ciaTreatments = Treatment::where('farm_id', $farm->id)
            ->whereHas('medicine', fn ($q) => $q->where('who_classification', 'critically_important'))
            ->count();

        return response()->json([
            'data' => [
                'total_treatments' => $totalTreatments,
                'total_antimicrobial_treatments' => $antimicrobialTreatments,
                'total_ddda_consumed' => round($totalDdda, 3),
                'critically_important_antimicrobial_treatments' => $ciaTreatments,
                'stewardship_status' => $ciaTreatments > 0 ? 'controlled_monitoring' : 'compliant',
            ],
        ]);
    }

    public function biosecurityAudits(): JsonResponse
    {
        $farm = Farm::first();
        if (! $farm) {
            return response()->json(['data' => []]);
        }

        $audits = BiosecurityAudit::where('farm_id', $farm->id)
            ->latest('audit_date')
            ->get();

        return response()->json(['data' => $audits]);
    }

    public function storeBiosecurityAudit(Request $request): JsonResponse
    {
        $farm = Farm::first();
        if (! $farm) {
            return response()->json(['message' => 'Farm not configured'], 422);
        }

        $validated = $request->validate([
            'audit_date' => 'required|date',
            'auditor_name' => 'required|string|max:100',
            'visitor_log_compliance_score' => 'required|integer|between:0,100',
            'footbath_disinfection_score' => 'required|integer|between:0,100',
            'quarantine_compliance_score' => 'required|integer|between:0,100',
            'carcass_disposal_compliance_score' => 'required|integer|between:0,100',
            'overall_risk_rating' => 'required|in:low_risk,medium_risk,critical_risk',
            'corrective_actions' => 'nullable|string',
        ]);

        $audit = BiosecurityAudit::create(array_merge($validated, [
            'farm_id' => $farm->id,
        ]));

        return response()->json([
            'message' => 'Biosecurity audit recorded successfully',
            'data' => $audit,
        ], 201);
    }
}
