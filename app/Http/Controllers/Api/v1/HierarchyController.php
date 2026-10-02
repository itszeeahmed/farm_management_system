<?php

namespace App\Http\Controllers\Api\v1;

use App\Domain\Animals\Models\AnimalGroup;
use App\Domain\Organization\Models\AuditLog;
use App\Domain\Organization\Models\Farm;
use App\Domain\Organization\Models\FarmStructure;
use App\Domain\Organization\Models\FarmZone;
use App\Domain\Organization\Models\Permission;
use App\Domain\Organization\Models\Role;
use App\Domain\Organization\Models\UserFarmAccess;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class HierarchyController extends Controller
{
    /**
     * Get farm zones and hierarchical structures with stocking densities.
     */
    public function index(Request $request): JsonResponse
    {
        $farm = Farm::with(['organization'])->first();
        if (! $farm) {
            return response()->json(['message' => 'Farm not configured'], 404);
        }

        $zones = FarmZone::where('farm_id', $farm->id)
            ->withCount('animals')
            ->get();

        $structures = FarmStructure::where('farm_id', $farm->id)
            ->with(['zone', 'parentStructure', 'subStructures'])
            ->withCount('animals')
            ->get()
            ->map(function ($struct) {
                return [
                    'id' => $struct->id,
                    'name' => $struct->name,
                    'code' => $struct->code,
                    'structure_type' => $struct->structure_type,
                    'target_species' => $struct->target_species,
                    'capacity' => $struct->capacity,
                    'current_headcount' => $struct->animals_count,
                    'stocking_rate_percent' => $struct->capacity > 0 ? round(($struct->animals_count / $struct->capacity) * 100, 1) : 0,
                    'area_sq_meters' => $struct->area_sq_meters,
                    'ventilation_type' => $struct->ventilation_type,
                    'has_automated_feeders' => $struct->has_automated_feeders,
                    'has_automated_waterers' => $struct->has_automated_waterers,
                    'has_misting_cooling' => $struct->has_misting_cooling,
                    'zone' => $struct->zone ? ['id' => $struct->zone->id, 'name' => $struct->zone->name, 'type' => $struct->zone->type] : null,
                    'parent_structure_id' => $struct->parent_structure_id,
                ];
            });

        return response()->json([
            'farm' => [
                'id' => $farm->id,
                'name' => $farm->name,
                'code' => $farm->code,
                'type' => $farm->type,
                'elevation_meters' => $farm->elevation_meters,
                'soil_type' => $farm->soil_type,
                'total_area' => $farm->total_area,
                'water_sources' => $farm->water_sources,
                'boundary_geojson' => $farm->boundary_geojson,
            ],
            'zones' => $zones,
            'structures' => $structures,
        ]);
    }

    /**
     * Get RBAC roles, permissions, and active delegated user accesses.
     */
    public function roles(): JsonResponse
    {
        $roles = Role::with('permissions')->get();
        $permissions = Permission::all()->groupBy('category');
        $accesses = UserFarmAccess::with(['user', 'role', 'grantor'])
            ->active()
            ->get();

        return response()->json([
            'roles' => $roles,
            'permissions_by_category' => $permissions,
            'active_user_farm_accesses' => $accesses,
        ]);
    }

    /**
     * Get animal management groups and active cohorts.
     */
    public function groups(): JsonResponse
    {
        $farm = Farm::first();
        if (! $farm) {
            return response()->json(['data' => []]);
        }

        $groups = AnimalGroup::where('farm_id', $farm->id)
            ->with(['currentMembers.species', 'currentMembers.breed'])
            ->withCount('currentMembers')
            ->get();

        return response()->json([
            'data' => $groups,
        ]);
    }

    /**
     * Get regulatory compliance audit trail logs.
     */
    public function auditLogs(Request $request): JsonResponse
    {
        $limit = min(100, max(10, (int) $request->input('limit', 25)));

        $logs = AuditLog::with(['user'])
            ->orderByDesc('created_at')
            ->paginate($limit);

        return response()->json($logs);
    }
}
