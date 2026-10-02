<?php

namespace Tests\Feature;

use App\Domain\Animals\Models\Animal;
use App\Domain\Animals\Models\Species;
use App\Domain\Animals\Services\AnimalLifecycleStateMachine;
use App\Domain\Organization\Models\Farm;
use Carbon\Carbon;
use Database\Seeders\PilotFarmSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class PhaseOneCoreFoundationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PilotFarmSeeder::class);
    }

    public function test_farm_hierarchy_and_zones_structure_api(): void
    {
        $response = $this->getJson('/api/v1/hierarchy');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'farm' => [
                    'id',
                    'name',
                    'code',
                    'type',
                    'elevation_meters',
                    'soil_type',
                    'total_area',
                    'water_sources',
                    'boundary_geojson',
                ],
                'zones' => [
                    '*' => ['id', 'name', 'code', 'type', 'area_size', 'status'],
                ],
                'structures' => [
                    '*' => ['id', 'name', 'code', 'structure_type', 'capacity', 'current_headcount', 'stocking_rate_percent'],
                ],
            ]);
    }

    public function test_rbac_roles_permissions_and_delegated_access(): void
    {
        $response = $this->getJson('/api/v1/roles');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'roles' => [
                    '*' => ['id', 'name', 'slug', 'is_system', 'permissions'],
                ],
                'permissions_by_category',
                'active_user_farm_accesses' => [
                    '*' => ['id', 'user_id', 'farm_id', 'role_id', 'access_level', 'user', 'role'],
                ],
            ]);

        $roles = $response->json('roles');
        $slugs = array_column($roles, 'slug');

        $this->assertContains('farm_owner', $slugs);
        $this->assertContains('veterinarian', $slugs);
        $this->assertContains('herd_manager', $slugs);
    }

    public function test_animal_pedigree_and_inbreeding_calculation(): void
    {
        $animal = Animal::first();
        $this->assertNotNull($animal);

        $response = $this->getJson("/api/v1/animals/{$animal->id}/pedigree");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'animal_id',
                'tag_number',
                'inbreeding_coefficient',
                'pedigree_record',
                'lineage_tree' => ['id', 'tag_number', 'name', 'sex', 'depth'],
            ]);
    }

    public function test_record_animal_weight_and_adg_calculation(): void
    {
        $animal = Animal::first();
        $this->assertNotNull($animal);

        $targetDate = Carbon::now()->addDays(5)->toDateString();
        $newWeight = (float) $animal->current_weight_kg + 3.5;

        $response = $this->postJson("/api/v1/animals/{$animal->id}/weights", [
            'weight_kg' => $newWeight,
            'recorded_at' => $targetDate,
            'weighing_method' => 'scale',
            'notes' => 'Test weight record',
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'message',
                'data' => [
                    'id',
                    'animal_id',
                    'weight_kg',
                    'average_daily_gain_kg',
                    'days_since_previous',
                ],
                'current_weight_kg',
            ]);

        $this->assertEquals($newWeight, (float) $animal->fresh()->current_weight_kg);
    }

    public function test_record_animal_bcs_and_locomotion(): void
    {
        $animal = Animal::first();
        $this->assertNotNull($animal);

        $response = $this->postJson("/api/v1/animals/{$animal->id}/bcs", [
            'bcs_score' => 3.5,
            'locomotion_score' => 1,
            'rumen_fill_score' => 4,
            'cleanliness_score' => 1,
            'assessed_at' => Carbon::now()->toDateString(),
            'notes' => 'Regular veterinary scoring',
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'message',
                'data' => [
                    'id',
                    'animal_id',
                    'bcs_score',
                    'locomotion_score',
                    'rumen_fill_score',
                ],
            ]);
    }

    public function test_animal_lifecycle_state_machine_transition(): void
    {
        $stateMachine = new AnimalLifecycleStateMachine;

        // Find or create a heifer
        $farm = Farm::first();
        $cattleSpecies = Species::where('code', 'cattle')->first();

        $animal = Animal::create([
            'organization_id' => $farm->organization_id,
            'farm_id' => $farm->id,
            'species_id' => $cattleSpecies->id,
            'tag_number' => 'TEST-COW-'.rand(1000, 9999),
            'sex' => 'female',
            'status' => 'active',
            'lifecycle_stage' => 'heifer',
        ]);

        // Valid transition: heifer -> pregnant_heifer
        $this->assertTrue($stateMachine->canTransition($animal, 'pregnant_heifer'));
        $stateMachine->transition($animal, 'pregnant_heifer', reason: 'Confirmed AI conception');
        $this->assertEquals('pregnant_heifer', $animal->fresh()->lifecycle_stage);

        // Valid transition: pregnant_heifer -> lactating
        $this->assertTrue($stateMachine->canTransition($animal, 'lactating'));
        $stateMachine->transition($animal, 'lactating', reason: 'First calving');
        $this->assertEquals('lactating', $animal->fresh()->lifecycle_stage);
        $this->assertEquals('lactating', $animal->fresh()->status);

        // Invalid transition: lactating cannot go back to calf
        $this->assertFalse($stateMachine->canTransition($animal, 'calf'));
        $this->expectException(ValidationException::class);
        $stateMachine->transition($animal, 'calf');
    }

    public function test_animal_groups_cohort_api(): void
    {
        $response = $this->getJson('/api/v1/animal-groups');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'code',
                        'group_type',
                        'current_members_count',
                        'current_members',
                    ],
                ],
            ]);
    }

    public function test_compliance_audit_logs_endpoint(): void
    {
        $response = $this->getJson('/api/v1/audit-logs');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'current_page',
                'data' => [
                    '*' => ['id', 'action', 'auditable_type', 'auditable_id', 'created_at'],
                ],
            ]);
    }
}
