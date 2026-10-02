<?php

namespace Tests\Feature;

use App\Domain\Animals\Models\Animal;
use App\Domain\Climate\Models\PasturePlot;
use App\Domain\Health\Models\Medicine;
use App\Domain\Health\Models\Treatment;
use App\Domain\Inventory\Models\FarmAsset;
use App\Domain\Inventory\Models\Warehouse;
use App\Domain\Organization\Models\Farm;
use App\Domain\Sales\Models\Customer;
use App\Domain\Sales\Models\DeliveryRun;
use Carbon\Carbon;
use Database\Seeders\PilotFarmSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PhaseFourMeatPastureInventoryAndSalesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PilotFarmSeeder::class);
    }

    public function test_feedlot_gain_tracking_and_fcr_calculation(): void
    {
        $animal = Animal::where('lifecycle_stage', 'calf')
            ->orWhere('status', 'active')
            ->firstOrFail();

        // 1. Intake into feedlot
        $intakePayload = [
            'animal_id' => $animal->id,
            'intake_date' => Carbon::now()->subDays(30)->toDateString(),
            'intake_weight_kg' => 300.00,
            'target_slaughter_weight_kg' => 450.00,
            'daily_ration_cost' => 380.00,
            'notes' => 'Steer entered finishing pen',
        ];

        $response = $this->postJson('/api/v1/meat/feedlots', $intakePayload);
        $response->assertStatus(201)
            ->assertJsonPath('data.intake_weight_kg', '300.00')
            ->assertJsonPath('data.status', 'active');

        $recordId = $response->json('data.id');

        // 2. Periodic weigh-in & gain calculation
        $updatePayload = [
            'current_weight_kg' => 345.00, // 45kg gain
            'feed_consumed_kg_dm' => 290.00,
            'ration_cost' => 380.00,
        ];

        $updateResponse = $this->postJson("/api/v1/meat/feedlots/{$recordId}/gain", $updatePayload);
        $updateResponse->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'data' => [
                    'record' => ['id', 'current_weight_kg', 'total_gain_kg', 'average_daily_gain_kg', 'feed_conversion_ratio'],
                    'metrics' => ['average_daily_gain_kg', 'total_gain_kg', 'feed_conversion_ratio', 'estimated_days_to_slaughter'],
                ],
            ]);

        $this->assertEquals(45.00, (float) $updateResponse->json('data.metrics.total_gain_kg'));
        $this->assertGreaterThan(0.0, (float) $updateResponse->json('data.metrics.average_daily_gain_kg'));
        $this->assertGreaterThan(0.0, (float) $updateResponse->json('data.metrics.feed_conversion_ratio'));
    }

    public function test_slaughter_clearance_guard_blocks_when_meat_withdrawal_active(): void
    {
        $animal = Animal::firstOrFail();
        $medicine = Medicine::firstOrFail();

        // Create active treatment with meat withdrawal in the future (+10 days)
        Treatment::create([
            'farm_id' => $animal->farm_id,
            'animal_id' => $animal->id,
            'medicine_id' => $medicine->id,
            'administered_at' => Carbon::now(),
            'dosage' => 15.0,
            'dosage_unit' => 'mL',
            'route' => 'intramuscular',
            'meat_withdrawal_until' => Carbon::now()->addDays(10),
        ]);

        // 1. Check clearance endpoint
        $clearanceResponse = $this->getJson("/api/v1/meat/slaughter/clearance/{$animal->id}");
        $clearanceResponse->assertStatus(200)
            ->assertJsonPath('data.cleared', false);
        $this->assertNotNull($clearanceResponse->json('data.reason'));

        // 2. Attempting to record slaughter must fail with 422
        $slaughterPayload = [
            'animal_id' => $animal->id,
            'slaughterhouse_name' => 'Lahore Meat Processors Abattoir',
            'slaughter_date' => Carbon::now()->toDateString(),
            'live_weight_kg' => 480.00,
            'hot_carcass_weight_kg' => 264.00,
        ];

        $response = $this->postJson('/api/v1/meat/slaughter', $slaughterPayload);
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['meat_withdrawal']);
    }

    public function test_slaughter_record_and_carcass_grading_success_when_cleared(): void
    {
        $farm = Farm::firstOrFail();
        $species = Animal::firstOrFail()->species;

        // Create clean animal with no treatments
        $cleanAnimal = Animal::create([
            'organization_id' => $farm->organization_id,
            'farm_id' => $farm->id,
            'species_id' => $species->id,
            'tag_number' => 'PK-CLEAN-BEEF-01',
            'name' => 'Prime Finisher',
            'sex' => 'male',
            'birth_date' => Carbon::now()->subMonths(24)->toDateString(),
            'birth_weight_kg' => 34.0,
            'current_weight_kg' => 520.0,
            'lifecycle_stage' => 'steer',
            'status' => 'active',
        ]);

        // 1. Check clearance
        $clearanceResponse = $this->getJson("/api/v1/meat/slaughter/clearance/{$cleanAnimal->id}");
        $clearanceResponse->assertStatus(200)
            ->assertJsonPath('data.cleared', true);

        // 2. Record slaughter
        $slaughterPayload = [
            'animal_id' => $cleanAnimal->id,
            'slaughterhouse_name' => 'Punjab Halal Development Agency Abattoir',
            'slaughter_date' => Carbon::now()->toDateString(),
            'live_weight_kg' => 520.00,
            'hot_carcass_weight_kg' => 296.40, // 57% dressing percentage
            'conformation_grade' => 'prime',
            'fat_score' => 3,
            'carcass_bar_code' => 'CARCASS-2026-9901',
            'technician_name' => 'Dr. Shakeel Ahmed',
        ];

        $response = $this->postJson('/api/v1/meat/slaughter', $slaughterPayload);
        $response->assertStatus(201)
            ->assertJsonPath('data.dressing_percentage', '57.00')
            ->assertJsonPath('data.conformation_grade', 'prime')
            ->assertJsonPath('data.meat_withdrawal_cleared', true);

        // Animal status must update to culled
        $cleanAnimal->refresh();
        $this->assertEquals('culled', $cleanAnimal->status);
    }

    public function test_fleece_shearing_and_micron_grading_classification(): void
    {
        $animal = Animal::firstOrFail();

        $fleecePayload = [
            'animal_id' => $animal->id,
            'shearing_date' => Carbon::now()->toDateString(),
            'fleece_type' => 'wool',
            'grease_fleece_weight_kg' => 3.50,
            'micron_grade' => 17.2, // < 17.5 = ultrafine
            'dirt_yield_deduction_percent' => 30.0,
            'staple_length_mm' => 75.0,
            'shearer_name' => 'Muhammad Aslam',
        ];

        $response = $this->postJson('/api/v1/meat/fleeces', $fleecePayload);
        $response->assertStatus(201)
            ->assertJsonPath('data.quality_tier', 'ultrafine')
            ->assertJsonPath('data.clean_yield_percentage', '70.00')
            ->assertJsonPath('data.clean_fleece_weight_kg', '2.45');
    }

    public function test_specialized_species_attributes_for_camel_and_buffalo(): void
    {
        $animal = Animal::firstOrFail();

        $payload = [
            'species_type' => 'camel',
            'hump_condition_score' => 4.5,
            'draft_work_type' => 'racing',
            'racing_eligibility_status' => true,
            'veterinary_passport_number' => 'UAE-CAMEL-PASS-7819',
            'microchip_transponder_rfid' => '982000182763541',
        ];

        $response = $this->postJson("/api/v1/meat/specialized-species/{$animal->id}", $payload);
        $response->assertStatus(200)
            ->assertJsonPath('data.species_type', 'camel')
            ->assertJsonPath('data.hump_condition_score', '4.5')
            ->assertJsonPath('data.racing_eligibility_status', true);
    }

    public function test_nrc_thi_sensor_ingestion_and_automated_cooling_actuators(): void
    {
        // High heat and humidity: 38°C, 65% RH
        $payload = [
            'temperature_c' => 38.0,
            'relative_humidity_percent' => 65.0,
            'species_type' => 'cattle',
            'air_velocity_m_s' => 0.8,
            'solar_radiation_w_m2' => 750.0,
            'sensor_device_id' => 'BARN-1-TH-01',
        ];

        $response = $this->postJson('/api/v1/climate/sensor-readings', $payload);
        $response->assertStatus(201)
            ->assertJsonStructure([
                'message',
                'data' => [
                    'reading' => ['id', 'temperature_c', 'relative_humidity_percent', 'thi_index', 'heat_stress_level'],
                    'assessment' => ['thi_index', 'heat_stress_level', 'cooling_actuator_activated', 'recommended_action'],
                ],
            ]);

        $thi = (float) $response->json('data.assessment.thi_index');
        $this->assertGreaterThan(88.0, $thi);
        $this->assertTrue($response->json('data.assessment.cooling_actuator_activated'));
    }

    public function test_pasture_rotational_grazing_entry_and_exit_biomass_utilization(): void
    {
        $paddock = PasturePlot::firstOrFail();

        // 1. Enter Paddock
        $entryPayload = [
            'pasture_plot_id' => $paddock->id,
            'stocking_density_heads' => 12,
            'pre_graze_height_cm' => 30.0,
            'entry_date' => Carbon::now()->subDays(4)->toDateString(),
        ];

        $enterResponse = $this->postJson('/api/v1/pastures/grazing/enter', $entryPayload);
        $enterResponse->assertStatus(201)
            ->assertJsonPath('data.pre_graze_height_cm', '30.0');

        $logId = $enterResponse->json('data.id');

        $paddock->refresh();
        $this->assertEquals('grazing', $paddock->status);

        // 2. Exit Paddock & calculate DM utilized
        $exitPayload = [
            'post_graze_residual_height_cm' => 12.0, // 18cm eaten
            'exit_date' => Carbon::now()->toDateString(),
        ];

        $exitResponse = $this->postJson("/api/v1/pastures/grazing/{$logId}/exit", $exitPayload);
        $exitResponse->assertStatus(200)
            ->assertJsonPath('data.post_graze_residual_height_cm', '12.0');

        // 18cm * 250 kg DM/cm = 4500 kg DM utilized
        $this->assertEquals(4500.00, (float) $exitResponse->json('data.dry_matter_utilized_kg_ha'));

        $paddock->refresh();
        $this->assertEquals('recovering', $paddock->status);
    }

    public function test_multi_warehouse_inventory_and_stock_transactions(): void
    {
        $warehouse = Warehouse::firstOrFail();

        // 1. Create SKU
        $skuPayload = [
            'warehouse_id' => $warehouse->id,
            'category' => 'spare_parts',
            'sku' => 'SKU-LINER-DL-04',
            'name' => 'DeLaval Triangular Clover Milking Liner 4-Pack',
            'unit_of_measure' => 'piece',
            'current_stock_quantity' => 10.00,
            'reorder_level_quantity' => 4.00,
            'safety_stock_quantity' => 2.00,
            'unit_cost' => 1200.00,
        ];

        $skuResponse = $this->postJson('/api/v1/inventory/items', $skuPayload);
        $skuResponse->assertStatus(201)
            ->assertJsonPath('data.sku', 'SKU-LINER-DL-04');

        $skuId = $skuResponse->json('data.id');

        // 2. Issue 3 pieces to parlor
        $issuePayload = [
            'inventory_item_id' => $skuId,
            'transaction_type' => 'issue_to_farm',
            'quantity' => 3.00,
            'notes' => 'Routine milking claw liner replacement',
        ];

        $issueResponse = $this->postJson('/api/v1/inventory/transactions', $issuePayload);
        $issueResponse->assertStatus(201)
            ->assertJsonPath('data.updated_stock_quantity', '7.00')
            ->assertJsonPath('data.is_low_stock', false);

        // 3. Issue exceeding available stock must fail with 422
        $excessPayload = [
            'inventory_item_id' => $skuId,
            'transaction_type' => 'issue_to_farm',
            'quantity' => 50.00,
        ];

        $excessResponse = $this->postJson('/api/v1/inventory/transactions', $excessPayload);
        $excessResponse->assertStatus(422)
            ->assertJsonValidationErrors(['quantity']);
    }

    public function test_farm_asset_machinery_and_preventative_maintenance_logs(): void
    {
        // 1. Register asset
        $assetPayload = [
            'name' => 'Mueller 3000L Direct-Expansion Bulk Milk Cooler',
            'asset_code' => 'ASSET-CHILL-02',
            'category' => 'bulk_tank',
            'make' => 'Paul Mueller Company',
            'model' => 'OE-3000',
            'serial_number' => 'PMC-OE-2025-4411',
            'purchase_cost' => 2800000.00,
            'meter_type' => 'hours',
            'current_meter_reading' => 520.0,
        ];

        $assetResponse = $this->postJson('/api/v1/inventory/assets', $assetPayload);
        $assetResponse->assertStatus(201)
            ->assertJsonPath('data.asset_code', 'ASSET-CHILL-02');

        $assetId = $assetResponse->json('data.id');

        // 2. Log maintenance
        $maintPayload = [
            'farm_asset_id' => $assetId,
            'maintenance_type' => 'preventative',
            'service_date' => Carbon::now()->toDateString(),
            'technician_name' => 'Pak Chill Tech Solutions',
            'meter_reading' => 540.0,
            'downtime_hours' => 2.0,
            'parts_cost' => 12000.00,
            'labor_cost' => 5000.00,
            'next_service_due_date' => Carbon::now()->addMonths(6)->toDateString(),
            'notes' => 'R404A Freon top-up and condenser coil pressure washing',
        ];

        $maintResponse = $this->postJson('/api/v1/inventory/maintenance', $maintPayload);
        $maintResponse->assertStatus(201)
            ->assertJsonPath('data.total_cost', '17000.00');

        $asset = FarmAsset::findOrFail($assetId);
        $this->assertEquals(540.0, (float) $asset->current_meter_reading);
    }

    public function test_direct_milk_subscription_delivery_run_and_wallet_billing(): void
    {
        $farm = Farm::firstOrFail();

        // 1. Create Customer
        $customerPayload = [
            'customer_type' => 'household_subscription',
            'name' => 'Professor Tariq Saeed',
            'phone' => '+92-333-1122334',
            'address' => 'House 88, Street 12, Cavalry Ground',
            'city' => 'Lahore',
            'initial_wallet_balance' => 3000.00,
        ];

        $custResponse = $this->postJson('/api/v1/sales/customers', $customerPayload);
        $custResponse->assertStatus(201);
        $customerId = $custResponse->json('data.id');

        // 2. Create Subscription (2L daily Raw Cow Milk @ 220/L)
        $subPayload = [
            'customer_id' => $customerId,
            'product_type' => 'raw_cow_milk',
            'daily_quantity_liters' => 2.00,
            'unit_price_per_liter' => 220.00,
            'frequency' => 'daily',
            'start_date' => Carbon::now()->subDays(5)->toDateString(),
        ];

        $subResponse = $this->postJson('/api/v1/sales/subscriptions', $subPayload);
        $subResponse->assertStatus(201);

        // 3. Generate Delivery Run for today
        $runPayload = [
            'run_date' => Carbon::now()->toDateString(),
            'route_name' => 'Cavalry Ground & Cantt Direct Route',
            'driver_name' => 'Imran Farooq',
            'vehicle_plate_number' => 'LEA-1092',
        ];

        $runResponse = $this->postJson('/api/v1/sales/delivery-runs/generate', $runPayload);
        $runResponse->assertStatus(201);

        $runId = $runResponse->json('data.id');
        $run = DeliveryRun::with('stops')->findOrFail($runId);
        $this->assertGreaterThan(0, $run->stops->count());

        $targetStop = $run->stops->where('customer_id', $customerId)->first();
        $this->assertNotNull($targetStop);
        $this->assertEquals(2.00, (float) $targetStop->planned_quantity_liters);

        // 4. Complete Stop and Verify Wallet Billing
        $completePayload = [
            'delivered_quantity_liters' => 2.00,
            'empty_bottles_returned' => 2,
            'proof_of_delivery_token' => 'OTP-9821',
            'notes' => 'Customer confirmed delivery via OTP',
        ];

        $stopResponse = $this->postJson("/api/v1/sales/delivery-stops/{$targetStop->id}/complete", $completePayload);
        $stopResponse->assertStatus(200)
            ->assertJsonPath('data.stop.status', 'delivered');

        // 3000.00 opening balance - (2L * 220 = 440) = 2560.00
        $customer = Customer::findOrFail($customerId);
        $this->assertEquals(2560.00, (float) $customer->wallet_balance);

        // 5. Test Wallet Top-up
        $topupResponse = $this->postJson("/api/v1/sales/customers/{$customerId}/topup", [
            'amount' => 1000.00,
            'reference_id' => 'EASYPAISA-98711',
        ]);
        $topupResponse->assertStatus(200);

        $customer->refresh();
        $this->assertEquals(3560.00, (float) $customer->wallet_balance);
    }
}
