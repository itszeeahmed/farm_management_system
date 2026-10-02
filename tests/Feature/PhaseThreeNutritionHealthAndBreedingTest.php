<?php

namespace Tests\Feature;

use App\Domain\Animals\Models\Animal;
use App\Domain\Breeding\Models\SemenStrawInventory;
use App\Domain\Feed\Models\FeedFormulation;
use App\Domain\Feed\Models\FeedItem;
use App\Domain\Feed\Services\DryMatterIntakeCalculator;
use App\Domain\Health\Models\Medicine;
use App\Domain\Organization\Models\Pen;
use Carbon\Carbon;
use Database\Seeders\PilotFarmSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PhaseThreeNutritionHealthAndBreedingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PilotFarmSeeder::class);
    }

    public function test_nrc_dry_matter_intake_prediction(): void
    {
        $dmiCalc = new DryMatterIntakeCalculator;

        // 1. Dairy Cattle: BW 620kg, Milk 28kg, Fat 3.8%, DIM 90
        $cowDmi = $dmiCalc->predictDairyCattleDmi(620.0, 28.0, 3.8, 90);
        $this->assertGreaterThan(20.0, $cowDmi['predicted_dmi_kg']);
        $this->assertLessThan(27.0, $cowDmi['predicted_dmi_kg']);
        $this->assertGreaterThan(25.0, $cowDmi['fcm_4_percent_kg']);
        $this->assertGreaterThan(3.0, $cowDmi['dmi_percent_body_weight']);

        // 2. Small Ruminant (Dairy Goat): BW 55kg, Milk 3.0kg
        $goatDmi = $dmiCalc->predictSmallRuminantDmi(55.0, 3.0);
        $this->assertGreaterThan(1.8, $goatDmi['predicted_dmi_kg']);
        $this->assertLessThan(3.0, $goatDmi['predicted_dmi_kg']);

        // 3. API Test
        $response = $this->postJson('/api/v1/feeds/predict-dmi', [
            'species_type' => 'cattle',
            'body_weight_kg' => 620.0,
            'milk_yield_kg' => 28.0,
            'fat_percentage' => 3.8,
            'days_in_milk' => 90,
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'data' => [
                    'predicted_dmi_kg',
                    'fcm_4_percent_kg',
                    'dmi_percent_body_weight',
                ],
            ]);
    }

    public function test_ration_formulation_balancing_and_cost_per_liter(): void
    {
        $silage = FeedItem::where('code', 'SIL-01')->firstOrFail();
        $alfalfa = FeedItem::where('code', 'ALF-01')->firstOrFail();
        $wanta = FeedItem::where('code', 'WNT-18')->firstOrFail();

        $payload = [
            'name' => 'Mid-Lactation Balanced TMR',
            'code' => 'TMR-MID-2026',
            'species_type' => 'cattle',
            'target_stage' => 'lactating_mid',
            'target_dmi_kg' => 21.00,
            'ingredients' => [
                ['feed_item_id' => $silage->id, 'inclusion_kg_as_fed' => 26.0],
                ['feed_item_id' => $alfalfa->id, 'inclusion_kg_as_fed' => 3.5],
                ['feed_item_id' => $wanta->id, 'inclusion_kg_as_fed' => 7.0],
            ],
            'notes' => 'Tested for 20L daily yield.',
        ];

        $response = $this->postJson('/api/v1/feeds/formulations', $payload);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'message',
                'nutritional_evaluation' => [
                    'total_as_fed_kg',
                    'total_dry_matter_kg',
                    'crude_protein_percent',
                    'ndf_percent',
                    'adf_percent',
                    'nel_mcal_total',
                    'nel_mcal_per_kg_dm',
                    'total_cost_per_head_day',
                    'feed_cost_per_liter',
                ],
                'data' => [
                    'id',
                    'name',
                    'calculated_cp_percent',
                    'calculated_nel_mcal',
                    'calculated_cost_per_head_day',
                ],
            ]);

        $this->assertDatabaseHas('feed_formulations', [
            'code' => 'TMR-MID-2026',
            'species_type' => 'cattle',
        ]);
    }

    public function test_tmr_mixer_wagon_batch_production_and_inventory_depletion(): void
    {
        $formulation = FeedFormulation::where('code', 'TMR-COW-HIGH')->firstOrFail();
        $pen = Pen::firstOrFail();
        $silage = FeedItem::where('code', 'SIL-01')->firstOrFail();
        $initialSilageStock = (float) $silage->current_stock;

        $payload = [
            'feed_formulation_id' => $formulation->id,
            'pen_id' => $pen->id,
            'headcount' => 30,
            'actual_weight_kg' => 1250.00,
            'mixer_wagon_id' => 'Kuhn Knight Reel Auggie',
            'mixing_duration_minutes' => 16,
        ];

        $response = $this->postJson('/api/v1/feeds/tmr-batches', $payload);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'message',
                'data' => [
                    'id',
                    'batch_number',
                    'planned_weight_kg',
                    'actual_weight_kg',
                    'deviation_percent',
                    'status',
                ],
            ]);

        $silage->refresh();
        $this->assertLessThan($initialSilageStock, (float) $silage->current_stock);

        $this->assertDatabaseHas('tmr_batches', [
            'feed_formulation_id' => $formulation->id,
            'mixer_wagon_id' => 'Kuhn Knight Reel Auggie',
        ]);
    }

    public function test_feed_bunk_scoring_and_silage_bunkers_api(): void
    {
        $pen = Pen::firstOrFail();

        // 1. Post bunk score
        $scoreResp = $this->postJson('/api/v1/feeds/bunk-scores', [
            'pen_id' => $pen->id,
            'assessed_at' => Carbon::now()->toDateTimeString(),
            'score' => 2,
            'refusal_estimated_kg' => 15.0,
            'adjustment_action' => 'decrease_5_percent',
            'notes' => 'High refusal in morning check due to humid weather.',
        ]);

        $scoreResp->assertStatus(201)
            ->assertJson([
                'data' => [
                    'score' => 2,
                    'adjustment_action' => 'decrease_5_percent',
                ],
            ]);

        // 2. Get bunk scores
        $getScores = $this->getJson('/api/v1/feeds/bunk-scores');
        $getScores->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'pen_id', 'score', 'adjustment_action'],
                ],
            ]);

        // 3. Silage bunkers
        $bunkerResp = $this->getJson('/api/v1/feeds/silage-bunkers');
        $bunkerResp->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'bunker_code', 'crop_type', 'ph_level', 'compaction_density_kg_m3'],
                ],
            ]);
    }

    public function test_veterinary_clinical_soap_case_recording(): void
    {
        $cow = Animal::where('tag_number', 'PK-COW-002')->firstOrFail();

        $payload = [
            'animal_id' => $cow->id,
            'diagnosis' => 'Acute Ruminal Acidosis (Sub-acute SARA)',
            'symptom_observed_at' => Carbon::now()->toDateString(),
            'symptoms_description' => 'Drop in milk yield, loose bubbly feces with mucin casts, lethargy.',
            'subjective_notes' => 'Animal sluggish at feed alley, partial grain refusal.',
            'objective_temp_c' => 38.9,
            'objective_heart_rate' => 82,
            'objective_respiration_rate' => 30,
            'objective_rumen_motility_per_2min' => 1, // Hypomotility
            'assessment_notes' => 'Rumen acidosis caused by excess rapidly fermentable starch.',
            'plan_notes' => 'Drench with 500g Sodium Bicarbonate + Magnesium Oxide buffer. Provide dry long-stem alfalfa hay.',
            'severity' => 'severe',
            'attending_vet_name' => 'Dr. Asim Raza',
        ];

        $response = $this->postJson('/api/v1/health/cases', $payload);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'message',
                'data' => [
                    'id',
                    'case_number',
                    'diagnosis',
                    'objective_temp_c',
                    'objective_rumen_motility_per_2min',
                ],
            ]);

        $cow->refresh();
        $this->assertEquals('sick', $cow->status);

        $this->assertDatabaseHas('health_cases', [
            'animal_id' => $cow->id,
            'objective_heart_rate' => 82,
            'objective_rumen_motility_per_2min' => 1,
        ]);
    }

    public function test_antimicrobial_usage_ddda_tracking_and_who_cia_restriction(): void
    {
        $cow = Animal::where('tag_number', 'PK-COW-003')->firstOrFail();
        $cia = Medicine::where('who_classification', 'critically_important')->firstOrFail();
        $amox = Medicine::where('name', 'like', '%Amoxicillin%')->firstOrFail();

        // 1. CIA without prescription/license number MUST FAIL with 422
        $failResp = $this->postJson('/api/v1/health/treatments', [
            'animal_id' => $cow->id,
            'medicine_id' => $cia->id,
            'dosage' => 10.0,
            'dosage_unit' => 'ml',
            'administered_by' => 'Lay Assistant',
        ]);

        $failResp->assertStatus(422)
            ->assertJsonValidationErrors(['medicine_id']);

        // 2. CIA WITH prescription and vet license passes
        $passResp = $this->postJson('/api/v1/health/treatments', [
            'animal_id' => $cow->id,
            'medicine_id' => $cia->id,
            'dosage' => 10.0,
            'dosage_unit' => 'ml',
            'veterinarian_license_number' => 'PVMC-88129-B',
            'prescription_number' => 'RX-CIA-99120',
            'batch_lot_number' => 'CEF-2026-X1',
            'active_substance_administered_mg' => 500.0,
            'administered_by' => 'Dr. Asim Raza',
            'cost' => 380.00,
        ]);

        $passResp->assertStatus(201)
            ->assertJsonStructure([
                'message',
                'antimicrobial_warning',
                'ddda_units_consumed',
                'data',
            ]);

        $this->assertGreaterThan(0, $passResp->json('ddda_units_consumed'));

        // 3. AMU Summary API
        $amuResp = $this->getJson('/api/v1/health/amu-summary');
        $amuResp->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'total_treatments',
                    'total_antimicrobial_treatments',
                    'total_ddda_consumed',
                    'critically_important_antimicrobial_treatments',
                    'stewardship_status',
                ],
            ]);
    }

    public function test_biosecurity_audits_api(): void
    {
        $payload = [
            'audit_date' => Carbon::now()->toDateString(),
            'auditor_name' => 'Dr. Sajjad Haider (Livestock Epidemiologist)',
            'visitor_log_compliance_score' => 96,
            'footbath_disinfection_score' => 90,
            'quarantine_compliance_score' => 100,
            'carcass_disposal_compliance_score' => 95,
            'overall_risk_rating' => 'low_risk',
            'corrective_actions' => 'Ensure chemical concentration testing strips used daily for vehicle disinfection trench.',
        ];

        $response = $this->postJson('/api/v1/health/biosecurity-audits', $payload);

        $response->assertStatus(201)
            ->assertJson([
                'data' => [
                    'auditor_name' => 'Dr. Sajjad Haider (Livestock Epidemiologist)',
                    'overall_risk_rating' => 'low_risk',
                ],
            ]);

        $this->assertDatabaseHas('biosecurity_audits', [
            'auditor_name' => 'Dr. Sajjad Haider (Livestock Epidemiologist)',
        ]);
    }

    public function test_semen_straw_inventory_and_artificial_insemination(): void
    {
        $cow = Animal::where('tag_number', 'PK-COW-004')->firstOrFail();
        $straw = SemenStrawInventory::where('straw_code', 'SEMEN-HF-9901')->firstOrFail();
        $initialStraws = $straw->straws_in_stock;

        $payload = [
            'animal_id' => $cow->id,
            'method' => 'artificial_insemination',
            'insemination_datetime' => Carbon::now()->toDateTimeString(),
            'semen_straw_inventory_id' => $straw->id,
            'technician_name' => 'Dr. Asim Raza (AI Specialist)',
            'cycle_number' => 1,
            'heat_intensity_score' => 4, // Standing heat with clear discharge
            'cost' => 4500.00,
            'notes' => 'Inseminated 12 hours after first standing heat observation (AM/PM rule).',
        ];

        $response = $this->postJson('/api/v1/breeding/events', $payload);

        $response->assertStatus(201)
            ->assertJson([
                'data' => [
                    'semen_straw_code' => 'SEMEN-HF-9901',
                    'sire_breed_code' => 'Holstein Friesian',
                    'status' => 'pending_check',
                ],
            ]);

        $straw->refresh();
        $this->assertEquals($initialStraws - 1, $straw->straws_in_stock);
    }

    public function test_calving_lifecycle_engine_automates_newborn_dam_lactation_and_postpartum(): void
    {
        $dam = Animal::where('tag_number', 'PK-COW-002')->firstOrFail();
        $dam->update(['status' => 'pregnant']);

        $payload = [
            'dam_id' => $dam->id,
            'calving_datetime' => Carbon::now()->toDateTimeString(),
            'calving_ease' => 'easy_unassisted',
            'birth_weight_kg' => 37.50,
            'offspring_sex' => 'female',
            'colostrum_fed' => true,
            'colostrum_liters' => 3.8,
            'attendant_name' => 'Muhammad Bilal (Herd Manager)',
            'notes' => 'Spontaneous delivery in calving pen. Calf vigorous and standing within 30 min.',
        ];

        $response = $this->postJson('/api/v1/breeding/calving', $payload);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'message',
                'data' => [
                    'birth' => ['id', 'dam_id', 'created_offspring_id', 'birth_weight_kg'],
                    'calf' => ['id', 'tag_number', 'sex', 'birth_weight_kg', 'lifecycle_stage'],
                    'dam' => ['id', 'status'],
                    'postpartum_check' => ['id', 'animal_id', 'days_in_milk', 'check_date'],
                ],
            ]);

        $calfId = $response->json('data.calf.id');
        $calf = Animal::findOrFail($calfId);
        $this->assertEquals('calf', $calf->lifecycle_stage);
        $this->assertEquals(37.50, (float) $calf->birth_weight_kg);

        // Dam MUST transition to lactating
        $dam->refresh();
        $this->assertEquals('lactating', $dam->status);

        // Lineage Pedigree and initial weight MUST exist
        $this->assertDatabaseHas('animal_pedigrees', [
            'animal_id' => $calf->id,
            'dam_id' => $dam->id,
        ]);

        $this->assertDatabaseHas('weight_records', [
            'animal_id' => $calf->id,
            'weight_kg' => 37.50,
        ]);

        // Postpartum check scheduled
        $this->assertDatabaseHas('postpartum_checks', [
            'animal_id' => $dam->id,
            'days_in_milk' => 10,
        ]);
    }

    public function test_reproductive_kpis_and_postpartum_checks_api(): void
    {
        $kpiResp = $this->getJson('/api/v1/breeding/kpis');
        $kpiResp->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'total_breeding_events',
                    'confirmed_pregnancies_count',
                    'conception_rate_percent',
                    'services_per_conception',
                    'heat_detection_index',
                ],
            ]);

        $ppResp = $this->getJson('/api/v1/breeding/postpartum-checks');
        $ppResp->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'animal_id', 'days_in_milk', 'rectal_temperature_c', 'ketosis_test_bhb_mmol_l'],
                ],
            ]);
    }
}
