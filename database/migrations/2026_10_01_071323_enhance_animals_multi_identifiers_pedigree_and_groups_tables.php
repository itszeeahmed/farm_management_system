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
        Schema::table('animals', function (Blueprint $table) {
            $table->foreignId('structure_id')->nullable()->after('pen_id')->constrained('farm_structures')->nullOnDelete();
            $table->foreignId('zone_id')->nullable()->after('structure_id')->constrained('farm_zones')->nullOnDelete();
            $table->string('lifecycle_stage', 50)->default('calf')->after('status');
            $table->decimal('genetic_merit_index', 6, 2)->nullable()->after('parity');
            $table->boolean('is_quarantined')->default(false)->after('genetic_merit_index');
        });

        Schema::create('animal_identifiers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('animal_id')->constrained()->cascadeOnDelete();
            $table->string('id_type', 50); // visual_ear_tag, rfid_iso11784, qr_code, national_livestock_id, tattoo, freeze_brand, rumen_bolus
            $table->string('id_value', 100);
            $table->string('tag_color', 50)->nullable();
            $table->string('tag_placement', 50)->nullable(); // left_ear, right_ear, rumen, neck_collar, tail_switch
            $table->boolean('is_primary')->default(false);
            $table->date('applied_date')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['id_type', 'id_value']);
            $table->index(['animal_id', 'is_primary']);
        });

        Schema::create('animal_pedigrees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('animal_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('sire_id')->nullable()->constrained('animals')->nullOnDelete();
            $table->string('sire_name', 100)->nullable();
            $table->string('sire_code', 50)->nullable();
            $table->string('sire_breed', 100)->nullable();
            $table->foreignId('dam_id')->nullable()->constrained('animals')->nullOnDelete();
            $table->string('dam_name', 100)->nullable();
            $table->string('dam_code', 50)->nullable();
            $table->string('maternal_grandsire_code', 50)->nullable();
            $table->string('paternal_grandsire_code', 50)->nullable();
            $table->integer('generation_depth')->default(1);
            $table->decimal('inbreeding_coefficient', 6, 4)->default(0.0000);
            $table->json('pedigree_tree_json')->nullable();
            $table->timestamps();
        });

        Schema::create('animal_weights', function (Blueprint $table) {
            $table->id();
            $table->foreignId('animal_id')->constrained()->cascadeOnDelete();
            $table->decimal('weight_kg', 6, 2);
            $table->string('weighing_method', 50)->default('scale'); // scale, heart_girth_tape, visual_estimate, 3d_camera_sensor
            $table->decimal('heart_girth_cm', 5, 2)->nullable();
            $table->decimal('body_length_cm', 5, 2)->nullable();
            $table->decimal('previous_weight_kg', 6, 2)->nullable();
            $table->integer('days_since_previous')->nullable();
            $table->decimal('average_daily_gain_kg', 5, 3)->nullable();
            $table->date('recorded_at');
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['animal_id', 'recorded_at']);
        });

        Schema::create('animal_bcs_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('animal_id')->constrained()->cascadeOnDelete();
            $table->decimal('bcs_score', 3, 2); // 1.00 to 5.00
            $table->smallInteger('locomotion_score')->nullable(); // 1 to 5
            $table->smallInteger('rumen_fill_score')->nullable(); // 1 to 5
            $table->smallInteger('cleanliness_score')->nullable(); // 1 to 5
            $table->date('assessed_at');
            $table->foreignId('assessed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['animal_id', 'assessed_at']);
        });

        Schema::create('animal_groups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('farm_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('code', 50);
            $table->string('group_type', 50)->default('production'); // production, health, breeding, nutrition, age_cohort
            $table->json('criteria_rules')->nullable();
            $table->boolean('is_dynamic')->default(false);
            $table->text('description')->nullable();
            $table->timestamps();

            $table->unique(['farm_id', 'code']);
        });

        Schema::create('animal_group_memberships', function (Blueprint $table) {
            $table->id();
            $table->foreignId('group_id')->constrained('animal_groups')->cascadeOnDelete();
            $table->foreignId('animal_id')->constrained()->cascadeOnDelete();
            $table->date('joined_at');
            $table->date('left_at')->nullable();
            $table->boolean('is_current')->default(true);
            $table->string('reason')->nullable();
            $table->timestamps();

            $table->index(['group_id', 'is_current']);
            $table->index(['animal_id', 'is_current']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('animal_group_memberships');
        Schema::dropIfExists('animal_groups');
        Schema::dropIfExists('animal_bcs_records');
        Schema::dropIfExists('animal_weights');
        Schema::dropIfExists('animal_pedigrees');
        Schema::dropIfExists('animal_identifiers');

        Schema::table('animals', function (Blueprint $table) {
            $table->dropForeign(['structure_id']);
            $table->dropForeign(['zone_id']);
            $table->dropColumn([
                'structure_id',
                'zone_id',
                'lifecycle_stage',
                'genetic_merit_index',
                'is_quarantined',
            ]);
        });
    }
};
