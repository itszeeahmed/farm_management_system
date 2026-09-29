<?php

namespace App\Http\Controllers\Api\v1;

use App\Domain\Animals\Models\Animal;
use App\Domain\Organization\Models\Farm;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AnimalController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $farm = Farm::first();
        if (! $farm) {
            return response()->json(['data' => []]);
        }

        $query = Animal::where('farm_id', $farm->id)
            ->with(['species', 'breed', 'pen']);

        if ($request->filled('species')) {
            $query->whereHas('species', fn ($q) => $q->where('code', $request->species));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('tag_number', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%")
                    ->orWhere('electronic_id', 'like', "%{$search}%")
                    ->orWhere('qr_code_identifier', 'like', "%{$search}%");
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
            'sire',
            'dam',
            'weightRecords' => fn ($q) => $q->latest('recorded_at')->take(10),
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

    public function store(Request $request): JsonResponse
    {
        $farm = Farm::first();
        if (! $farm) {
            return response()->json(['message' => 'Farm not configured'], 422);
        }

        $validated = $request->validate([
            'species_id' => 'required|exists:species,id',
            'breed_id' => 'nullable|exists:breeds,id',
            'pen_id' => 'nullable|exists:pens,id',
            'tag_number' => 'required|string|max:50|unique:animals,tag_number,NULL,id,farm_id,'.$farm->id,
            'name' => 'nullable|string|max:100',
            'sex' => 'required|in:female,male',
            'birth_date' => 'nullable|date',
            'current_weight_kg' => 'nullable|numeric|min:0',
            'status' => 'required|in:active,lactating,dry,pregnant,open,sick,quarantine,sold,culled,deceased',
            'parity' => 'nullable|integer|min:0',
            'notes' => 'nullable|string',
        ]);

        $qrCode = 'FMS-'.strtoupper(Str::slug($validated['tag_number'])).'-QR';

        $animal = Animal::create(array_merge($validated, [
            'organization_id' => $farm->organization_id,
            'farm_id' => $farm->id,
            'qr_code_identifier' => $qrCode,
            'electronic_id' => '982'.rand(100000000, 999999999),
        ]));

        return response()->json([
            'message' => 'Animal registered successfully',
            'data' => $animal->load(['species', 'breed']),
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
            'current_weight_kg' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        $animal->update($validated);

        return response()->json([
            'message' => 'Animal updated successfully',
            'data' => $animal,
        ]);
    }
}
