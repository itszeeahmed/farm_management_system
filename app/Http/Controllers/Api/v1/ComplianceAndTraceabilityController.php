<?php

namespace App\Http\Controllers\Api\v1;

use App\Domain\Animals\Models\Animal;
use App\Domain\Compliance\Models\AnimalMovementPermit;
use App\Domain\Compliance\Models\AnimalWelfareAssessment;
use App\Domain\Compliance\Models\CompliancePack;
use App\Domain\Compliance\Models\HalalSlaughterCertification;
use App\Domain\Compliance\Services\TraceabilityEngine;
use App\Domain\Organization\Models\Farm;
use App\Domain\Sales\Models\DeliveryRunStop;
use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ComplianceAndTraceabilityController extends Controller
{
    public function compliancePacks(Request $request): JsonResponse
    {
        $packs = CompliancePack::where('is_enabled', true)->get();

        return response()->json(['data' => $packs]);
    }

    public function movementPermits(Request $request): JsonResponse
    {
        $farm = Farm::first();
        if (! $farm) {
            return response()->json(['data' => []]);
        }

        $permits = AnimalMovementPermit::where('farm_id', $farm->id)
            ->latest('departure_date')
            ->paginate($request->integer('per_page', 20));

        return response()->json($permits);
    }

    public function createMovementPermit(Request $request): JsonResponse
    {
        $farm = Farm::firstOrFail();

        $validated = $request->validate([
            'departure_date' => 'required|date',
            'movement_purpose' => 'required|in:slaughter,sale_transfer,exhibition_show,pasture_transhumance',
            'destination_premises_name' => 'required|string|max:150',
            'destination_address' => 'required|string|max:255',
            'vehicle_plate_number' => 'required|string|max:50',
            'driver_name' => 'required|string|max:100',
            'driver_phone' => 'nullable|string|max:50',
            'animal_ids' => 'required|array|min:1',
            'animal_ids.*' => 'exists:animals,id',
            'veterinary_health_certificate_no' => 'nullable|string|max:100',
        ]);

        $animals = Animal::whereIn('id', $validated['animal_ids'])->get();

        $permitNumber = 'PERMIT-'.now()->format('ymd').'-'.rand(1000, 9999);

        $permit = AnimalMovementPermit::create([
            'farm_id' => $farm->id,
            'permit_number' => $permitNumber,
            'departure_date' => $validated['departure_date'],
            'movement_purpose' => $validated['movement_purpose'],
            'origin_premises_id' => $farm->code ?? "FARM-{$farm->id}",
            'destination_premises_name' => $validated['destination_premises_name'],
            'destination_address' => $validated['destination_address'],
            'vehicle_plate_number' => $validated['vehicle_plate_number'],
            'driver_name' => $validated['driver_name'],
            'driver_phone' => $validated['driver_phone'] ?? null,
            'animal_ids_json' => $animals->map(fn ($a) => ['id' => $a->id, 'tag' => $a->tag_number])->toArray(),
            'total_heads' => $animals->count(),
            'veterinary_health_certificate_no' => $validated['veterinary_health_certificate_no'] ?? null,
            'status' => 'approved',
            'approved_by' => $request->user()?->id,
        ]);

        return response()->json([
            'message' => 'Animal movement transit permit approved and issued',
            'data' => $permit,
        ], 201);
    }

    public function forwardTrace(int $animalId, TraceabilityEngine $engine): JsonResponse
    {
        $animal = Animal::findOrFail($animalId);
        $trace = $engine->forwardTrace($animal);

        return response()->json(['data' => $trace]);
    }

    public function backwardTrace(int $deliveryStopId, TraceabilityEngine $engine): JsonResponse
    {
        $stop = DeliveryRunStop::findOrFail($deliveryStopId);
        $trace = $engine->backwardTrace($stop);

        return response()->json(['data' => $trace]);
    }

    public function halalCertifications(Request $request): JsonResponse
    {
        $farm = Farm::first();
        if (! $farm) {
            return response()->json(['data' => []]);
        }

        $certs = HalalSlaughterCertification::where('farm_id', $farm->id)
            ->with('slaughterRecord')
            ->latest('verified_at')
            ->paginate($request->integer('per_page', 20));

        return response()->json($certs);
    }

    public function recordHalalCertification(Request $request): JsonResponse
    {
        $farm = Farm::firstOrFail();

        $validated = $request->validate([
            'slaughter_record_id' => 'required|exists:slaughter_records,id',
            'certification_body' => 'required|string|max:150',
            'slaughterer_name' => 'required|string|max:100',
            'slaughterer_credential_id' => 'required|string|max:100',
            'slaughter_method' => 'nullable|string|in:tazkiyah_non_stun,reversible_stunning_approved',
            'inspector_name' => 'required|string|max:100',
        ]);

        $certNumber = 'HALAL-'.now()->format('ymd').'-'.rand(1000, 9999);

        $cert = HalalSlaughterCertification::create([
            'farm_id' => $farm->id,
            'slaughter_record_id' => $validated['slaughter_record_id'],
            'certificate_number' => $certNumber,
            'certification_body' => $validated['certification_body'],
            'slaughterer_name' => $validated['slaughterer_name'],
            'slaughterer_credential_id' => $validated['slaughterer_credential_id'],
            'slaughter_method' => $validated['slaughter_method'] ?? 'tazkiyah_non_stun',
            'tasmiyah_recited' => true,
            'trachea_esophagus_jugular_cut_verified' => true,
            'inspector_name' => $validated['inspector_name'],
            'verified_at' => Carbon::now(),
        ]);

        return response()->json([
            'message' => 'Halal slaughter inspection certified and recorded',
            'data' => $cert->load('slaughterRecord'),
        ], 201);
    }

    public function recordWelfareAssessment(Request $request): JsonResponse
    {
        $farm = Farm::firstOrFail();

        $validated = $request->validate([
            'audit_date' => 'required|date',
            'auditor_name' => 'required|string|max:100',
            'water_access_score' => 'required|integer|between:1,5',
            'thermal_comfort_score' => 'required|integer|between:1,5',
            'bedding_cleanliness_score' => 'required|integer|between:1,5',
            'lameness_prevalence_percent' => 'required|numeric|between:0,100',
            'space_allowance_score' => 'required|integer|between:1,5',
            'corrective_actions' => 'nullable|string',
        ]);

        $scores = [
            $validated['water_access_score'],
            $validated['thermal_comfort_score'],
            $validated['bedding_cleanliness_score'],
            $validated['space_allowance_score'],
        ];
        $avgScore = array_sum($scores) / count($scores);

        $grade = match (true) {
            $avgScore >= 4.5 && $validated['lameness_prevalence_percent'] < 5.0 => 'excellent',
            $avgScore >= 3.5 && $validated['lameness_prevalence_percent'] < 12.0 => 'acceptable',
            $avgScore >= 2.5 => 'needs_improvement',
            default => 'critical_breach',
        };

        $assessment = AnimalWelfareAssessment::create(array_merge($validated, [
            'farm_id' => $farm->id,
            'overall_welfare_grade' => $grade,
        ]));

        return response()->json([
            'message' => 'Five Freedoms animal welfare audit recorded',
            'data' => $assessment,
        ], 201);
    }
}
