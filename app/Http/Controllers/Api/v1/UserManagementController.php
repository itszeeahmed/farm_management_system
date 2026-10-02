<?php

namespace App\Http\Controllers\Api\v1;

use App\Domain\Organization\Models\Farm;
use App\Domain\Organization\Models\Role;
use App\Domain\Organization\Models\UserFarmAccess;
use App\Http\Controllers\Controller;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserManagementController extends Controller
{
    /**
     * List all team members with access to the active farm.
     */
    public function index(Request $request): JsonResponse
    {
        $farm = Farm::current();
        if (! $farm) {
            return response()->json(['message' => 'No active farm context found.'], 404);
        }

        $accesses = UserFarmAccess::where('farm_id', $farm->id)
            ->with(['user', 'role.permissions', 'grantor'])
            ->orderBy('is_active', 'desc')
            ->orderBy('created_at', 'asc')
            ->get()
            ->map(function (UserFarmAccess $access) {
                return [
                    'id' => $access->id,
                    'user_id' => $access->user_id,
                    'name' => $access->user?->name ?? 'Unknown',
                    'email' => $access->user?->email ?? '',
                    'role_id' => $access->role_id,
                    'role_name' => $access->role?->name ?? 'Custom Role',
                    'role_slug' => $access->role?->slug ?? 'custom',
                    'access_level' => $access->access_level,
                    'is_active' => $access->is_active,
                    'granted_at' => $access->granted_at?->toIso8601String(),
                    'expires_at' => $access->expires_at?->toIso8601String(),
                    'reason' => $access->reason,
                    'granted_by_name' => $access->grantor?->name ?? 'System',
                    'permissions' => $access->role?->permissions->pluck('name') ?? [],
                ];
            });

        // Available roles for assignment
        $roles = Role::whereNull('organization_id')
            ->orWhere('organization_id', $farm->organization_id)
            ->with('permissions')
            ->get()
            ->map(fn (Role $r) => [
                'id' => $r->id,
                'name' => $r->name,
                'slug' => $r->slug,
                'description' => $r->description,
                'permissions' => $r->permissions->pluck('name'),
            ]);

        return response()->json([
            'farm_id' => $farm->id,
            'farm_name' => $farm->name,
            'team_count' => $accesses->count(),
            'team' => $accesses,
            'available_roles' => $roles,
        ]);
    }

    /**
     * Invite or assign a user to the active farm.
     */
    public function invite(Request $request): JsonResponse
    {
        $farm = Farm::current();
        if (! $farm) {
            return response()->json(['message' => 'No active farm context found.'], 404);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'role_id' => 'required|exists:roles,id',
            'access_level' => 'nullable|string|in:full,operational,veterinary_only,read_only',
            'expires_at' => 'nullable|date',
            'reason' => 'nullable|string|max:500',
            'password' => 'nullable|string|min:6',
        ]);

        $currentUser = $request->user();

        // Find or create target user
        $targetUser = User::where('email', $validated['email'])->first();
        $isNewUser = false;

        if (! $targetUser) {
            $isNewUser = true;
            $password = $validated['password'] ?? Str::random(12);
            $targetUser = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($password),
            ]);
            $targetUser->getOrCreatePreference();
        }

        // Check if access grant already exists
        $access = UserFarmAccess::where('farm_id', $farm->id)
            ->where('user_id', $targetUser->id)
            ->first();

        if ($access) {
            $access->update([
                'role_id' => $validated['role_id'],
                'access_level' => $validated['access_level'] ?? 'operational',
                'granted_by' => $currentUser?->id,
                'granted_at' => Carbon::now(),
                'expires_at' => ! empty($validated['expires_at']) ? Carbon::parse($validated['expires_at']) : null,
                'is_active' => true,
                'reason' => $validated['reason'] ?? 'Access updated by farm administrator',
            ]);
        } else {
            $access = UserFarmAccess::create([
                'user_id' => $targetUser->id,
                'farm_id' => $farm->id,
                'role_id' => $validated['role_id'],
                'access_level' => $validated['access_level'] ?? 'operational',
                'granted_by' => $currentUser?->id,
                'granted_at' => Carbon::now(),
                'expires_at' => ! empty($validated['expires_at']) ? Carbon::parse($validated['expires_at']) : null,
                'is_active' => true,
                'reason' => $validated['reason'] ?? 'Farm team member assignment',
            ]);
        }

        $access->load(['user', 'role.permissions']);

        return response()->json([
            'message' => $isNewUser ? 'User created and access granted successfully.' : 'Farm access granted successfully.',
            'access' => [
                'id' => $access->id,
                'user_id' => $access->user_id,
                'name' => $access->user->name,
                'email' => $access->user->email,
                'role_name' => $access->role?->name,
                'role_slug' => $access->role?->slug,
                'access_level' => $access->access_level,
                'is_active' => $access->is_active,
                'granted_at' => $access->granted_at?->toIso8601String(),
                'expires_at' => $access->expires_at?->toIso8601String(),
                'reason' => $access->reason,
            ],
        ], 201);
    }

    /**
     * Update access grant for a team member.
     */
    public function updateRole(Request $request, int $id): JsonResponse
    {
        $farm = Farm::current();
        $access = UserFarmAccess::where('farm_id', $farm->id)->findOrFail($id);

        $validated = $request->validate([
            'role_id' => 'sometimes|required|exists:roles,id',
            'access_level' => 'sometimes|required|in:full,operational,veterinary_only,read_only',
            'is_active' => 'sometimes|boolean',
            'expires_at' => 'nullable|date',
            'reason' => 'nullable|string',
        ]);

        if (isset($validated['expires_at'])) {
            $validated['expires_at'] = $validated['expires_at'] ? Carbon::parse($validated['expires_at']) : null;
        }

        $access->update($validated);
        $access->load(['user', 'role']);

        return response()->json([
            'message' => 'Team member access updated successfully.',
            'access' => $access,
        ]);
    }

    /**
     * Revoke access for a team member on this farm.
     */
    public function revoke(Request $request, int $id): JsonResponse
    {
        $farm = Farm::current();
        $access = UserFarmAccess::where('farm_id', $farm->id)->findOrFail($id);

        $access->update([
            'is_active' => false,
            'reason' => 'Access revoked by farm owner/administrator',
        ]);

        return response()->json([
            'message' => 'Team member access revoked successfully.',
        ]);
    }
}
