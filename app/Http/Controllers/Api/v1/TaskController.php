<?php

namespace App\Http\Controllers\Api\v1;

use App\Domain\Organization\Models\Farm;
use App\Domain\Workforce\Models\FarmTask;
use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TaskController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $farm = Farm::first();
        if (! $farm) {
            return response()->json(['tasks' => []]);
        }

        $query = FarmTask::where('farm_id', $farm->id)
            ->with(['animal', 'assignee']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('priority')) {
            $query->where('priority', $request->priority);
        }

        $tasks = $query->orderBy('due_date')->get();

        return response()->json(['data' => $tasks]);
    }

    public function updateStatus(Request $request, int $id): JsonResponse
    {
        $task = FarmTask::findOrFail($id);

        $validated = $request->validate([
            'status' => 'required|in:pending,in_progress,completed,cancelled',
            'completion_notes' => 'nullable|string',
        ]);

        $task->update([
            'status' => $validated['status'],
            'completion_notes' => $validated['completion_notes'] ?? $task->completion_notes,
            'completed_at' => $validated['status'] === 'completed' ? Carbon::now() : null,
        ]);

        return response()->json([
            'message' => 'Task status updated',
            'data' => $task,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $farm = Farm::first();
        if (! $farm) {
            return response()->json(['message' => 'Farm not configured'], 422);
        }

        $validated = $request->validate([
            'title' => 'required|string|max:150',
            'description' => 'nullable|string',
            'category' => 'required|string',
            'priority' => 'required|in:low,medium,high,urgent',
            'due_date' => 'required|date',
            'due_time' => 'nullable',
            'animal_id' => 'nullable|exists:animals,id',
            'assigned_to' => 'nullable|exists:users,id',
        ]);

        $task = FarmTask::create(array_merge($validated, [
            'farm_id' => $farm->id,
            'status' => 'pending',
        ]));

        return response()->json([
            'message' => 'Task created successfully',
            'data' => $task,
        ], 201);
    }
}
