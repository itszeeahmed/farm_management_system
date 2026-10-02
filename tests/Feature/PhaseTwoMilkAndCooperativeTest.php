<?php

namespace Tests\Feature;

use App\Domain\Animals\Models\Animal;
use App\Domain\Health\Models\HealthCase;
use App\Domain\Health\Models\Medicine;
use App\Domain\Health\Models\Treatment;
use App\Domain\Milk\Models\BulkTank;
use App\Domain\Milk\Models\FarmerSupplier;
use App\Domain\Milk\Models\MilkCollectionCenter;
use App\Domain\Milk\Models\MilkCollectionIntake;
use App\Domain\Milk\Models\MilkRateChart;
use App\Domain\Milk\Models\MilkRecord;
use App\Domain\Milk\Models\MilkSession;
use App\Domain\Milk\Services\SomaticCellCountGraderService;
use App\Domain\Milk\Services\TwoDimensionalRateChartPricingCalculator;
use App\Models\User;
use Carbon\Carbon;
use Database\Seeders\PilotFarmSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PhaseTwoMilkAndCooperativeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PilotFarmSeeder::class);
    }

    public function test_milk_summary_and_bulk_tanks_api(): void
    {
        $response = $this->getJson('/api/v1/milk/summary');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'date',
                'cattle' => ['morning', 'evening', 'total'],
                'goats' => ['morning', 'evening', 'total'],
                'discarded_liters',
                'saleable_grand_total',
            ]);

        $tanksResponse = $this->getJson('/api/v1/milk/bulk-tanks');

        $tanksResponse->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'tank_code',
                        'capacity_liters',
                        'current_volume_liters',
                        'current_temperature_c',
                        'cooling_status',
                        'is_sanitized',
                        'fill_percentage',
                    ],
                ],
            ]);
    }

    public function test_automated_drug_withdrawal_safety_lock_discards_milk_and_blocks_bulk_pooling(): void
    {
        $cow = Animal::where('tag_number', 'PK-COW-005')->firstOrFail();
        $session = MilkSession::latest()->firstOrFail();
        $medicine = Medicine::firstOrFail();
        $tank = BulkTank::where('tank_code', 'TANK-01')->firstOrFail();
        $initialTankVolume = (float) $tank->current_volume_liters;
        $initialSessionTotal = (float) $session->total_yield_liters;

        // Ensure active withdrawal treatment exists for PK-COW-005
        Treatment::updateOrCreate(
            ['animal_id' => $cow->id, 'milk_withdrawal_until' => Carbon::now()->addDays(3)],
            [
                'farm_id' => $cow->farm_id,
                'medicine_id' => $medicine->id,
                'health_case_id' => HealthCase::where('animal_id', $cow->id)->value('id'),
                'treatment_date' => Carbon::now()->subDay(),
                'administered_at' => Carbon::now()->subDay(),
                'dosage' => 20.00,
                'administered_by' => 'Dr. Asim Raza',
            ]
        );

        $payload = [
            'animal_id' => $cow->id,
            'milk_session_id' => $session->id,
            'recorded_date' => Carbon::now()->toDateString(),
            'shift' => 'morning',
            'yield_liters' => 6.5,
            'fat_percentage' => 4.2,
            'snf_percentage' => 8.8,
            'scc' => 450000,
        ];

        $response = $this->postJson('/api/v1/milk/record', $payload);

        $response->assertStatus(201)
            ->assertJson([
                'safe_to_pool' => false,
            ]);

        $record = MilkRecord::where('animal_id', $cow->id)->latest('id')->first();
        $this->assertNotNull($record);
        $this->assertEquals('discarded_withdrawal', $record->quality_status);
        $this->assertStringContainsString('Active food safety withdrawal', $record->discard_reason);

        // Bulk tank and session total MUST NOT increment for withheld milk
        $tank->refresh();
        $session->refresh();
        $this->assertEquals($initialTankVolume, (float) $tank->current_volume_liters);
        $this->assertEquals($initialSessionTotal, (float) $session->total_yield_liters);

        // Audit log must be logged
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'milk_discarded_withdrawal_lock',
            'auditable_id' => $cow->id,
        ]);
    }

    public function test_supervisor_override_on_withholding_lock_with_dual_audit_log(): void
    {
        $cow = Animal::where('tag_number', 'PK-COW-005')->firstOrFail();
        $session = MilkSession::latest()->firstOrFail();
        $owner = User::where('email', 'owner@farm.local')->firstOrFail();

        $tank = BulkTank::where('tank_code', 'TANK-01')->firstOrFail();
        $initialTankVolume = (float) $tank->current_volume_liters;

        $payload = [
            'animal_id' => $cow->id,
            'milk_session_id' => $session->id,
            'recorded_date' => Carbon::now()->toDateString(),
            'shift' => 'evening',
            'yield_liters' => 5.0,
            'fat_percentage' => 4.0,
            'snf_percentage' => 8.7,
            'scc' => 220000,
            'override_withholding' => true,
            'override_reason' => 'Controlled thermal separation for calf-only feeding authorized by farm manager.',
            'override_user_id' => $owner->id,
        ];

        $response = $this->actingAs($owner)->postJson('/api/v1/milk/record', $payload);

        $response->assertStatus(201);
        $this->assertTrue($response->json('safe_to_pool'));

        $record = MilkRecord::where('animal_id', $cow->id)->latest('id')->first();
        $this->assertEquals($owner->id, $record->withholding_override_by);
        $this->assertStringContainsString('SUPERVISOR OVERRIDE', $record->discard_reason);

        // Tank volume should reflect the pooled yield
        $tank->refresh();
        $this->assertEquals($initialTankVolume + 5.0, (float) $tank->current_volume_liters);

        // Audit log check
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'milk_withdrawal_override',
            'auditable_id' => $cow->id,
            'user_id' => $owner->id,
        ]);
    }

    public function test_somatic_cell_count_grading_and_electrical_conductivity_mastitis_alert(): void
    {
        $sccGrader = new SomaticCellCountGraderService;

        // 1. Premium Grade (SCC < 200,000)
        $this->assertEquals('premium', $sccGrader->gradeByScc(150000));

        // 2. Standard Grade (200,000 - 400,000)
        $this->assertEquals('standard', $sccGrader->gradeByScc(280000));

        // 3. Substandard Grade (SCC > 400,000)
        $this->assertEquals('substandard', $sccGrader->gradeByScc(550000));

        // 4. Electrical Conductivity Mastitis Alert
        $normalEc = $sccGrader->evaluateConductivity(5.2);
        $this->assertFalse($normalEc['has_alert']);

        $highEc = $sccGrader->evaluateConductivity(7.1);
        $this->assertTrue($highEc['has_alert']);
        $this->assertEquals('critical', $highEc['alert_level']);
        $this->assertStringContainsString('Potential subclinical mastitis', $highEc['message']);

        // Test through API
        $cow = Animal::where('tag_number', 'PK-COW-001')->firstOrFail();
        $session = MilkSession::latest()->firstOrFail();

        $response = $this->postJson('/api/v1/milk/record', [
            'animal_id' => $cow->id,
            'milk_session_id' => $session->id,
            'recorded_date' => Carbon::now()->toDateString(),
            'shift' => 'morning',
            'yield_liters' => 8.0,
            'fat_percentage' => 4.5,
            'snf_percentage' => 9.0,
            'scc' => 620000,
            'electrical_conductivity_ms_cm' => 6.8,
        ]);

        $response->assertStatus(201);
        $this->assertEquals('substandard', $response->json('data.quality_status'));
        $this->assertNotNull($response->json('conductivity_alert'));
    }

    public function test_bulk_tank_cip_clean_flushing(): void
    {
        $tank = BulkTank::where('tank_code', 'TANK-01')->firstOrFail();
        $worker = User::where('email', 'worker@farm.local')->firstOrFail();

        $response = $this->actingAs($worker)->postJson("/api/v1/milk/bulk-tanks/{$tank->id}/cip-clean", [
            'cleaner_user_id' => $worker->id,
            'cleaning_cycle' => 'full_acid_and_alkali',
            'temperature_verified_c' => 75.0,
            'chemical_used' => 'Chlorinated Alkaline Detergent 1.5% + Nitric Acid Rinse',
            'empty_tank_first' => true,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'current_volume_liters' => '0.00',
                    'is_sanitized' => true,
                ],
            ]);

        $tank->refresh();
        $this->assertEquals(0.0, (float) $tank->current_volume_liters);
        $this->assertTrue($tank->is_sanitized);
        $this->assertNotNull($tank->last_cip_cleaned_at);
    }

    public function test_milk_tanker_dispatch_gate_pass_and_volume_deduction(): void
    {
        $tank = BulkTank::where('tank_code', 'TANK-01')->firstOrFail();
        $tank->update(['current_volume_liters' => 800.00]);
        $owner = User::where('email', 'owner@farm.local')->firstOrFail();

        $payload = [
            'bulk_tank_id' => $tank->id,
            'buyer_name' => 'FrieslandCampina Engro Pakistan',
            'driver_name' => 'Kashif Mehmood',
            'driver_phone' => '+92-300-7766554',
            'tanker_plate_number' => 'LXZ-9142',
            'seal_number' => 'SEAL-FC-10928',
            'dispatched_volume_liters' => 350.00,
            'temperature_c' => 3.7,
            'composite_fat_percentage' => 4.20,
            'composite_snf_percentage' => 8.80,
            'composite_scc' => 180000,
            'unit_price_pkr' => 180.00,
            'notes' => 'Bulk cold-chain transfer compliant with PSQCA standards.',
        ];

        $response = $this->actingAs($owner)->postJson('/api/v1/milk/dispatches', $payload);

        $response->assertStatus(201)
            ->assertJson([
                'data' => [
                    'dispatched_volume_liters' => '350.00',
                    'total_price_pkr' => '63000.00',
                    'status' => 'dispatched',
                ],
            ]);

        $tank->refresh();
        $this->assertEquals(450.00, (float) $tank->current_volume_liters);

        // Attempting to dispatch more than available volume should fail with 422
        $excessResponse = $this->actingAs($owner)->postJson('/api/v1/milk/dispatches', array_merge($payload, [
            'dispatched_volume_liters' => 900.00,
        ]));

        $excessResponse->assertStatus(422)
            ->assertJsonValidationErrors(['dispatched_volume_liters']);
    }

    public function test_cooperative_milk_collection_center_and_suppliers_api(): void
    {
        $mcc = MilkCollectionCenter::where('center_code', 'MCC-KASUR-01')->firstOrFail();

        // 1. Get centers
        $centersResp = $this->getJson('/api/v1/collection-centers');
        $centersResp->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'center_code', 'name', 'chilling_capacity_liters', 'current_volume_liters'],
                ],
            ]);

        // 2. Get single center
        $singleResp = $this->getJson("/api/v1/collection-centers/{$mcc->id}");
        $singleResp->assertStatus(200)
            ->assertJson([
                'data' => [
                    'center_code' => 'MCC-KASUR-01',
                ],
            ]);

        // 3. Register a new smallholder farmer supplier
        $supplierPayload = [
            'collection_center_id' => $mcc->id,
            'supplier_code' => 'SUP-003',
            'name' => 'Ghulam Rasool Gujar',
            'phone' => '+92-302-9988776',
            'cnic_or_national_id' => '35102-9876543-5',
            'village_address' => 'Rao Khan Wala, Chunian',
            'cattle_count' => 4,
            'buffalo_count' => 7,
            'goat_count' => 3,
            'payout_channel' => 'easypaisa',
            'payout_account_number' => '03029988776',
            'payout_account_title' => 'Ghulam Rasool',
        ];

        $supplierResp = $this->postJson('/api/v1/suppliers', $supplierPayload);
        $supplierResp->assertStatus(201)
            ->assertJson([
                'data' => [
                    'supplier_code' => 'SUP-003',
                    'name' => 'Ghulam Rasool Gujar',
                ],
            ]);

        $this->assertDatabaseHas('farmer_suppliers', [
            'supplier_code' => 'SUP-003',
            'collection_center_id' => $mcc->id,
        ]);
    }

    public function test_richmond_formula_snf_calculation_and_2d_rate_chart_pricing(): void
    {
        $calculator = new TwoDimensionalRateChartPricingCalculator;

        // Richmond Formula Verification
        // Cow: SNF = (CLR / 4) + (0.25 * Fat) + 0.35
        // CLR = 28.0, Fat = 4.0 -> (28/4) + (0.25 * 4.0) + 0.35 = 7.0 + 1.0 + 0.35 = 8.35
        $cowSnf = $calculator->calculateSnfFromLactometer(28.0, 4.0, 'cow');
        $this->assertEquals(8.35, $cowSnf);

        // Buffalo: SNF = (CLR / 4) + (0.20 * Fat) + 0.60
        // CLR = 30.0, Fat = 6.5 -> (30/4) + (0.20 * 6.5) + 0.60 = 7.5 + 1.30 + 0.60 = 9.40
        $buffSnf = $calculator->calculateSnfFromLactometer(30.0, 6.5, 'buffalo');
        $this->assertEquals(9.40, $buffSnf);

        $cowChart = MilkRateChart::where('species_type', 'cow')->firstOrFail();
        $mcc = MilkCollectionCenter::where('center_code', 'MCC-KASUR-01')->firstOrFail();
        $supplier = FarmerSupplier::where('supplier_code', 'SUP-001')->firstOrFail();

        // 2D Rate Calculation:
        // Base = 140.00, StdFat = 3.5, StdSNF = 8.5
        // FatRate = 12.0/unit, SNFRate = 8.0/unit, Premium = 2.5%
        // If Fat = 4.0 (delta +0.5 * 12 = +6.0), SNF = 8.5 (delta 0 * 8 = 0)
        // Subtotal = 146.00 + (146 * 2.5% = 3.65) = 149.65 / L
        $pricing = $calculator->calculateIntakePrice($cowChart, 50.0, 4.0, 8.5);
        $this->assertTrue($pricing['quality_accepted']);
        $this->assertEquals(149.65, $pricing['price_per_liter']);
        $this->assertEquals(7482.50, $pricing['gross_amount']);
        $this->assertEquals(7482.50, $pricing['net_payable_amount']);

        // Post Intake via API
        $intakePayload = [
            'collection_center_id' => $mcc->id,
            'farmer_supplier_id' => $supplier->id,
            'rate_chart_id' => $cowChart->id,
            'collection_date' => Carbon::now()->toDateString(),
            'shift' => 'morning',
            'species_type' => 'cow',
            'gross_volume_liters' => 50.00,
            'lactometer_reading' => 28.6,
            'fat_percentage' => 4.0,
            'alcohol_test_result' => 'negative',
            'adulteration_starch' => false,
            'adulteration_urea' => false,
            'adulteration_detergent' => false,
            'adulteration_formalin' => false,
            'adulteration_hydrogen_peroxide' => false,
            'added_water_percentage' => 0.0,
        ];

        $response = $this->postJson('/api/v1/intakes', $intakePayload);

        $response->assertStatus(201)
            ->assertJson([
                'quality_accepted' => true,
                'data' => [
                    'quality_accepted' => true,
                    'gross_volume_liters' => '50.00',
                ],
            ]);

        $intake = MilkCollectionIntake::latest('id')->firstOrFail();
        $this->assertTrue($intake->quality_accepted);
        $this->assertGreaterThan(0, (float) $intake->calculated_price_per_liter);
        $this->assertGreaterThan(0, (float) $intake->net_payable_amount);
    }

    public function test_adulteration_battery_detection_triggers_automatic_rejection(): void
    {
        $calculator = new TwoDimensionalRateChartPricingCalculator;
        $chart = MilkRateChart::where('species_type', 'cow')->firstOrFail();

        // 1. Alcohol Curdling Test (Acidic/Sour Milk)
        $curdled = $calculator->calculateIntakePrice($chart, 40.0, 3.8, 8.5, [
            'alcohol_test_result' => 'positive',
        ]);
        $this->assertFalse($curdled['quality_accepted']);
        $this->assertEquals(0.0, $curdled['net_payable_amount']);
        $this->assertStringContainsString('Alcohol Test', $curdled['rejection_reason']);

        // 2. Chemical Starch Adulteration
        $starch = $calculator->calculateIntakePrice($chart, 40.0, 3.8, 8.5, [
            'adulteration_starch' => true,
        ]);
        $this->assertFalse($starch['quality_accepted']);
        $this->assertEquals(0.0, $starch['net_payable_amount']);
        $this->assertStringContainsString('Starch Adulterant Detected', $starch['rejection_reason']);

        // 3. Excess Added Water > 5%
        $watered = $calculator->calculateIntakePrice($chart, 40.0, 3.8, 8.5, [
            'added_water_percentage' => 12.5,
        ]);
        $this->assertFalse($watered['quality_accepted']);
        $this->assertEquals(0.0, $watered['net_payable_amount']);
        $this->assertStringContainsString('Added water (12.5%) exceeds allowable threshold', $watered['rejection_reason']);

        // API Test Rejection
        $mcc = MilkCollectionCenter::where('center_code', 'MCC-KASUR-01')->firstOrFail();
        $supplier = FarmerSupplier::where('supplier_code', 'SUP-001')->firstOrFail();

        $rejectedPayload = [
            'collection_center_id' => $mcc->id,
            'farmer_supplier_id' => $supplier->id,
            'rate_chart_id' => $chart->id,
            'collection_date' => Carbon::now()->toDateString(),
            'shift' => 'evening',
            'species_type' => 'cow',
            'gross_volume_liters' => 30.00,
            'lactometer_reading' => 22.0,
            'fat_percentage' => 2.5, // Below min acceptance
            'alcohol_test_result' => 'positive',
            'adulteration_starch' => true,
            'added_water_percentage' => 15.0,
        ];

        $response = $this->postJson('/api/v1/intakes', $rejectedPayload);

        $response->assertStatus(201)
            ->assertJson([
                'quality_accepted' => false,
                'data' => [
                    'quality_accepted' => false,
                    'net_payable_amount' => '0.00',
                ],
            ]);

        $intake = MilkCollectionIntake::latest('id')->firstOrFail();
        $this->assertFalse($intake->quality_accepted);
        $this->assertEquals(0.0, (float) $intake->net_payable_amount);
        $this->assertNotNull($intake->rejection_reason);
    }
}
