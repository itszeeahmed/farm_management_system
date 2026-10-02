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
        // 1. Enhance feed_items with full proximate and energy nutritional profile
        Schema::table('feed_items', function (Blueprint $table) {
            $table->decimal('ndf_percentage', 5, 2)->nullable()->after('crude_protein_percentage'); // Neutral Detergent Fiber
            $table->decimal('adf_percentage', 5, 2)->nullable()->after('ndf_percentage'); // Acid Detergent Fiber
            $table->decimal('nel_mcal_per_kg', 5, 2)->nullable()->after('adf_percentage'); // Net Energy for Lactation
            $table->decimal('tdn_percentage', 5, 2)->nullable()->after('nel_mcal_per_kg'); // Total Digestible Nutrients
            $table->decimal('calcium_percentage', 5, 2)->nullable()->after('tdn_percentage');
            $table->decimal('phosphorus_percentage', 5, 2)->nullable()->after('calcium_percentage');
            $table->decimal('ash_percentage', 5, 2)->nullable()->after('phosphorus_percentage');
            $table->boolean('is_active')->default(true)->after('ash_percentage');
        });

        // 2. Feed Formulations (Ration Templates & NRC/CNCPS targets)
        Schema::create('feed_formulations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('farm_id')->constrained()->cascadeOnDelete();
            $table->string('name', 150);
            $table->string('code', 50)->nullable();
            $table->enum('species_type', ['cattle', 'buffalo', 'goat', 'sheep'])->default('cattle');
            $table->string('target_stage', 100); // lactating_high, lactating_mid, dry_close_up, heifer_growing, etc.
            $table->decimal('target_dmi_kg', 6, 2);
            $table->decimal('calculated_cp_percent', 5, 2)->default(0);
            $table->decimal('calculated_nel_mcal', 6, 2)->default(0);
            $table->decimal('calculated_cost_per_head_day', 10, 2)->default(0);
            $table->json('ingredients'); // [{"feed_item_id": 1, "inclusion_kg_as_fed": 25.0, "inclusion_kg_dm": 8.75, "percentage": 45.0}]
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['farm_id', 'species_type', 'is_active']);
        });

        // 3. TMR Batches (Mixer wagon batch production & loading accuracy)
        Schema::create('tmr_batches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('farm_id')->constrained()->cascadeOnDelete();
            $table->foreignId('feed_formulation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('pen_id')->nullable()->constrained()->nullOnDelete();
            $table->string('batch_number', 50)->unique();
            $table->string('mixer_wagon_id', 100)->nullable(); // e.g. Kuhn Knight 5144 / Keenan MechFiber
            $table->decimal('planned_weight_kg', 10, 2);
            $table->decimal('actual_weight_kg', 10, 2);
            $table->decimal('deviation_percent', 5, 2)->default(0);
            $table->unsignedInteger('mixing_duration_minutes')->default(15);
            $table->enum('status', ['mixing', 'completed', 'dispatched_to_bunk'])->default('completed');
            $table->foreignId('operator_id')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('batch_timestamp');
            $table->timestamps();

            $table->index(['farm_id', 'batch_timestamp']);
        });

        // 4. Feed Bunk Scores (0-4 slick-to-excess bunk scoring system)
        Schema::create('feed_bunk_scores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('farm_id')->constrained()->cascadeOnDelete();
            $table->foreignId('pen_id')->constrained()->cascadeOnDelete();
            $table->dateTime('assessed_at');
            $table->unsignedTinyInteger('score'); // 0: slick/bare, 1: scattered crumbs, 2: 25-50% left, 3: heavy refusal, 4: untouched
            $table->decimal('refusal_estimated_kg', 8, 2)->default(0);
            $table->enum('adjustment_action', [
                'increase_5_percent',
                'increase_10_percent',
                'maintain',
                'decrease_5_percent',
                'decrease_10_percent',
                'clean_bunk',
            ])->default('maintain');
            $table->foreignId('assessed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['pen_id', 'assessed_at']);
        });

        // 5. Silage Bunkers & Clamps (Compaction, fermentation & face management)
        Schema::create('silage_bunkers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('farm_id')->constrained()->cascadeOnDelete();
            $table->string('bunker_code', 50);
            $table->string('name', 150);
            $table->enum('crop_type', ['corn_maize', 'alfalfa', 'sorghum', 'oats_grass', 'mixed'])->default('corn_maize');
            $table->decimal('initial_tonnage', 10, 2);
            $table->decimal('remaining_tonnage', 10, 2);
            $table->decimal('face_temperature_c', 4, 1)->default(22.0);
            $table->decimal('ph_level', 3, 1)->default(4.0); // Normal: 3.8 - 4.2
            $table->decimal('compaction_density_kg_m3', 6, 1)->default(650.0);
            $table->enum('fermentation_score', ['excellent', 'good', 'fair', 'poor'])->default('excellent');
            $table->enum('status', ['fermenting', 'open_feeding', 'exhausted'])->default('open_feeding');
            $table->timestamps();
        });

        // 6. Enhance health_cases with clinical SOAP protocol
        Schema::table('health_cases', function (Blueprint $table) {
            $table->text('subjective_notes')->nullable()->after('symptoms_description');
            $table->decimal('objective_temp_c', 4, 1)->nullable()->after('subjective_notes');
            $table->unsignedInteger('objective_heart_rate')->nullable()->after('objective_temp_c');
            $table->unsignedInteger('objective_respiration_rate')->nullable()->after('objective_heart_rate');
            $table->unsignedInteger('objective_rumen_motility_per_2min')->nullable()->after('objective_respiration_rate');
            $table->text('assessment_notes')->nullable()->after('objective_rumen_motility_per_2min');
            $table->text('plan_notes')->nullable()->after('assessment_notes');
        });

        // 7. Enhance medicines with WHO Antimicrobial Classification and standard DDDA
        Schema::table('medicines', function (Blueprint $table) {
            if (! Schema::hasColumn('medicines', 'who_classification')) {
                $table->enum('who_classification', [
                    'critically_important',
                    'highly_important',
                    'important',
                    'not_applicable',
                ])->default('not_applicable')->after('is_antimicrobial');
            }
            if (! Schema::hasColumn('medicines', 'standard_ddda_mg_per_kg')) {
                $table->decimal('standard_ddda_mg_per_kg', 8, 4)->nullable()->after('who_classification');
            }
        });

        // 8. Enhance treatments with AMU metrics and veterinary prescription verification
        Schema::table('treatments', function (Blueprint $table) {
            $table->string('veterinarian_license_number', 50)->nullable()->after('administered_by');
            $table->string('prescription_number', 50)->nullable()->after('veterinarian_license_number');
            $table->string('batch_lot_number', 50)->nullable()->after('prescription_number');
            $table->decimal('active_substance_administered_mg', 10, 2)->default(0)->after('batch_lot_number');
            $table->decimal('ddda_units_consumed', 8, 3)->default(0)->after('active_substance_administered_mg');
        });

        // 9. Biosecurity Audits & Farm-Entry Hygiene Logs
        Schema::create('biosecurity_audits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('farm_id')->constrained()->cascadeOnDelete();
            $table->date('audit_date');
            $table->string('auditor_name', 100);
            $table->unsignedTinyInteger('visitor_log_compliance_score'); // 0-100%
            $table->unsignedTinyInteger('footbath_disinfection_score'); // 0-100%
            $table->unsignedTinyInteger('quarantine_compliance_score'); // 0-100%
            $table->unsignedTinyInteger('carcass_disposal_compliance_score'); // 0-100%
            $table->enum('overall_risk_rating', ['low_risk', 'medium_risk', 'critical_risk'])->default('low_risk');
            $table->text('corrective_actions')->nullable();
            $table->timestamps();
        });

        // 10. Liquid Nitrogen Semen Straw Inventory
        Schema::create('semen_straw_inventories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('farm_id')->constrained()->cascadeOnDelete();
            $table->string('straw_code', 50)->unique();
            $table->string('sire_name', 150);
            $table->string('sire_breed', 100);
            $table->string('naab_code', 50)->nullable(); // National Association of Animal Breeders code
            $table->enum('semen_type', ['conventional', 'sexed_female', 'sexed_male'])->default('conventional');
            $table->string('canister_location', 50)->default('Tank 1 - Canister 3');
            $table->string('cane_number', 50)->default('Cane 02');
            $table->unsignedInteger('straws_in_stock')->default(0);
            $table->decimal('unit_cost_pkr', 10, 2)->default(0);
            $table->timestamps();

            $table->index(['farm_id', 'straw_code']);
        });

        // 11. Enhance breeding_events to link to semen inventory
        Schema::table('breeding_events', function (Blueprint $table) {
            $table->foreignId('semen_straw_inventory_id')->nullable()->after('sire_id')->constrained('semen_straw_inventories')->nullOnDelete();
            $table->unsignedTinyInteger('cycle_number')->default(1)->after('status');
            $table->unsignedTinyInteger('heat_intensity_score')->default(3)->after('cycle_number'); // 1-5 scale
        });

        // 12. Enhance births with offspring linkage and birth weight
        Schema::table('births', function (Blueprint $table) {
            $table->foreignId('created_offspring_id')->nullable()->after('sire_id')->constrained('animals')->nullOnDelete();
            $table->decimal('birth_weight_kg', 5, 2)->nullable()->after('calving_ease');
            $table->enum('offspring_sex', ['male', 'female', 'mixed'])->default('female')->after('birth_weight_kg');
        });

        // 13. Postpartum Health Monitoring (Ketosis & Metritis scoring)
        Schema::create('postpartum_checks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('farm_id')->constrained()->cascadeOnDelete();
            $table->foreignId('animal_id')->constrained()->cascadeOnDelete();
            $table->foreignId('birth_id')->nullable()->constrained()->nullOnDelete();
            $table->date('check_date');
            $table->unsignedInteger('days_in_milk')->default(10);
            $table->decimal('rectal_temperature_c', 4, 1)->default(38.6);
            $table->unsignedTinyInteger('lochia_score')->default(0); // 0: clear/normal, 1: reddish, 2: purulent, 3: fetid purulent
            $table->decimal('ketosis_test_bhb_mmol_l', 4, 2)->default(0.8); // Normal: < 1.2 mmol/L, Subclinical ketosis: >= 1.2
            $table->enum('uterine_involution_status', ['normal_involution', 'delayed_metritis', 'pyometra'])->default('normal_involution');
            $table->string('checked_by', 100)->nullable();
            $table->text('clinical_notes')->nullable();
            $table->timestamps();

            $table->index(['animal_id', 'check_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('postpartum_checks');

        Schema::table('births', function (Blueprint $table) {
            $table->dropForeign(['created_offspring_id']);
            $table->dropColumn(['created_offspring_id', 'birth_weight_kg', 'offspring_sex']);
        });

        Schema::table('breeding_events', function (Blueprint $table) {
            $table->dropForeign(['semen_straw_inventory_id']);
            $table->dropColumn(['semen_straw_inventory_id', 'cycle_number', 'heat_intensity_score']);
        });

        Schema::dropIfExists('semen_straw_inventories');
        Schema::dropIfExists('biosecurity_audits');

        Schema::table('treatments', function (Blueprint $table) {
            $table->dropColumn([
                'veterinarian_license_number',
                'prescription_number',
                'batch_lot_number',
                'active_substance_administered_mg',
                'ddda_units_consumed',
            ]);
        });

        Schema::table('medicines', function (Blueprint $table) {
            $table->dropColumn([
                'active_ingredient',
                'is_antimicrobial',
                'who_classification',
                'standard_ddda_mg_per_kg',
            ]);
        });

        Schema::table('health_cases', function (Blueprint $table) {
            $table->dropColumn([
                'subjective_notes',
                'objective_temp_c',
                'objective_heart_rate',
                'objective_respiration_rate',
                'objective_rumen_motility_per_2min',
                'assessment_notes',
                'plan_notes',
            ]);
        });

        Schema::dropIfExists('silage_bunkers');
        Schema::dropIfExists('feed_bunk_scores');
        Schema::dropIfExists('tmr_batches');
        Schema::dropIfExists('feed_formulations');

        Schema::table('feed_items', function (Blueprint $table) {
            $table->dropColumn([
                'ndf_percentage',
                'adf_percentage',
                'nel_mcal_per_kg',
                'tdn_percentage',
                'calcium_percentage',
                'phosphorus_percentage',
                'ash_percentage',
                'is_active',
            ]);
        });
    }
};
