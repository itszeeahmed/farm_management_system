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
        // 1. Feedlot Gains & Performance
        Schema::create('feedlot_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('farm_id')->constrained()->cascadeOnDelete();
            $table->foreignId('animal_id')->constrained()->cascadeOnDelete();
            $table->foreignId('pen_id')->nullable()->constrained()->nullOnDelete();
            $table->date('intake_date');
            $table->decimal('intake_weight_kg', 6, 2);
            $table->decimal('current_weight_kg', 6, 2);
            $table->decimal('target_slaughter_weight_kg', 6, 2)->default(550.00);
            $table->integer('days_on_feed')->default(0);
            $table->decimal('average_daily_gain_kg', 5, 3)->default(0.000);
            $table->decimal('total_gain_kg', 6, 2)->default(0.00);
            $table->decimal('total_feed_consumed_kg_dm', 8, 2)->default(0.00);
            $table->decimal('feed_conversion_ratio', 5, 2)->default(0.00);
            $table->decimal('daily_ration_cost', 10, 2)->default(0.00);
            $table->decimal('cost_per_kg_gain', 10, 2)->default(0.00);
            $table->string('status', 30)->default('active'); // active, ready_for_slaughter, slaughtered, sold
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // 2. Slaughter & Carcass Grading
        Schema::create('slaughter_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('farm_id')->constrained()->cascadeOnDelete();
            $table->foreignId('animal_id')->constrained()->cascadeOnDelete();
            $table->string('slaughterhouse_name', 150);
            $table->date('slaughter_date');
            $table->decimal('live_weight_kg', 6, 2);
            $table->decimal('hot_carcass_weight_kg', 6, 2);
            $table->decimal('cold_carcass_weight_kg', 6, 2)->nullable();
            $table->decimal('dressing_percentage', 5, 2);
            $table->string('conformation_grade', 50)->default('prime');
            $table->integer('fat_score')->default(3);
            $table->boolean('meat_withdrawal_cleared')->default(true);
            $table->string('carcass_bar_code', 100)->nullable();
            $table->string('technician_name', 100)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // 3. Fleece & Fiber Records (Sheep wool, Cashmere, Mohair, Camel hair)
        Schema::create('fleece_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('farm_id')->constrained()->cascadeOnDelete();
            $table->foreignId('animal_id')->constrained()->cascadeOnDelete();
            $table->date('shearing_date');
            $table->string('fleece_type', 50)->default('wool'); // wool, mohair, cashmere, camel_hair
            $table->decimal('grease_fleece_weight_kg', 5, 2);
            $table->decimal('clean_fleece_weight_kg', 5, 2);
            $table->decimal('clean_yield_percentage', 5, 2);
            $table->decimal('micron_grade', 4, 1); // e.g. 18.5 um
            $table->string('quality_tier', 50)->default('fine'); // ultrafine, superfine, fine, medium, coarse
            $table->decimal('staple_length_mm', 5, 1)->nullable();
            $table->string('shearer_name', 100)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // 4. Specialized Species Attributes (Camels, Buffaloes, Equines)
        Schema::create('specialized_species_attributes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('animal_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('species_type', 50); // camel, buffalo, sheep, goat
            $table->decimal('hump_condition_score', 3, 1)->nullable(); // 1.0 to 5.0
            $table->string('draft_work_type', 50)->nullable(); // riding, racing, pack, dairy, meat
            $table->boolean('racing_eligibility_status')->default(false);
            $table->string('veterinary_passport_number', 100)->nullable();
            $table->string('microchip_transponder_rfid', 100)->nullable();
            $table->timestamps();
        });

        // 5. Pasture Plots & Paddocks
        Schema::create('pasture_plots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('farm_id')->constrained()->cascadeOnDelete();
            $table->string('name', 100);
            $table->string('code', 50);
            $table->decimal('area_hectares', 6, 2);
            $table->string('forage_type', 100)->default('alfalfa');
            $table->decimal('soil_ph', 3, 1)->nullable();
            $table->integer('target_rest_days')->default(28);
            $table->decimal('current_biomass_kg_dm_per_ha', 8, 2)->default(2500.00);
            $table->string('status', 50)->default('resting'); // resting, grazing, recovering, fallow
            $table->dateTime('last_grazed_at')->nullable();
            $table->timestamps();
        });

        // 6. Grazing Logs
        Schema::create('grazing_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('farm_id')->constrained()->cascadeOnDelete();
            $table->foreignId('pasture_plot_id')->constrained()->cascadeOnDelete();
            $table->foreignId('animal_group_id')->nullable()->constrained()->nullOnDelete();
            $table->date('entry_date');
            $table->date('exit_date')->nullable();
            $table->integer('stocking_density_heads');
            $table->decimal('livestock_units_per_ha', 5, 2);
            $table->decimal('pre_graze_height_cm', 5, 1);
            $table->decimal('post_graze_residual_height_cm', 5, 1)->nullable();
            $table->decimal('dry_matter_utilized_kg_ha', 8, 2)->default(0.00);
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // 7. Climate Readings Table enhancements
        Schema::table('climate_readings', function (Blueprint $table) {
            if (! Schema::hasColumn('climate_readings', 'zone_id')) {
                $table->foreignId('zone_id')->nullable()->after('barn_id')->constrained('farm_zones')->nullOnDelete();
            }
            if (! Schema::hasColumn('climate_readings', 'air_velocity_m_s')) {
                $table->decimal('air_velocity_m_s', 4, 1)->nullable()->after('relative_humidity_percent');
            }
            if (! Schema::hasColumn('climate_readings', 'solar_radiation_w_m2')) {
                $table->decimal('solar_radiation_w_m2', 6, 1)->nullable()->after('air_velocity_m_s');
            }
            if (! Schema::hasColumn('climate_readings', 'cooling_actuator_activated')) {
                $table->boolean('cooling_actuator_activated')->default(false)->after('heat_stress_level');
            }
        });

        // 8. Warehouses
        Schema::create('warehouses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('farm_id')->constrained()->cascadeOnDelete();
            $table->string('name', 100);
            $table->string('code', 50);
            $table->string('type', 50)->default('general'); // feed_store, cold_pharmacy, spare_parts, dairy_packaging, general
            $table->boolean('temperature_controlled')->default(false);
            $table->decimal('target_temp_c', 4, 1)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // 9. Universal SKU Inventory Items
        Schema::create('inventory_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('farm_id')->constrained()->cascadeOnDelete();
            $table->foreignId('warehouse_id')->nullable()->constrained()->nullOnDelete();
            $table->string('category', 50)->default('general'); // feed, medicine, semen, spare_parts, sanitizer, packaging, general
            $table->string('sku', 50);
            $table->string('name', 150);
            $table->string('unit_of_measure', 20)->default('kg');
            $table->decimal('current_stock_quantity', 10, 2)->default(0.00);
            $table->decimal('reorder_level_quantity', 10, 2)->default(10.00);
            $table->decimal('safety_stock_quantity', 10, 2)->default(5.00);
            $table->decimal('unit_cost', 10, 2)->default(0.00);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // 10. Inventory Transactions
        Schema::create('inventory_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('farm_id')->constrained()->cascadeOnDelete();
            $table->foreignId('inventory_item_id')->constrained()->cascadeOnDelete();
            $table->foreignId('warehouse_id')->nullable()->constrained()->nullOnDelete();
            $table->string('transaction_type', 50); // goods_receipt, issue_to_farm, transfer, wastage_loss, cycle_count_adjustment
            $table->decimal('quantity', 10, 2);
            $table->decimal('unit_cost', 10, 2);
            $table->decimal('total_cost', 12, 2);
            $table->string('batch_number', 50)->nullable();
            $table->date('expiry_date')->nullable();
            $table->string('reference_type', 100)->nullable();
            $table->string('reference_id', 50)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // 11. Farm Assets & Machinery
        Schema::create('farm_assets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('farm_id')->constrained()->cascadeOnDelete();
            $table->string('name', 150);
            $table->string('asset_code', 50);
            $table->string('category', 50); // milking_parlor, bulk_tank, tractor, mixer_wagon, generator, solar_pv, platform_scale
            $table->string('make', 100)->nullable();
            $table->string('model', 100)->nullable();
            $table->string('serial_number', 100)->nullable();
            $table->date('purchase_date')->nullable();
            $table->decimal('purchase_cost', 12, 2)->default(0.00);
            $table->string('meter_type', 20)->default('hours'); // hours, km, none
            $table->decimal('current_meter_reading', 10, 1)->default(0.0);
            $table->string('status', 50)->default('operational'); // operational, under_maintenance, decommissioned
            $table->timestamps();
        });

        // 12. Maintenance Logs
        Schema::create('maintenance_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('farm_asset_id')->constrained()->cascadeOnDelete();
            $table->string('maintenance_type', 50)->default('preventative'); // preventative, breakdown_repair, oil_filter_service, calibration
            $table->date('service_date');
            $table->string('technician_name', 100);
            $table->decimal('meter_reading', 10, 1)->default(0.0);
            $table->decimal('downtime_hours', 5, 2)->default(0.00);
            $table->decimal('parts_cost', 10, 2)->default(0.00);
            $table->decimal('labor_cost', 10, 2)->default(0.00);
            $table->decimal('total_cost', 10, 2)->default(0.00);
            $table->date('next_service_due_date')->nullable();
            $table->decimal('next_service_due_meter', 10, 1)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // 13. Customers & Direct Subscriptions CRM
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('farm_id')->constrained()->cascadeOnDelete();
            $table->string('customer_type', 50)->default('household_subscription'); // household_subscription, retail_store, restaurant, bulk_processor
            $table->string('name', 150);
            $table->string('phone', 50);
            $table->string('email', 100)->nullable();
            $table->string('address', 255);
            $table->string('city', 100)->default('Lahore');
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->decimal('wallet_balance', 10, 2)->default(0.00);
            $table->string('status', 50)->default('active'); // active, suspended, cancelled
            $table->timestamps();
        });

        // 14. Customer Subscriptions
        Schema::create('customer_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('farm_id')->constrained()->cascadeOnDelete();
            $table->string('product_type', 50)->default('raw_cow_milk'); // raw_cow_milk, raw_goat_milk, pasteurized_cow_milk, yogurt, cheese
            $table->decimal('daily_quantity_liters', 6, 2)->default(2.00);
            $table->decimal('unit_price_per_liter', 10, 2)->default(220.00);
            $table->string('frequency', 50)->default('daily'); // daily, alternate_days, weekly, weekdays_only, weekends_only
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->boolean('is_paused')->default(false);
            $table->date('pause_start_date')->nullable();
            $table->date('pause_end_date')->nullable();
            $table->string('status', 50)->default('active'); // active, paused, cancelled
            $table->timestamps();
        });

        // 15. Customer Wallet Ledger
        Schema::create('customer_wallet_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->string('transaction_type', 50); // prepaid_topup, delivery_deduction, refund, manual_adjustment
            $table->decimal('amount', 10, 2);
            $table->decimal('opening_balance', 10, 2);
            $table->decimal('closing_balance', 10, 2);
            $table->string('reference_id', 100)->nullable();
            $table->string('notes', 255)->nullable();
            $table->timestamps();
        });

        // 16. Delivery Runs (Direct Distribution)
        Schema::create('delivery_runs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('farm_id')->constrained()->cascadeOnDelete();
            $table->date('run_date');
            $table->string('route_name', 100);
            $table->string('driver_name', 100);
            $table->string('vehicle_plate_number', 50)->nullable();
            $table->decimal('vehicle_departure_temp_c', 4, 1)->default(3.8);
            $table->decimal('total_liters_planned', 8, 2)->default(0.00);
            $table->decimal('total_liters_delivered', 8, 2)->default(0.00);
            $table->string('status', 50)->default('in_progress'); // draft, in_progress, completed
            $table->timestamps();
        });

        // 17. Delivery Run Stops
        Schema::create('delivery_run_stops', function (Blueprint $table) {
            $table->id();
            $table->foreignId('delivery_run_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_subscription_id')->nullable()->constrained()->nullOnDelete();
            $table->integer('stop_sequence')->default(1);
            $table->decimal('planned_quantity_liters', 6, 2);
            $table->decimal('delivered_quantity_liters', 6, 2)->default(0.00);
            $table->decimal('unit_price', 10, 2);
            $table->decimal('total_amount', 10, 2);
            $table->integer('empty_bottles_returned')->default(0);
            $table->string('proof_of_delivery_type', 50)->default('otp'); // otp, digital_signature, photo_dropoff, cash_on_delivery
            $table->string('proof_of_delivery_token', 100)->nullable();
            $table->string('status', 50)->default('pending'); // pending, delivered, skipped, failed
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('delivery_run_stops');
        Schema::dropIfExists('delivery_runs');
        Schema::dropIfExists('customer_wallet_transactions');
        Schema::dropIfExists('customer_subscriptions');
        Schema::dropIfExists('customers');
        Schema::dropIfExists('maintenance_logs');
        Schema::dropIfExists('farm_assets');
        Schema::dropIfExists('inventory_transactions');
        Schema::dropIfExists('inventory_items');
        Schema::dropIfExists('warehouses');

        if (Schema::hasTable('climate_readings')) {
            Schema::table('climate_readings', function (Blueprint $table) {
                if (Schema::hasColumn('climate_readings', 'cooling_actuator_activated')) {
                    $table->dropColumn('cooling_actuator_activated');
                }
                if (Schema::hasColumn('climate_readings', 'solar_radiation_w_m2')) {
                    $table->dropColumn('solar_radiation_w_m2');
                }
                if (Schema::hasColumn('climate_readings', 'air_velocity_m_s')) {
                    $table->dropColumn('air_velocity_m_s');
                }
                if (Schema::hasColumn('climate_readings', 'zone_id')) {
                    $table->dropConstrainedForeignId('zone_id');
                }
            });
        }

        Schema::dropIfExists('grazing_logs');
        Schema::dropIfExists('pasture_plots');
        Schema::dropIfExists('specialized_species_attributes');
        Schema::dropIfExists('fleece_records');
        Schema::dropIfExists('slaughter_records');
        Schema::dropIfExists('feedlot_records');
    }
};
