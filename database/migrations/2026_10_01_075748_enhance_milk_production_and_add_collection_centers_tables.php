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
        Schema::create('bulk_tanks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('farm_id')->constrained()->cascadeOnDelete();
            $table->string('tank_code', 50);
            $table->string('model_name', 100)->nullable();
            $table->decimal('capacity_liters', 8, 2);
            $table->decimal('current_volume_liters', 8, 2)->default(0);
            $table->decimal('target_temperature_c', 4, 1)->default(3.5);
            $table->decimal('current_temperature_c', 4, 1)->nullable();
            $table->string('cooling_status', 50)->default('idle'); // idle, cooling, holding, error
            $table->string('agitator_status', 50)->default('off'); // off, continuous, cyclic
            $table->timestamp('last_cip_cleaned_at')->nullable();
            $table->foreignId('last_cip_cleaned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->boolean('is_sanitized')->default(true);
            $table->string('status', 50)->default('active'); // active, maintenance, decommissioned
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['farm_id', 'tank_code']);
        });

        Schema::create('milk_dispatches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('farm_id')->constrained()->cascadeOnDelete();
            $table->foreignId('bulk_tank_id')->nullable()->constrained('bulk_tanks')->nullOnDelete();
            $table->string('dispatch_number', 50)->unique();
            $table->string('buyer_name', 150);
            $table->string('driver_name', 100)->nullable();
            $table->string('driver_phone', 50)->nullable();
            $table->string('tanker_plate_number', 50);
            $table->string('seal_number', 50);
            $table->decimal('dispatched_volume_liters', 8, 2);
            $table->decimal('temperature_c', 4, 1);
            $table->decimal('composite_fat_percentage', 4, 2);
            $table->decimal('composite_snf_percentage', 4, 2);
            $table->unsignedInteger('composite_scc')->nullable();
            $table->decimal('unit_price_pkr', 8, 2);
            $table->decimal('total_price_pkr', 10, 2);
            $table->timestamp('dispatched_at')->useCurrent();
            $table->foreignId('authorized_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status', 50)->default('dispatched'); // pending, dispatched, delivered, rejected
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['farm_id', 'dispatched_at']);
        });

        Schema::table('milk_sessions', function (Blueprint $table) {
            $table->foreignId('bulk_tank_id')->nullable()->after('farm_id')->constrained('bulk_tanks')->nullOnDelete();
            $table->string('parlor_identifier', 50)->nullable()->after('bulk_tank_id');
            $table->decimal('ambient_temp_c', 4, 1)->nullable()->after('bulk_tank_temperature_c');
            $table->decimal('chiller_temp_c', 4, 1)->nullable()->after('ambient_temp_c');
            $table->timestamp('started_at')->nullable()->after('notes');
            $table->timestamp('ended_at')->nullable()->after('started_at');
        });

        Schema::table('milk_records', function (Blueprint $table) {
            $table->decimal('flow_rate_kg_min', 4, 2)->nullable()->after('yield_liters');
            $table->integer('milking_duration_seconds')->nullable()->after('flow_rate_kg_min');
            $table->decimal('electrical_conductivity_ms_cm', 4, 2)->nullable()->after('temperature_c');
            $table->decimal('lactose_percentage', 4, 2)->nullable()->after('protein_percentage');
            $table->foreignId('causative_treatment_id')->nullable()->after('quality_status')->constrained('treatments')->nullOnDelete();
            $table->foreignId('withholding_override_by')->nullable()->after('causative_treatment_id')->constrained('users')->nullOnDelete();
            $table->text('withholding_override_reason')->nullable()->after('withholding_override_by');
            $table->string('discard_reason', 255)->nullable()->after('withholding_override_reason');
        });

        Schema::create('milk_collection_centers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('center_code', 50)->unique();
            $table->string('name', 150);
            $table->string('location', 255)->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->decimal('chilling_capacity_liters', 8, 2);
            $table->decimal('current_volume_liters', 8, 2)->default(0);
            $table->string('route_code', 50)->nullable();
            $table->string('status', 50)->default('active'); // active, maintenance, closed
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('farmer_suppliers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('collection_center_id')->nullable()->constrained('milk_collection_centers')->nullOnDelete();
            $table->string('supplier_code', 50)->unique();
            $table->string('name', 150);
            $table->string('phone', 50);
            $table->string('cnic_or_national_id', 50)->nullable();
            $table->string('village_address', 255)->nullable();
            $table->integer('cattle_count')->default(0);
            $table->integer('buffalo_count')->default(0);
            $table->integer('goat_count')->default(0);
            $table->string('payout_channel', 50)->default('cash'); // cash, bank_transfer, easypaisa, jazzcash, nayapay
            $table->string('payout_account_number', 100)->nullable();
            $table->string('payout_account_title', 100)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('milk_rate_charts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('name', 150);
            $table->string('species_type', 50)->default('cow'); // cow, buffalo, goat, mixed
            $table->decimal('base_price_per_liter', 8, 2);
            $table->decimal('standard_fat_percentage', 4, 2)->default(3.50);
            $table->decimal('standard_snf_percentage', 4, 2)->default(8.50);
            $table->decimal('fat_rate_per_unit', 8, 2);
            $table->decimal('snf_rate_per_unit', 8, 2);
            $table->decimal('min_fat_acceptance', 4, 2)->default(3.00);
            $table->decimal('min_snf_acceptance', 4, 2)->default(7.50);
            $table->decimal('premium_incentive_percent', 4, 2)->default(0.00);
            $table->date('effective_from');
            $table->date('effective_until')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('milk_collection_intakes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('collection_center_id')->constrained('milk_collection_centers')->cascadeOnDelete();
            $table->foreignId('farmer_supplier_id')->constrained('farmer_suppliers')->cascadeOnDelete();
            $table->foreignId('rate_chart_id')->nullable()->constrained('milk_rate_charts')->nullOnDelete();
            $table->string('intake_number', 50)->unique();
            $table->date('collection_date');
            $table->string('shift', 50)->default('morning'); // morning, evening
            $table->string('species_type', 50)->default('cow');
            $table->decimal('gross_volume_liters', 8, 2);
            $table->decimal('lactometer_reading', 5, 2)->nullable();
            $table->decimal('fat_percentage', 4, 2);
            $table->decimal('snf_percentage', 4, 2);
            $table->decimal('calculated_price_per_liter', 8, 2);
            $table->decimal('gross_amount', 10, 2);
            $table->decimal('deductions_amount', 8, 2)->default(0);
            $table->decimal('net_payable_amount', 10, 2);
            $table->string('payment_status', 50)->default('pending'); // pending, approved, paid
            $table->timestamp('paid_at')->nullable();
            $table->string('paid_via_reference', 100)->nullable();
            $table->string('alcohol_test_result', 50)->default('negative');
            $table->boolean('adulteration_starch')->default(false);
            $table->boolean('adulteration_urea')->default(false);
            $table->boolean('adulteration_detergent')->default(false);
            $table->boolean('adulteration_formalin')->default(false);
            $table->boolean('adulteration_hydrogen_peroxide')->default(false);
            $table->decimal('added_water_percentage', 4, 2)->default(0.00);
            $table->boolean('quality_accepted')->default(true);
            $table->string('rejection_reason', 255)->nullable();
            $table->foreignId('operator_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['collection_center_id', 'collection_date']);
            $table->index(['farmer_supplier_id', 'collection_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('milk_collection_intakes');
        Schema::dropIfExists('milk_rate_charts');
        Schema::dropIfExists('farmer_suppliers');
        Schema::dropIfExists('milk_collection_centers');

        Schema::table('milk_records', function (Blueprint $table) {
            $table->dropForeign(['causative_treatment_id']);
            $table->dropForeign(['withholding_override_by']);
            $table->dropColumn([
                'flow_rate_kg_min',
                'milking_duration_seconds',
                'electrical_conductivity_ms_cm',
                'lactose_percentage',
                'causative_treatment_id',
                'withholding_override_by',
                'withholding_override_reason',
                'discard_reason',
            ]);
        });

        Schema::table('milk_sessions', function (Blueprint $table) {
            $table->dropForeign(['bulk_tank_id']);
            $table->dropColumn([
                'bulk_tank_id',
                'parlor_identifier',
                'ambient_temp_c',
                'chiller_temp_c',
                'started_at',
                'ended_at',
            ]);
        });

        Schema::dropIfExists('milk_dispatches');
        Schema::dropIfExists('bulk_tanks');
    }
};
