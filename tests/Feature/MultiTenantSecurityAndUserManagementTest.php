<?php

namespace Tests\Feature;

use App\Domain\Animals\Models\Animal;
use App\Domain\Organization\Models\Farm;
use App\Domain\Organization\Models\Organization;
use App\Domain\Organization\Models\Role;
use App\Models\User;
use Database\Seeders\PilotFarmSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MultiTenantSecurityAndUserManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PilotFarmSeeder::class);
    }

    public function test_auth_me_returns_dynamic_permission_slugs(): void
    {
        $owner = User::where('email', 'owner@farm.local')->first();
        $this->assertNotNull($owner);

        $response = $this->actingAs($owner, 'sanctum')->getJson('/api/v1/auth/me');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'user' => ['id', 'name', 'email', 'role', 'role_slug', 'permissions'],
                'permissions',
                'farms',
                'active_farm',
            ]);

        $permissions = $response->json('permissions');
        $this->assertIsArray($permissions);
        $this->assertContains('animals.delete', $permissions);
        $this->assertContains('animals.create', $permissions);
        $this->assertContains('milk.record', $permissions);
    }

    public function test_farm_switcher_only_returns_accessible_farms(): void
    {
        $vet = User::where('email', 'vet@farm.local')->first();
        $this->assertNotNull($vet);

        // Create a second farm belonging to a completely different organization
        $otherOrg = Organization::create([
            'name' => 'Foreign Agro Corp',
            'slug' => 'foreign-agro',
            'status' => 'active',
        ]);
        $otherFarm = Farm::create([
            'organization_id' => $otherOrg->id,
            'name' => 'Secret Highland Ranch',
            'code' => 'SHR-01',
            'type' => 'beef',
            'location' => 'Chitral, KPK',
        ]);

        // When vet calls /api/v1/farms, they must NOT see otherFarm
        $response = $this->actingAs($vet, 'sanctum')->getJson('/api/v1/farms');

        $response->assertStatus(200);
        $farmIds = collect($response->json('data'))->pluck('id')->toArray();

        $this->assertNotContains($otherFarm->id, $farmIds);
        $this->assertContains(1, $farmIds); // Pilot farm 1
    }

    public function test_resolve_farm_middleware_rejects_unauthorized_x_farm_id(): void
    {
        $vet = User::where('email', 'vet@farm.local')->first();

        // Create another farm that vet has NO access to
        $otherOrg = Organization::create([
            'name' => 'Foreign Agro Corp',
            'slug' => 'foreign-agro-2',
            'status' => 'active',
        ]);
        $otherFarm = Farm::create([
            'organization_id' => $otherOrg->id,
            'name' => 'Unauthorized Farm',
            'code' => 'UNF-01',
            'type' => 'dairy_mixed',
            'location' => 'Islamabad',
        ]);

        // Attempting to make an API request with X-Farm-Id of unauthorized farm must return 403 Forbidden
        $response = $this->actingAs($vet, 'sanctum')
            ->withHeader('X-Farm-Id', (string) $otherFarm->id)
            ->getJson('/api/v1/animals');

        $response->assertStatus(403)
            ->assertJson(['message' => 'Unauthorized: You do not have access to this farm.']);
    }

    public function test_veterinarian_cannot_delete_animals_permission_enforcement(): void
    {
        $vet = User::where('email', 'vet@farm.local')->first();
        $animal = Animal::first();
        $this->assertNotNull($animal);

        // Vet tries to delete an animal
        $response = $this->actingAs($vet, 'sanctum')
            ->deleteJson("/api/v1/animals/{$animal->id}");

        $response->assertStatus(403)
            ->assertJsonPath('message', "Access denied: You do not have the required permission 'animals.delete' on this farm.");

        // Animal must still exist in the database
        $this->assertDatabaseHas('animals', ['id' => $animal->id]);
    }

    public function test_farm_owner_can_delete_animal(): void
    {
        $owner = User::where('email', 'owner@farm.local')->first();
        $animal = Animal::first();
        $this->assertNotNull($animal);

        $response = $this->actingAs($owner, 'sanctum')
            ->deleteJson("/api/v1/animals/{$animal->id}");

        $response->assertStatus(200)
            ->assertJson(['message' => 'Animal successfully culled or removed from herd record.']);

        $this->assertSoftDeleted('animals', ['id' => $animal->id]);
    }

    public function test_saas_org_and_farm_self_registration(): void
    {
        $payload = [
            'org_name' => 'Sunrise Dairy Holdings',
            'farm_name' => 'Sunrise Green Meadow',
            'farm_type' => 'dairy_mixed',
            'location' => 'Faisalabad, Punjab',
            'total_area' => 45.5,
            'area_unit' => 'acres',
            'admin_name' => 'Tariq Munir',
            'admin_email' => 'tariq@sunrisedairy.com',
            'password' => 'SecurePass#2026',
        ];

        $response = $this->postJson('/api/v1/onboarding/register', $payload);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'message',
                'token',
                'user' => ['id', 'name', 'email', 'role', 'role_slug', 'permissions'],
                'organization' => ['id', 'name', 'slug'],
                'active_farm' => ['id', 'name', 'code', 'type'],
                'preferences',
                'permissions',
            ]);

        $this->assertDatabaseHas('organizations', ['name' => 'Sunrise Dairy Holdings']);
        $this->assertDatabaseHas('farms', ['name' => 'Sunrise Green Meadow']);
        $this->assertDatabaseHas('users', ['email' => 'tariq@sunrisedairy.com']);
    }

    public function test_user_management_team_list_invite_and_revoke(): void
    {
        $owner = User::where('email', 'owner@farm.local')->first();
        $managerRole = Role::where('slug', 'herd_manager')->first();

        // 1. List team
        $listResponse = $this->actingAs($owner, 'sanctum')->getJson('/api/v1/team');
        $listResponse->assertStatus(200)
            ->assertJsonStructure([
                'farm_id',
                'farm_name',
                'team_count',
                'team' => [
                    '*' => ['id', 'user_id', 'name', 'email', 'role_name', 'access_level', 'is_active'],
                ],
                'available_roles',
            ]);

        // 2. Invite a new team member
        $inviteResponse = $this->actingAs($owner, 'sanctum')->postJson('/api/v1/team/invite', [
            'name' => 'Ali Raza Specialist',
            'email' => 'aliraza@farm.local',
            'role_id' => $managerRole->id,
            'access_level' => 'operational',
            'reason' => 'New Milking Unit Supervisor',
        ]);

        $inviteResponse->assertStatus(201)
            ->assertJsonPath('access.email', 'aliraza@farm.local')
            ->assertJsonPath('access.role_slug', 'herd_manager');

        $this->assertDatabaseHas('users', ['email' => 'aliraza@farm.local']);
        $accessId = $inviteResponse->json('access.id');

        // 3. Revoke access
        $revokeResponse = $this->actingAs($owner, 'sanctum')->deleteJson("/api/v1/team/{$accessId}");
        $revokeResponse->assertStatus(200)
            ->assertJson(['message' => 'Team member access revoked successfully.']);

        $this->assertDatabaseHas('user_farm_access', [
            'id' => $accessId,
            'is_active' => false,
        ]);
    }
}
