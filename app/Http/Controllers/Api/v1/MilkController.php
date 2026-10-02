<?php

namespace App\Http\Controllers\Api\v1;

use App\Domain\Animals\Models\Animal;
use App\Domain\Milk\Models\BulkTank;
use App\Domain\Milk\Models\MilkDispatch;
use App\Domain\Milk\Models\MilkRecord;
use App\Domain\Milk\Models\MilkSession;
use App\Domain\Milk\Services\MilkWithholdingSafetyService;
use App\Domain\Milk\Services\SomaticCellCountGraderService;
use App\Domain\Organization\Models\Farm;
use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class MilkController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $farm = Farm::first();
        if (! $farm) {
            return response()->json(['data' => []]);
        }

        $query = MilkRecord::where('farm_id', $farm->id)
            ->with(['animal.species', 'animal.breed', 'session.bulkTank', 'treatment.medicine']);

        if ($request->filled('date')) {
            $query->where('recorded_date', $request->date);
        }

        if ($request->filled('animal_id')) {
            $query->where('animal_id', $request->animal_id);
        }

        if ($request->filled('shift')) {
            $query->where('shift', $request->shift);
        }

        if ($request->filled('quality_status')) {
            $query->where('quality_status', $request->quality_status);
        }

        $records = $query->latest('recorded_date')->latest('id')->paginate(30);

        return response()->json($records);
    }

    public function storeRecord(
        Request $request,
        MilkWithholdingSafetyService $safetyService,
        SomaticCellCountGraderService $sccGrader
    ): JsonResponse {
        $farm = Farm::first();
        if (! $farm) {
            return response()->json(['message' => 'Farm not found'], 422);
        }

        $validated = $request->validate([
            'animal_id' => 'required|exists:animals,id',
            'milk_session_id' => 'nullable|exists:milk_sessions,id',
            'recorded_date' => 'required|date',
            'shift' => 'required|in:morning,afternoon,evening,night',
            'yield_liters' => 'required|numeric|min:0.1',
            'flow_rate_kg_min' => 'nullable|numeric|min:0',
            'milking_duration_seconds' => 'nullable|integer|min:0',
            'fat_percentage' => 'nullable|numeric|between:1,12',
            'snf_percentage' => 'nullable|numeric|between:5,15',
            'protein_percentage' => 'nullable|numeric|between:2,6',
            'lactose_percentage' => 'nullable|numeric|between:2,7',
            'scc' => 'nullable|integer|min:0',
            'temperature_c' => 'nullable|numeric',
            'electrical_conductivity_ms_cm' => 'nullable|numeric|min:0',
            'is_colostrum' => 'nullable|boolean',
            'operator_notes' => 'nullable|string',
            'override_withholding' => 'nullable|boolean',
            'override_reason' => 'nullable|string|required_if:override_withholding,true',
            'override_user_id' => 'nullable|exists:users,id',
        ]);

        $animal = Animal::findOrFail($validated['animal_id']);

        // 1. Food Safety & Biosecurity Withdrawal Lock Engine
        $overrideUser = (! empty($validated['override_withholding']) && ! empty($validated['override_reason']))
            ? ($request->user() ?: auth()->user() ?: (! empty($validated['override_user_id']) ? User::find($validated['override_user_id']) : null))
            : null;

        $safetyResult = $safetyService->evaluateMilkSafety(
            animal: $animal,
            recordedAt: $validated['recorded_date'],
            overrideUser: $overrideUser,
            overrideReason: $validated['override_reason'] ?? null
        );

        // 2. SCC Quality Grading (if not withheld)
        $qualityStatus = $safetyResult['quality_status'];
        if (! $safetyResult['is_withheld']) {
            $qualityStatus = $sccGrader->gradeByScc($validated['scc'] ?? null);
        }

        // 3. Electrical Conductivity Mastitis Alert
        $ecAlert = $sccGrader->evaluateConductivity($validated['electrical_conductivity_ms_cm'] ?? null);

        // 4. Create Milk Record
        $record = MilkRecord::create(array_merge($validated, [
            'farm_id' => $farm->id,
            'quality_status' => $qualityStatus,
            'causative_treatment_id' => $safetyResult['causative_treatment_id'],
            'discard_reason' => $safetyResult['discard_reason'],
            'withholding_override_by' => $overrideUser?->id,
            'withholding_override_reason' => $validated['override_reason'] ?? null,
            'recorded_by' => auth()->id(),
        ]));

        // 5. Update Session & Bulk Tank volumes (ONLY if safe to pool)
        if ($safetyResult['safe_to_pool'] && ! empty($validated['milk_session_id'])) {
            $session = MilkSession::find($validated['milk_session_id']);
            if ($session) {
                $session->increment('total_yield_liters', (float) $validated['yield_liters']);

                if ($session->bulk_tank_id) {
                    $tank = BulkTank::find($session->bulk_tank_id);
                    if ($tank) {
                        $tank->increment('current_volume_liters', (float) $validated['yield_liters']);
                    }
                }
            }
        }

        $warning = null;
        if ($safetyResult['is_withheld']) {
            $warning = $safetyResult['safe_to_pool']
                ? "NOTICE: Withholding lock was overridden by supervisor ({$validated['override_reason']})."
                : "FOOD SAFETY HAZARD: Animal {$animal->tag_number} is under active drug withdrawal! Milk excluded from bulk tank.";
        }

        return response()->json([
            'message' => 'Milk record processed successfully',
            'withdrawal_alert' => $warning,
            'conductivity_alert' => $ecAlert['has_alert'] ? $ecAlert['message'] : null,
            'safe_to_pool' => $safetyResult['safe_to_pool'],
            'data' => $record->load(['animal', 'treatment.medicine']),
        ], 201);
    }

    public function summary(): JsonResponse
    {
        $farm = Farm::first();
        if (! $farm) {
            return response()->json([]);
        }

        $today = Carbon::now()->toDateString();

        $cowMorning = MilkRecord::where('farm_id', $farm->id)
            ->where('recorded_date', $today)
            ->where('shift', 'morning')
            ->whereNotIn('quality_status', ['discarded_withdrawal', 'discarded_mastitis'])
            ->whereHas('animal.species', fn ($q) => $q->where('code', 'cattle'))
            ->sum('yield_liters');

        $cowEvening = MilkRecord::where('farm_id', $farm->id)
            ->where('recorded_date', $today)
            ->where('shift', 'evening')
            ->whereNotIn('quality_status', ['discarded_withdrawal', 'discarded_mastitis'])
            ->whereHas('animal.species', fn ($q) => $q->where('code', 'cattle'))
            ->sum('yield_liters');

        $goatMorning = MilkRecord::where('farm_id', $farm->id)
            ->where('recorded_date', $today)
            ->where('shift', 'morning')
            ->whereNotIn('quality_status', ['discarded_withdrawal', 'discarded_mastitis'])
            ->whereHas('animal.species', fn ($q) => $q->where('code', 'goat'))
            ->sum('yield_liters');

        $goatEvening = MilkRecord::where('farm_id', $farm->id)
            ->where('recorded_date', $today)
            ->where('shift', 'evening')
            ->whereNotIn('quality_status', ['discarded_withdrawal', 'discarded_mastitis'])
            ->whereHas('animal.species', fn ($q) => $q->where('code', 'goat'))
            ->sum('yield_liters');

        $discardedToday = MilkRecord::where('farm_id', $farm->id)
            ->where('recorded_date', $today)
            ->whereIn('quality_status', ['discarded_withdrawal', 'discarded_mastitis'])
            ->sum('yield_liters');

        return response()->json([
            'date' => $today,
            'cattle' => [
                'morning' => round($cowMorning, 1),
                'evening' => round($cowEvening, 1),
                'total' => round($cowMorning + $cowEvening, 1),
            ],
            'goats' => [
                'morning' => round($goatMorning, 1),
                'evening' => round($goatEvening, 1),
                'total' => round($goatMorning + $goatEvening, 1),
            ],
            'discarded_liters' => round($discardedToday, 1),
            'saleable_grand_total' => round($cowMorning + $cowEvening + $goatMorning + $goatEvening, 1),
        ]);
    }

    /**
     * Get farm bulk milk cooling tanks with live fill status and CIP hygiene logs.
     */
    public function bulkTanks(): JsonResponse
    {
        $farm = Farm::first();
        if (! $farm) {
            return response()->json(['data' => []]);
        }

        $tanks = BulkTank::where('farm_id', $farm->id)
            ->with(['cipCleaner'])
            ->get()
            ->map(function ($tank) {
                return [
                    'id' => $tank->id,
                    'tank_code' => $tank->tank_code,
                    'model_name' => $tank->model_name,
                    'capacity_liters' => (float) $tank->capacity_liters,
                    'current_volume_liters' => (float) $tank->current_volume_liters,
                    'fill_percentage' => $tank->fill_percentage,
                    'target_temperature_c' => (float) $tank->target_temperature_c,
                    'current_temperature_c' => (float) $tank->current_temperature_c,
                    'cooling_status' => $tank->cooling_status,
                    'agitator_status' => $tank->agitator_status,
                    'is_sanitized' => $tank->is_sanitized,
                    'last_cip_cleaned_at' => $tank->last_cip_cleaned_at,
                    'status' => $tank->status,
                ];
            });

        return response()->json(['data' => $tanks]);
    }

    /**
     * Record Clean-In-Place (CIP) chemical wash on bulk milk tank.
     */
    public function cipClean(Request $request, int $id): JsonResponse
    {
        $tank = BulkTank::findOrFail($id);

        $tank->update([
            'last_cip_cleaned_at' => now(),
            'last_cip_cleaned_by' => auth()->id(),
            'is_sanitized' => true,
            'current_volume_liters' => 0.0, // Flushed clean
        ]);

        return response()->json([
            'message' => "Bulk tank {$tank->tank_code} Clean-In-Place (CIP) wash completed successfully",
            'data' => $tank,
        ]);
    }

    /**
     * List milk dispatches and tanker gate passes.
     */
    public function dispatches(): JsonResponse
    {
        $farm = Farm::first();
        if (! $farm) {
            return response()->json(['data' => []]);
        }

        $dispatches = MilkDispatch::where('farm_id', $farm->id)
            ->with(['bulkTank', 'authorizer'])
            ->latest('dispatched_at')
            ->paginate(25);

        return response()->json($dispatches);
    }

    /**
     * Create tanker dispatch and deplete bulk tank volume.
     */
    public function storeDispatch(Request $request): JsonResponse
    {
        $farm = Farm::first();
        if (! $farm) {
            return response()->json(['message' => 'Farm not found'], 422);
        }

        $validated = $request->validate([
            'bulk_tank_id' => 'required|exists:bulk_tanks,id',
            'buyer_name' => 'required|string|max:150',
            'driver_name' => 'nullable|string|max:100',
            'driver_phone' => 'nullable|string|max:50',
            'tanker_plate_number' => 'required|string|max:50',
            'seal_number' => 'required|string|max:50',
            'dispatched_volume_liters' => 'required|numeric|min:1',
            'temperature_c' => 'required|numeric',
            'composite_fat_percentage' => 'required|numeric|between:1,12',
            'composite_snf_percentage' => 'required|numeric|between:5,15',
            'composite_scc' => 'nullable|integer',
            'unit_price_pkr' => 'required|numeric|min:1',
            'notes' => 'nullable|string',
        ]);

        $tank = BulkTank::findOrFail($validated['bulk_tank_id']);
        if ((float) $tank->current_volume_liters < (float) $validated['dispatched_volume_liters']) {
            throw ValidationException::withMessages([
                'dispatched_volume_liters' => ["Insufficient volume in tank {$tank->tank_code}. Available: {$tank->current_volume_liters}L, requested: {$validated['dispatched_volume_liters']}L"],
            ]);
        }

        $totalPrice = round((float) $validated['dispatched_volume_liters'] * (float) $validated['unit_price_pkr'], 2);
        $dispatchNum = 'DSP-'.date('Ymd').'-'.strtoupper(Str::random(4));

        $dispatch = MilkDispatch::create(array_merge($validated, [
            'farm_id' => $farm->id,
            'dispatch_number' => $dispatchNum,
            'total_price_pkr' => $totalPrice,
            'dispatched_at' => now(),
            'authorized_by' => auth()->id(),
            'status' => 'dispatched',
        ]));

        // Deduct from bulk tank
        $tank->decrement('current_volume_liters', (float) $validated['dispatched_volume_liters']);

        return response()->json([
            'message' => "Milk tanker dispatch {$dispatchNum} issued successfully",
            'data' => $dispatch,
        ], 201);
    }
}
