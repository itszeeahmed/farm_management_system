<?php

namespace App\Http\Controllers\Api\v1;

use App\Domain\Animals\Models\Animal;
use App\Domain\Animals\Models\AnimalBcsRecord;
use App\Domain\Animals\Models\AnimalIdentifier;
use App\Domain\Animals\Services\AnimalLifecycleStateMachine;
use App\Domain\Animals\Services\AverageDailyGainCalculator;
use App\Domain\Animals\Services\InbreedingCoefficientCalculator;
use App\Domain\Organization\Models\Farm;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AnimalController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $farm = Farm::current();
        if (! $farm) {
            return response()->json(['data' => []]);
        }

        $query = Animal::where('farm_id', $farm->id)
            ->with(['species', 'breed', 'pen', 'structure', 'zone', 'primaryIdentifier', 'currentGroups']);

        if ($request->filled('species')) {
            $query->whereHas('species', fn ($q) => $q->where('code', $request->species));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('lifecycle_stage')) {
            $query->where('lifecycle_stage', $request->lifecycle_stage);
        }

        if ($request->filled('group_id')) {
            $query->whereHas('groups', fn ($q) => $q->where('animal_groups.id', $request->group_id)->where('is_current', true));
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('tag_number', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%")
                    ->orWhere('electronic_id', 'like', "%{$search}%")
                    ->orWhere('qr_code_identifier', 'like', "%{$search}%")
                    ->orWhereHas('identifiers', fn ($iq) => $iq->where('id_value', 'like', "%{$search}%"));
            });
        }

        $animals = $query->orderBy('tag_number')->get();

        return response()->json([
            'count' => $animals->count(),
            'data' => $animals,
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $animal = Animal::with([
            'species',
            'breed',
            'pen.barn',
            'structure.parentStructure',
            'zone',
            'identifiers',
            'pedigree.sire',
            'pedigree.dam',
            'currentGroups',
            'weights' => fn ($q) => $q->latest('recorded_at')->take(10),
            'bcsRecords' => fn ($q) => $q->latest('assessed_at')->take(10),
            'sire',
            'dam',
            'milkRecords' => fn ($q) => $q->latest('recorded_date')->take(14),
            'healthCases' => fn ($q) => $q->latest('symptom_observed_at'),
            'treatments.medicine',
            'vaccinations',
            'breedingEvents.sire',
            'pregnancies',
        ])->find($id);

        if (! $animal) {
            return response()->json(['message' => 'Animal not found'], 404);
        }

        return response()->json([
            'data' => $animal,
        ]);
    }

    public function pedigree(int $id, InbreedingCoefficientCalculator $calculator): JsonResponse
    {
        $animal = Animal::with(['sire', 'dam', 'breed', 'pedigree'])->find($id);
        if (! $animal) {
            return response()->json(['message' => 'Animal not found'], 404);
        }

        $tree = $calculator->getPedigreeTree($animal, maxDepth: 3);
        $fx = $calculator->calculateInbreedingCoefficient($animal->sire, $animal->dam);

        return response()->json([
            'animal_id' => $animal->id,
            'tag_number' => $animal->tag_number,
            'inbreeding_coefficient' => $fx,
            'pedigree_record' => $animal->pedigree,
            'lineage_tree' => $tree,
        ]);
    }

    public function recordWeight(Request $request, int $id, AverageDailyGainCalculator $calculator): JsonResponse
    {
        $animal = Animal::find($id);
        if (! $animal) {
            return response()->json(['message' => 'Animal not found'], 404);
        }

        $validated = $request->validate([
            'weight_kg' => 'required|numeric|min:1',
            'recorded_at' => 'required|date',
            'weighing_method' => 'nullable|string|in:scale,heart_girth_tape,visual_estimate,3d_camera_sensor',
            'heart_girth_cm' => 'nullable|numeric|min:10',
            'body_length_cm' => 'nullable|numeric|min:10',
            'notes' => 'nullable|string',
        ]);

        $record = $calculator->recordWeight(
            animal: $animal,
            weightKg: (float) $validated['weight_kg'],
            recordedAt: $validated['recorded_at'],
            weighingMethod: $validated['weighing_method'] ?? 'scale',
            heartGirthCm: isset($validated['heart_girth_cm']) ? (float) $validated['heart_girth_cm'] : null,
            bodyLengthCm: isset($validated['body_length_cm']) ? (float) $validated['body_length_cm'] : null,
            recordedByUserId: auth()->id(),
            notes: $validated['notes'] ?? null
        );

        return response()->json([
            'message' => 'Weight and ADG recorded successfully',
            'data' => $record,
            'current_weight_kg' => $animal->fresh()->current_weight_kg,
        ], 201);
    }

    public function recordBcs(Request $request, int $id): JsonResponse
    {
        $animal = Animal::find($id);
        if (! $animal) {
            return response()->json(['message' => 'Animal not found'], 404);
        }

        $validated = $request->validate([
            'bcs_score' => 'required|numeric|min:1.0|max:5.0',
            'locomotion_score' => 'nullable|integer|between:1,5',
            'rumen_fill_score' => 'nullable|integer|between:1,5',
            'cleanliness_score' => 'nullable|integer|between:1,5',
            'assessed_at' => 'required|date',
            'notes' => 'nullable|string',
        ]);

        $bcs = AnimalBcsRecord::create(array_merge($validated, [
            'animal_id' => $animal->id,
            'assessed_by' => auth()->id(),
        ]));

        return response()->json([
            'message' => 'BCS assessment recorded successfully',
            'data' => $bcs,
        ], 201);
    }

    public function transitionLifecycle(Request $request, int $id, AnimalLifecycleStateMachine $stateMachine): JsonResponse
    {
        $animal = Animal::find($id);
        if (! $animal) {
            return response()->json(['message' => 'Animal not found'], 404);
        }

        $validated = $request->validate([
            'new_stage' => 'required|string',
            'reason' => 'nullable|string',
        ]);

        $updatedAnimal = $stateMachine->transition(
            animal: $animal,
            newStage: $validated['new_stage'],
            userId: auth()->id(),
            reason: $validated['reason'] ?? null
        );

        return response()->json([
            'message' => "Lifecycle state updated to {$validated['new_stage']}",
            'data' => $updatedAnimal,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $farm = Farm::current();
        if (! $farm) {
            return response()->json(['message' => 'Farm not configured'], 422);
        }

        $validated = $request->validate([
            'species_id' => 'required|exists:species,id',
            'breed_id' => 'nullable|exists:breeds,id',
            'pen_id' => 'nullable|exists:pens,id',
            'structure_id' => 'nullable|exists:farm_structures,id',
            'zone_id' => 'nullable|exists:farm_zones,id',
            'tag_number' => 'required|string|max:50|unique:animals,tag_number,NULL,id,farm_id,'.$farm->id,
            'name' => 'nullable|string|max:100',
            'sex' => 'required|in:female,male',
            'birth_date' => 'nullable|date',
            'current_weight_kg' => 'nullable|numeric|min:0',
            'status' => 'required|in:active,lactating,dry,pregnant,open,sick,quarantine,sold,culled,deceased',
            'lifecycle_stage' => 'nullable|string',
            'parity' => 'nullable|integer|min:0',
            'notes' => 'nullable|string',
        ]);

        $qrCode = 'FMS-'.strtoupper(Str::slug($validated['tag_number'])).'-QR';
        $eid = '982'.rand(100000000, 999999999);

        $animal = Animal::create(array_merge($validated, [
            'organization_id' => $farm->organization_id,
            'farm_id' => $farm->id,
            'qr_code_identifier' => $qrCode,
            'electronic_id' => $eid,
        ]));

        AnimalIdentifier::create([
            'animal_id' => $animal->id,
            'id_type' => 'rfid_iso11784',
            'id_value' => $eid,
            'tag_color' => 'yellow',
            'tag_placement' => 'left_ear',
            'is_primary' => true,
            'applied_date' => now(),
        ]);

        AnimalIdentifier::create([
            'animal_id' => $animal->id,
            'id_type' => 'visual_ear_tag',
            'id_value' => $validated['tag_number'],
            'tag_color' => 'yellow',
            'tag_placement' => 'right_ear',
            'is_primary' => false,
            'applied_date' => now(),
        ]);

        return response()->json([
            'message' => 'Animal registered successfully with ISO RFID tag',
            'data' => $animal->load(['species', 'breed', 'identifiers']),
        ], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $animal = Animal::find($id);
        if (! $animal) {
            return response()->json(['message' => 'Animal not found'], 404);
        }

        $validated = $request->validate([
            'name' => 'nullable|string|max:100',
            'status' => 'nullable|in:active,lactating,dry,pregnant,open,sick,quarantine,sold,culled,deceased',
            'pen_id' => 'nullable|exists:pens,id',
            'structure_id' => 'nullable|exists:farm_structures,id',
            'zone_id' => 'nullable|exists:farm_zones,id',
            'current_weight_kg' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        $animal->update($validated);

        return response()->json([
            'message' => 'Animal updated successfully',
            'data' => $animal,
        ]);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $farm = Farm::current();
        $query = Animal::query();
        if ($farm) {
            $query->where('farm_id', $farm->id);
        }

        $animal = $query->find($id);
        if (! $animal) {
            return response()->json(['message' => 'Animal not found'], 404);
        }

        $animal->delete();

        return response()->json([
            'message' => 'Animal successfully culled or removed from herd record.',
        ]);
    }
}
