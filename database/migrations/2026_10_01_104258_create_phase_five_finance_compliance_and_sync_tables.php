<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Chart of Accounts (COA)
        Schema::create('chart_of_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('account_code', 50);
            $table->string('name', 100);
            $table->string('account_type', 50); // asset, liability, equity, revenue, direct_expense, overhead_expense
            $table->foreignId('parent_account_id')->nullable()->constrained('chart_of_accounts')->nullOnDelete();
            $table->string('currency', 10)->default('PKR');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['organization_id', 'account_code']);
        });

        // 2. Double-Entry General Ledger Entries
        Schema::create('general_ledger_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('farm_id')->constrained()->cascadeOnDelete();
            $table->string('entry_number', 50);
            $table->date('entry_date');
            $table->foreignId('debit_account_id')->constrained('chart_of_accounts');
            $table->foreignId('credit_account_id')->constrained('chart_of_accounts');
            $table->decimal('amount', 14, 2);
            $table->string('currency', 10)->default('PKR');
            $table->string('reference_type', 100)->nullable(); // milk_sale, feed_purchase, biological_valuation
            $table->string('reference_id', 50)->nullable();
            $table->text('description');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['farm_id', 'entry_date']);
        });

        // 3. IAS-41 Biological Asset Valuations
        Schema::create('biological_asset_valuations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('farm_id')->constrained()->cascadeOnDelete();
            $table->foreignId('animal_id')->constrained()->cascadeOnDelete();
            $table->date('valuation_date');
            $table->decimal('fair_value_amount', 12, 2);
            $table->decimal('estimated_cost_to_sell', 12, 2);
            $table->decimal('net_carrying_value', 12, 2);
            $table->string('valuation_method', 50)->default('market_comparison'); // market_comparison, discounted_cash_flow, cost_depreciated
            $table->string('maturity_stage', 50); // mature_lactating, pregnant_heifer, weaned_grower, breeding_sire
            $table->string('valuer_name', 100)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['farm_id', 'valuation_date']);
        });

        // 4. Indirect Cost Allocation Rules
        Schema::create('cost_allocation_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('farm_id')->constrained()->cascadeOnDelete();
            $table->string('name', 100);
            $table->string('cost_category', 50); // labor, electricity_diesel, bedding_waste, depreciation
            $table->string('allocation_basis', 50)->default('headcount_ratio'); // headcount_ratio, milk_volume_ratio, direct_hours
            $table->decimal('percentage_dairy_cattle', 5, 2)->default(65.00);
            $table->decimal('percentage_goats', 5, 2)->default(25.00);
            $table->decimal('percentage_feedlot', 5, 2)->default(10.00);
            $table->timestamps();
        });

        // 5. Compliance Packs Registry
        Schema::create('compliance_packs', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique(); // pakistan_pfa, gcc_adafsa, eu_traceability, us_usda
            $table->string('name', 100);
            $table->string('country_code', 10);
            $table->string('regulatory_body', 100);
            $table->string('version', 20)->default('1.0.0');
            $table->boolean('is_enabled')->default(true);
            $table->json('configuration_json')->nullable();
            $table->timestamps();
        });

        // 6. Animal Movement & Transport Permits
        Schema::create('animal_movement_permits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('farm_id')->constrained()->cascadeOnDelete();
            $table->string('permit_number', 50)->unique();
            $table->date('departure_date');
            $table->string('movement_purpose', 50); // slaughter, sale_transfer, exhibition_show, pasture_transhumance
            $table->string('origin_premises_id', 100);
            $table->string('destination_premises_name', 150);
            $table->string('destination_premises_id', 100)->nullable();
            $table->string('destination_address', 255);
            $table->string('vehicle_plate_number', 50);
            $table->string('driver_name', 100);
            $table->string('driver_phone', 50)->nullable();
            $table->json('animal_ids_json'); // array of animal IDs and tag numbers
            $table->integer('total_heads');
            $table->string('veterinary_health_certificate_no', 100)->nullable();
            $table->string('status', 50)->default('draft'); // draft, approved, dispatched, completed, cancelled
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // 7. Halal Slaughter Certifications & Traceability
        Schema::create('halal_slaughter_certifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('farm_id')->constrained()->cascadeOnDelete();
            $table->foreignId('slaughter_record_id')->nullable()->constrained('slaughter_records')->nullOnDelete();
            $table->string('certificate_number', 50)->unique();
            $table->string('certification_body', 150); // Punjab Halal Development Agency, SANHA, Halal Research Council
            $table->string('slaughterer_name', 100);
            $table->string('slaughterer_credential_id', 100);
            $table->string('slaughter_method', 50)->default('tazkiyah_non_stun'); // tazkiyah_non_stun, reversible_stunning_approved
            $table->boolean('tasmiyah_recited')->default(true);
            $table->boolean('trachea_esophagus_jugular_cut_verified')->default(true);
            $table->string('inspector_name', 100);
            $table->dateTime('verified_at');
            $table->timestamps();
        });

        // 8. Animal Welfare & 5-Freedoms Audits
        Schema::create('animal_welfare_assessments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('farm_id')->constrained()->cascadeOnDelete();
            $table->date('audit_date');
            $table->string('auditor_name', 100);
            $table->integer('water_access_score')->default(5); // 1 to 5
            $table->integer('thermal_comfort_score')->default(5); // 1 to 5
            $table->integer('bedding_cleanliness_score')->default(5); // 1 to 5
            $table->decimal('lameness_prevalence_percent', 5, 2)->default(0.00);
            $table->integer('space_allowance_score')->default(5); // 1 to 5
            $table->string('overall_welfare_grade', 30)->default('excellent'); // excellent, acceptable, needs_improvement, critical_breach
            $table->text('corrective_actions')->nullable();
            $table->timestamps();
        });

        // 9. IoT Hardware Device Registries
        Schema::create('device_registries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('farm_id')->constrained()->cascadeOnDelete();
            $table->string('device_identifier', 100)->unique();
            $table->string('device_name', 100);
            $table->string('device_type', 50); // rfid_stick_reader, automatic_milk_meter, bulk_tank_temp_probe, digital_platform_scale, weather_station
            $table->string('api_key', 100)->unique();
            $table->string('firmware_version', 50)->nullable();
            $table->string('ip_address', 50)->nullable();
            $table->decimal('battery_percentage', 5, 2)->nullable();
            $table->dateTime('last_heartbeat_at')->nullable();
            $table->string('status', 30)->default('online'); // online, offline, maintenance
            $table->timestamps();
        });

        // 10. IoT Device Telemetry Time-Series Logs
        Schema::create('device_telemetry_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('device_registry_id')->constrained('device_registries')->cascadeOnDelete();
            $table->dateTime('recorded_at');
            $table->string('metric_name', 50); // milk_flow_rate_kg_min, weight_kg, tank_temp_c, rfid_tag_scanned
            $table->decimal('metric_value', 18, 4);
            $table->string('unit_of_measure', 20)->nullable();
            $table->json('raw_payload_json')->nullable();
            $table->timestamps();

            $table->index(['device_registry_id', 'recorded_at']);
        });

        // 11. Mobile Sync Client Registries
        Schema::create('sync_client_registries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('device_uuid', 100)->unique();
            $table->string('app_version', 50)->default('1.0.0');
            $table->string('platform', 30)->default('android'); // android, ios, windows_tablet
            $table->bigInteger('last_sync_rev_id')->default(0);
            $table->dateTime('last_synced_at')->nullable();
            $table->timestamps();
        });

        // 12. Offline Change Log for Delta Sync
        Schema::create('sync_change_logs', function (Blueprint $table) {
            $table->id(); // sequential revision ID
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('entity_type', 100); // animals, milk_records, treatments, weight_records
            $table->string('entity_id', 50);
            $table->string('action', 20); // insert, update, delete
            $table->string('idempotency_key', 100)->nullable()->index();
            $table->json('delta_payload_json')->nullable();
            $table->dateTime('timestamp');
            $table->timestamps();

            $table->index(['organization_id', 'id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sync_change_logs');
        Schema::dropIfExists('sync_client_registries');
        Schema::dropIfExists('device_telemetry_logs');
        Schema::dropIfExists('device_registries');
        Schema::dropIfExists('animal_welfare_assessments');
        Schema::dropIfExists('halal_slaughter_certifications');
        Schema::dropIfExists('animal_movement_permits');
        Schema::dropIfExists('compliance_packs');
        Schema::dropIfExists('cost_allocation_rules');
        Schema::dropIfExists('biological_asset_valuations');
        Schema::dropIfExists('general_ledger_entries');
        Schema::dropIfExists('chart_of_accounts');
    }
};
