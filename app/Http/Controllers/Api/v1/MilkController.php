<?php

namespace App\Http\Controllers\Api\v1;

use App\Domain\Animals\Models\Animal;
use App\Domain\Milk\Models\MilkRecord;
use App\Domain\Organization\Models\Farm;
use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MilkController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $farm = Farm::first();
        if (! $farm) {
            return response()->json(['data' => []]);
        }

        $query = MilkRecord::where('farm_id', $farm->id)
            ->with(['animal.species', 'animal.breed']);

        if ($request->filled('date')) {
            $query->where('recorded_date', $request->date);
        }

        if ($request->filled('animal_id')) {
            $query->where('animal_id', $request->animal_id);
        }

        if ($request->filled('shift')) {
            $query->where('shift', $request->shift);
        }

        $records = $query->latest('recorded_date')->latest('id')->paginate(30);

        return response()->json($records);
    }

    public function storeRecord(Request $request): JsonResponse
    {
        $farm = Farm::first();
        if (! $farm) {
            return response()->json(['message' => 'Farm not found'], 422);
        }

        $validated = $request->validate([
            'animal_id' => 'required|exists:animals,id',
            'recorded_date' => 'required|date',
            'shift' => 'required|in:morning,afternoon,evening',
            'yield_liters' => 'required|numeric|min:0.1',
            'fat_percentage' => 'nullable|numeric|between:1,12',
            'snf_percentage' => 'nullable|numeric|between:5,15',
            'protein_percentage' => 'nullable|numeric|between:2,6',
            'scc' => 'nullable|integer|min:0',
            'temperature_c' => 'nullable|numeric',
            'operator_notes' => 'nullable|string',
        ]);

        $animal = Animal::findOrFail($validated['animal_id']);

        // WITHDRAWAL CONTROL CHECK: (HLT-013, HLT-018, MLK-016)
        $isUnderWithdrawal = $animal->treatments()
            ->where('milk_withdrawal_until', '>', Carbon::now())
            ->exists();

        $qualityStatus = $isUnderWithdrawal ? 'discarded_withdrawal' : 'standard';
        $warning = null;

        if ($isUnderWithdrawal) {
            $warning = "WARNING: Animal {$animal->tag_number} is under active veterinary milk withdrawal! Milk must NOT be pooled or sold. Status marked as discarded_withdrawal.";
        }

        $record = MilkRecord::create(array_merge($validated, [
            'farm_id' => $farm->id,
            'quality_status' => $qualityStatus,
        ]));

        return response()->json([
            'message' => 'Milk record saved successfully',
            'withdrawal_alert' => $warning,
            'data' => $record->load('animal'),
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
            ->where('quality_status', '!=', 'discarded_withdrawal')
            ->whereHas('animal.species', fn ($q) => $q->where('code', 'cattle'))
            ->sum('yield_liters');

        $cowEvening = MilkRecord::where('farm_id', $farm->id)
            ->where('recorded_date', $today)
            ->where('shift', 'evening')
            ->where('quality_status', '!=', 'discarded_withdrawal')
            ->whereHas('animal.species', fn ($q) => $q->where('code', 'cattle'))
            ->sum('yield_liters');

        $goatMorning = MilkRecord::where('farm_id', $farm->id)
            ->where('recorded_date', $today)
            ->where('shift', 'morning')
            ->where('quality_status', '!=', 'discarded_withdrawal')
            ->whereHas('animal.species', fn ($q) => $q->where('code', 'goat'))
            ->sum('yield_liters');

        $goatEvening = MilkRecord::where('farm_id', $farm->id)
            ->where('recorded_date', $today)
            ->where('shift', 'evening')
            ->where('quality_status', '!=', 'discarded_withdrawal')
            ->whereHas('animal.species', fn ($q) => $q->where('code', 'goat'))
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
            'grand_total' => round($cowMorning + $cowEvening + $goatMorning + $goatEvening, 1),
        ]);
    }
}
