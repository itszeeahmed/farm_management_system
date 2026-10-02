<?php

namespace App\Http\Controllers\Api\v1;

use App\Domain\Organization\Models\Farm;
use App\Domain\Organization\Models\Organization;
use App\Domain\Organization\Models\Permission;
use App\Domain\Organization\Models\Role;
use App\Domain\Organization\Models\UserFarmAccess;
use App\Http\Controllers\Controller;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class OnboardingController extends Controller
{
    /**
     * Self-service organization & farm onboarding for new agricultural enterprises.
     */
    public function register(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'org_name' => 'required|string|max:255',
            'farm_name' => 'required|string|max:255',
            'farm_type' => 'nullable|string|in:dairy,beef,goat_sheep,dairy_mixed,feedlot,pasture,poultry',
            'location' => 'required|string|max:255',
            'total_area' => 'nullable|numeric|min:0.1',
            'area_unit' => 'nullable|string|in:acres,hectares,sqm',
            'admin_name' => 'required|string|max:255',
            'admin_email' => 'required|email|max:255|unique:users,email',
            'password' => 'required|string|min:6',
        ]);

        return DB::transaction(function () use ($validated) {
            // 1. Create Organization Tenant
            $org = Organization::create([
                'name' => $validated['org_name'],
                'slug' => Str::slug($validated['org_name']).'-'.Str::lower(Str::random(4)),
                'status' => 'active',
                'subscription_tier' => 'enterprise',
                'country' => 'Pakistan',
            ]);

            // 2. Provision Enterprise Roles & Permissions
            $allPermissions = Permission::all();

            $ownerRole = Role::create([
                'organization_id' => $org->id,
                'name' => 'Farm Owner',
                'slug' => 'farm_owner',
                'description' => 'Enterprise administrator with unrestricted farm control',
                'is_system' => true,
            ]);
            $ownerRole->permissions()->attach($allPermissions->pluck('id'));

            $managerRole = Role::create([
                'organization_id' => $org->id,
                'name' => 'Herd Manager',
                'slug' => 'herd_manager',
                'description' => 'Operations, milking, feed rations and herd movement lead',
                'is_system' => true,
            ]);
            $managerPermissions = Permission::whereIn('name', [
                'animals.view', 'animals.create', 'animals.update', 'milk.record', 'feed.manage',
            ])->pluck('id');
            $managerRole->permissions()->attach($managerPermissions);

            $vetRole = Role::create([
                'organization_id' => $org->id,
                'name' => 'Consulting Veterinarian',
                'slug' => 'veterinarian',
                'description' => 'Clinical diagnosis, drug administration and biosecurity oversight',
                'is_system' => true,
            ]);
            $vetPermissions = Permission::whereIn('name', [
                'animals.view', 'health.diagnose', 'health.administer', 'audit.view',
            ])->pluck('id');
            $vetRole->permissions()->attach($vetPermissions);

            // 3. Create First Farm
            $cleanCodePrefix = strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $validated['farm_name']), 0, 3));
            if (strlen($cleanCodePrefix) < 3) {
                $cleanCodePrefix = 'FRM';
            }

            $farm = Farm::create([
                'organization_id' => $org->id,
                'name' => $validated['farm_name'],
                'code' => $cleanCodePrefix.'-01',
                'type' => $validated['farm_type'] ?? 'dairy_mixed',
                'location' => $validated['location'],
                'total_area' => $validated['total_area'] ?? 20.0,
                'area_unit' => $validated['area_unit'] ?? 'acres',
                'timezone' => 'Asia/Karachi',
                'climate_settings' => [
                    'cooling_fans_threshold_c' => 28,
                    'misting_thi_threshold' => 76,
                ],
            ]);

            // 4. Create Farm Owner User Account
            $user = User::create([
                'name' => $validated['admin_name'],
                'email' => $validated['admin_email'],
                'password' => Hash::make($validated['password']),
            ]);

            $preferences = $user->getOrCreatePreference();

            // 5. Grant Full Farm Owner Access
            UserFarmAccess::create([
                'user_id' => $user->id,
                'farm_id' => $farm->id,
                'role_id' => $ownerRole->id,
                'access_level' => 'full',
                'granted_at' => Carbon::now(),
                'is_active' => true,
                'reason' => 'Initial enterprise founder and proprietor',
            ]);

            // 6. Issue Authentication Token
            $token = $user->createToken('agritech-token')->plainTextToken;
            $permissions = $user->getFarmPermissions($farm);

            $farmData = [
                'id' => $farm->id,
                'name' => $farm->name,
                'code' => $farm->code,
                'type' => $farm->type,
                'location' => $farm->location,
                'organization_id' => $org->id,
                'organization_name' => $org->name,
                'animals_count' => 0,
                'role' => 'Farm Owner',
                'role_slug' => 'farm_owner',
                'access_level' => 'full',
            ];

            return response()->json([
                'message' => "Welcome to GreenPastures! {$org->name} has been provisioned successfully.",
                'token' => $token,
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => 'Farm Owner',
                    'role_slug' => 'farm_owner',
                    'permissions' => $permissions,
                ],
                'organization' => [
                    'id' => $org->id,
                    'name' => $org->name,
                    'slug' => $org->slug,
                ],
                'active_farm' => $farmData,
                'farms' => [$farmData],
                'preferences' => $preferences,
                'permissions' => $permissions,
            ], 201);
        });
    }
}
