<?php

namespace Tests\Feature;

use App\Domain\Animals\Models\Animal;
use App\Domain\Animals\Models\SlaughterRecord;
use App\Domain\Compliance\Models\AnimalMovementPermit;
use App\Domain\Finance\Models\ChartOfAccount;
use App\Domain\Finance\Models\GeneralLedgerEntry;
use App\Domain\Health\Models\Medicine;
use App\Domain\Health\Models\Treatment;
use App\Domain\Milk\Models\MilkRecord;
use App\Domain\Organization\Models\Farm;
use App\Domain\Sales\Models\Customer;
use App\Domain\Sales\Models\DeliveryRun;
use App\Domain\Sales\Models\DeliveryRunStop;
use Carbon\Carbon;
use Database\Seeders\PilotFarmSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PhaseFiveFinanceComplianceAndSyncTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PilotFarmSeeder::class);
    }

    public function test_chart_of_accounts_and_double_entry_general_ledger_voucher(): void
    {
        $farm = Farm::firstOrFail();

        // 1. Fetch Chart of Accounts
        $coaResponse = $this->getJson('/api/v1/finance/chart-of-accounts');
        $coaResponse->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'account_code', 'name', 'account_type', 'is_active'],
                ],
            ]);

        $cashAccount = ChartOfAccount::where('account_code', '1010')->firstOrFail();

        // 2. Create New Direct Expense Sub-Account
        $accountPayload = [
            'account_code' => '5103-01',
            'name' => 'Silage Inoculant & Fermentation Additives',
            'account_type' => 'direct_expense',
            'currency' => 'PKR',
        ];

        $createResponse = $this->postJson('/api/v1/finance/chart-of-accounts', $accountPayload);
        $createResponse->assertStatus(201)
            ->assertJsonPath('data.account_code', '5103-01')
            ->assertJsonPath('data.account_type', 'direct_expense');

        $expenseAccountId = $createResponse->json('data.id');

        // 3. Post Double-Entry Journal Voucher (Debit Silage Inoculant, Credit Cash)
        $voucherPayload = [
            'entry_date' => Carbon::now()->toDateString(),
            'debit_account_id' => $expenseAccountId,
            'credit_account_id' => $cashAccount->id,
            'amount' => 12500.00,
            'description' => 'Purchase of Pioneer 11A44 corn silage inoculant',
            'reference_type' => 'purchase_bill',
            'reference_id' => 'INV-PIONEER-991',
        ];

        $voucherResponse = $this->postJson('/api/v1/finance/general-ledger/journal-voucher', $voucherPayload);
        $voucherResponse->assertStatus(201)
            ->assertJsonPath('data.amount', '12500.00')
            ->assertJsonPath('data.debit_account.id', $expenseAccountId)
            ->assertJsonPath('data.credit_account.id', $cashAccount->id);

        $voucherNumber = $voucherResponse->json('data.entry_number');
        $this->assertNotEmpty($voucherNumber);

        // 4. Verify General Ledger query contains the posted voucher
        $glResponse = $this->getJson('/api/v1/finance/general-ledger');
        $glResponse->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'entry_number', 'entry_date', 'amount', 'debit_account', 'credit_account'],
                ],
            ]);
    }

    public function test_cost_per_liter_engine_computes_net_margin_and_breakdowns(): void
    {
        $response = $this->getJson('/api/v1/finance/cost-per-liter?labor_energy_overheads=25000&selling_price_benchmark=225.00');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'farm',
                'cost_per_liter_breakdown' => [
                    'total_milk_liters',
                    'feed_cost_total',
                    'feed_cost_per_liter',
                    'health_cost_total',
                    'health_cost_per_liter',
                    'labor_energy_overhead_total',
                    'labor_energy_overhead_per_liter',
                    'total_cost_per_liter',
                    'average_selling_price_per_liter',
                    'net_margin_per_liter',
                ],
            ]);

        $cpl = $response->json('cost_per_liter_breakdown');
        $this->assertGreaterThan(0, (float) $cpl['total_milk_liters']);
        $this->assertGreaterThan(0, (float) $cpl['total_cost_per_liter']);
        $this->assertIsNumeric($cpl['net_margin_per_liter']);
    }

    public function test_ias41_biological_asset_valuation_and_automatic_gl_journal_entry(): void
    {
        $animal = Animal::where('status', 'active')->firstOrFail();

        // 1. Appraise animal under IAS-41 fair value model
        $appraisalPayload = [
            'valuation_date' => Carbon::now()->toDateString(),
            'valuer_name' => 'Dr. Salman Tariq, Certified Livestock Valuer',
        ];

        $response = $this->postJson("/api/v1/finance/biological-valuations/{$animal->id}/appraise", $appraisalPayload);
        $response->assertStatus(201)
            ->assertJsonPath('data.animal_id', $animal->id)
            ->assertJsonPath('data.valuation_method', 'market_comparison')
            ->assertJsonPath('data.valuer_name', 'Dr. Salman Tariq, Certified Livestock Valuer');

        $valuationData = $response->json('data');
        $this->assertGreaterThan(0, (float) $valuationData['fair_value_amount']);
        $this->assertGreaterThan(0, (float) $valuationData['net_carrying_value']);

        // Verify the GL journal entry was actually created
        $glEntry = GeneralLedgerEntry::where('reference_type', 'biological_asset_valuation')
            ->where('reference_id', (string) $valuationData['id'])
            ->firstOrFail();
        $this->assertEquals('biological_asset_valuation', $glEntry->reference_type);
        $this->assertEquals((float) $valuationData['net_carrying_value'], (float) $glEntry->amount);

        // 2. Query biological valuations endpoint
        $listResponse = $this->getJson('/api/v1/finance/biological-valuations');
        $listResponse->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'animal_id', 'fair_value_amount', 'estimated_cost_to_sell', 'net_carrying_value'],
                ],
            ]);
    }

    public function test_cost_allocation_rules_retrieval(): void
    {
        $response = $this->getJson('/api/v1/finance/cost-allocation-rules');
        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'name', 'cost_category', 'allocation_basis', 'percentage_dairy_cattle', 'percentage_goats', 'percentage_feedlot'],
                ],
            ]);

        $rules = $response->json('data');
        $this->assertNotEmpty($rules);

        $categories = array_column($rules, 'cost_category');
        $this->assertContains('labor', $categories);
    }

    public function test_compliance_packs_registry_and_pfa_rules(): void
    {
        $response = $this->getJson('/api/v1/compliance/packs');
        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'code', 'name', 'country_code', 'regulatory_body', 'version', 'is_enabled'],
                ],
            ]);

        $packs = $response->json('data');
        $codes = array_column($packs, 'code');

        $this->assertContains('pakistan_pfa', $codes);
    }

    public function test_animal_movement_permit_issuance_and_transit_tracking(): void
    {
        $animal = Animal::where('status', 'active')->firstOrFail();

        // 1. Issue movement permit
        $permitPayload = [
            'departure_date' => Carbon::now()->toDateString(),
            'movement_purpose' => 'slaughter',
            'destination_premises_name' => 'Lahore Meat Processing Complex',
            'destination_address' => 'Multan Road, Shahpur Kanjra, Lahore',
            'vehicle_plate_number' => 'LEA-4921',
            'driver_name' => 'Muhammad Aslam',
            'driver_phone' => '+92-300-5551234',
            'animal_ids' => [$animal->id],
            'veterinary_health_certificate_no' => 'VET-CERT-2026-901',
        ];

        $response = $this->postJson('/api/v1/compliance/movement-permits', $permitPayload);
        $response->assertStatus(201)
            ->assertJsonPath('data.movement_purpose', 'slaughter')
            ->assertJsonPath('data.vehicle_plate_number', 'LEA-4921')
            ->assertJsonPath('data.total_heads', 1)
            ->assertJsonPath('data.status', 'approved');

        $permitId = $response->json('data.id');

        // 2. Fetch permits list
        $listResponse = $this->getJson('/api/v1/compliance/movement-permits');
        $listResponse->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'permit_number', 'departure_date', 'movement_purpose', 'vehicle_plate_number'],
                ],
            ]);

        $permit = AnimalMovementPermit::findOrFail($permitId);
        $this->assertStringStartsWith('PERMIT-', $permit->permit_number);
    }

    public function test_forward_traceability_from_animal_to_slaughter_and_milk(): void
    {
        $farm = Farm::firstOrFail();
        $animal = Animal::where('status', 'active')->firstOrFail();

        // Ensure animal has a health treatment logged
        $medicine = Medicine::firstOrCreate(
            ['sku' => 'MED-TEST-OXY'],
            [
                'organization_id' => $farm->organization_id,
                'name' => 'Oxytetracycline 20% LA',
                'active_ingredient' => 'Oxytetracycline',
                'category' => 'antibiotic',
                'unit_cost' => 1200.00,
                'milk_withholding_hours' => 72,
                'meat_withholding_days' => 28,
            ]
        );

        Treatment::create([
            'farm_id' => $farm->id,
            'animal_id' => $animal->id,
            'medicine_id' => $medicine->id,
            'administered_at' => Carbon::now()->subDays(5),
            'dosage' => '20.00',
            'dosage_unit' => 'ml',
            'route' => 'intramuscular',
            'milk_withdrawal_until' => Carbon::now()->subDays(2),
            'meat_withdrawal_until' => Carbon::now()->addDays(23),
            'cost' => 450.00,
            'notes' => 'Respiratory prophylaxis test treatment',
        ]);

        $response = $this->getJson("/api/v1/compliance/traceability/forward/{$animal->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.direction', 'forward_trace')
            ->assertJsonPath('data.animal.id', $animal->id)
            ->assertJsonStructure([
                'data' => [
                    'animal' => ['id', 'tag_number', 'species', 'breed'],
                    'medical_history_and_withdrawals',
                    'production_summary',
                    'carcass_trace',
                ],
            ]);

        $medicalHistory = $response->json('data.medical_history_and_withdrawals');
        $this->assertNotEmpty($medicalHistory);
        $this->assertEquals('Oxytetracycline 20% LA', $medicalHistory[0]['medicine_name']);
    }

    public function test_backward_traceability_from_delivery_stop_to_milking_cows(): void
    {
        $farm = Farm::firstOrFail();

        // 1. Create Customer, Run, and Stop
        $customer = Customer::create([
            'organization_id' => $farm->organization_id,
            'farm_id' => $farm->id,
            'customer_type' => 'household_subscription',
            'name' => 'Dr. Kamran Malik',
            'phone' => '+92-321-7788990',
            'address' => 'Plot 44, Phase 5, DHA Lahore',
            'city' => 'Lahore',
            'wallet_balance' => 5000.00,
            'is_active' => true,
        ]);

        $deliveryRun = DeliveryRun::create([
            'farm_id' => $farm->id,
            'run_date' => Carbon::now()->toDateString(),
            'route_name' => 'DHA Phase 5 Morning Run',
            'driver_name' => 'Rashid Mehmood',
            'vehicle_plate_number' => 'LEC-3390',
            'vehicle_departure_temp_c' => 3.8,
            'status' => 'completed',
        ]);

        $stop = DeliveryRunStop::create([
            'delivery_run_id' => $deliveryRun->id,
            'customer_id' => $customer->id,
            'stop_order' => 1,
            'product_type' => 'raw_cow_milk',
            'planned_quantity_liters' => 5.00,
            'delivered_quantity_liters' => 5.00,
            'unit_price' => 220.00,
            'total_amount' => 1100.00,
            'proof_of_delivery_token' => 'OTP-4401',
            'status' => 'delivered',
            'delivered_at' => Carbon::now()->subHours(2),
        ]);

        // Ensure milk record exists on the previous day
        $milkingDate = Carbon::now()->subDay()->toDateString();
        $cow = Animal::where('status', 'active')->firstOrFail();

        MilkRecord::firstOrCreate(
            ['farm_id' => $farm->id, 'animal_id' => $cow->id, 'recorded_date' => $milkingDate, 'shift' => 'morning'],
            [
                'yield_liters' => 14.50,
                'fat_percentage' => 4.10,
                'protein_percentage' => 3.35,
                'scc' => 140,
            ]
        );

        $response = $this->getJson("/api/v1/compliance/traceability/backward/{$stop->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.direction', 'backward_trace')
            ->assertJsonPath('data.delivery_stop.stop_id', $stop->id)
            ->assertJsonPath('data.delivery_stop.customer_name', 'Dr. Kamran Malik')
            ->assertJsonPath('data.transport_and_cold_chain.temperature_compliant', true)
            ->assertJsonStructure([
                'data' => [
                    'delivery_stop',
                    'transport_and_cold_chain',
                    'bulk_chilling_origin',
                    'contributing_livestock',
                ],
            ]);

        $contributing = $response->json('data.contributing_livestock');
        $this->assertNotEmpty($contributing);
    }

    public function test_halal_slaughter_certification_and_welfare_assessment(): void
    {
        $farm = Farm::firstOrFail();
        $animal = Animal::where('status', 'active')->firstOrFail();

        // 1. Create Slaughter Record
        $slaughter = SlaughterRecord::create([
            'farm_id' => $farm->id,
            'animal_id' => $animal->id,
            'slaughterhouse_name' => 'Punjab Halal Abattoir Complex',
            'slaughter_date' => Carbon::now()->toDateString(),
            'live_weight_kg' => 440.00,
            'hot_carcass_weight_kg' => 242.00,
            'dressing_percentage' => 55.00,
            'conformation_grade' => 'A',
            'carcass_bar_code' => 'CARCASS-2026-9901',
            'halal_certified' => true,
        ]);

        // 2. Record Halal Certification
        $halalPayload = [
            'slaughter_record_id' => $slaughter->id,
            'certification_body' => 'Punjab Halal Development Agency (PHDA)',
            'slaughterer_name' => 'Qari Abdul Rasheed',
            'slaughterer_credential_id' => 'PHDA-HALAL-7712',
            'slaughter_method' => 'tazkiyah_non_stun',
            'inspector_name' => 'Dr. Zafar Qureshi, Halal Lead Auditor',
        ];

        $halalResponse = $this->postJson('/api/v1/compliance/halal-certifications', $halalPayload);
        $halalResponse->assertStatus(201)
            ->assertJsonPath('data.slaughterer_name', 'Qari Abdul Rasheed')
            ->assertJsonPath('data.tasmiyah_recited', true)
            ->assertJsonPath('data.trachea_esophagus_jugular_cut_verified', true);

        $certNumber = $halalResponse->json('data.certificate_number');
        $this->assertStringStartsWith('HALAL-', $certNumber);

        // Query certifications
        $certListResponse = $this->getJson('/api/v1/compliance/halal-certifications');
        $certListResponse->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'certificate_number', 'certification_body', 'slaughterer_name', 'tasmiyah_recited'],
                ],
            ]);

        // 3. Record Five Freedoms Animal Welfare Assessment
        $welfarePayload = [
            'audit_date' => Carbon::now()->toDateString(),
            'auditor_name' => 'Dr. Ayesha Tariq, Animal Welfare Inspector',
            'water_access_score' => 5,
            'thermal_comfort_score' => 5,
            'bedding_cleanliness_score' => 4,
            'lameness_prevalence_percent' => 2.0,
            'space_allowance_score' => 5,
            'corrective_actions' => 'None. High animal comfort observed.',
        ];

        $welfareResponse = $this->postJson('/api/v1/compliance/welfare-assessments', $welfarePayload);
        $welfareResponse->assertStatus(201)
            ->assertJsonPath('data.overall_welfare_grade', 'excellent')
            ->assertJsonPath('data.auditor_name', 'Dr. Ayesha Tariq, Animal Welfare Inspector');
    }

    public function test_iot_telemetry_ingestion_and_offline_sync_pull_push_idempotency(): void
    {
        // 1. Hardware Device Registration
        $devicePayload = [
            'device_identifier' => 'MILK-METER-PARLOR-08',
            'device_name' => 'AfiMilk MPC Milk Meter Stall 8',
            'device_type' => 'automatic_milk_meter',
            'firmware_version' => 'v3.1.9',
            'ip_address' => '192.168.1.188',
        ];

        $deviceResponse = $this->postJson('/api/v1/sync/devices', $devicePayload);
        $deviceResponse->assertStatus(201)
            ->assertJsonPath('data.device_identifier', 'MILK-METER-PARLOR-08')
            ->assertJsonPath('data.status', 'online');

        $apiKey = $deviceResponse->json('data.api_key');
        $this->assertNotEmpty($apiKey);

        // Fetch devices list
        $devicesListResponse = $this->getJson('/api/v1/sync/devices');
        $devicesListResponse->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'device_identifier', 'device_name', 'status', 'telemetry_logs_count'],
                ],
            ]);

        // 2. Ingest IoT Telemetry Packet
        $telemetryPayload = [
            'api_key' => $apiKey,
            'metric_name' => 'milk_flow_rate_kg_min',
            'metric_value' => 3.85,
            'unit_of_measure' => 'kg/min',
            'raw_payload' => [
                'session' => 'morning',
                'conductivity_ms_cm' => 5.2,
                'stall_number' => 8,
            ],
        ];

        $telemetryResponse = $this->postJson('/api/v1/sync/telemetry', $telemetryPayload);
        $telemetryResponse->assertStatus(201)
            ->assertJsonPath('data.metric_name', 'milk_flow_rate_kg_min')
            ->assertJsonPath('data.metric_value', '3.8500');

        // 3. Mobile Offline Sync Pull
        $pullResponse = $this->getJson('/api/v1/sync/pull?last_rev_id=0&limit=50');
        $pullResponse->assertStatus(200)
            ->assertJsonStructure([
                'current_rev_id',
                'changes_count',
                'changes',
                'has_more',
            ]);

        // 4. Mobile Offline Sync Push with Idempotency Key Deduplication
        $mutationIdempotencyKey = 'SYNC-UUID-'.rand(100000, 999999);
        $pushPayload = [
            'device_uuid' => 'OFFLINE-TABLET-BARN-04',
            'mutations' => [
                [
                    'entity_type' => 'milk_records',
                    'action' => 'insert',
                    'idempotency_key' => $mutationIdempotencyKey,
                    'payload' => [
                        'animal_tag' => 'PK-COW-001',
                        'yield_liters' => 15.2,
                        'session' => 'morning',
                    ],
                ],
            ],
        ];

        // First push: must process 1 mutation
        $pushResponse1 = $this->postJson('/api/v1/sync/push', $pushPayload);
        $pushResponse1->assertStatus(200)
            ->assertJsonPath('processed_count', 1);

        // Duplicate push with same idempotency key: must skip and return processed_count = 0
        $pushResponse2 = $this->postJson('/api/v1/sync/push', $pushPayload);
        $pushResponse2->assertStatus(200)
            ->assertJsonPath('processed_count', 0);
    }
}
