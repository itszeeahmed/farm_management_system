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
        Schema::table('organizations', function (Blueprint $table) {
            $table->string('tier', 50)->default('commercial')->after('slug');
            $table->string('status', 50)->default('active')->after('tier');
            $table->json('operating_profile')->nullable()->after('settings');
        });

        Schema::table('farms', function (Blueprint $table) {
            $table->json('boundary_geojson')->nullable()->after('climate_settings');
            $table->integer('elevation_meters')->nullable()->after('boundary_geojson');
            $table->string('soil_type', 100)->nullable()->after('elevation_meters');
            $table->json('water_sources')->nullable()->after('soil_type');
            $table->boolean('backup_generator')->default(false)->after('water_sources');
        });

        Schema::create('farm_zones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('farm_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('code', 50);
            $table->string('type', 50)->default('pasture'); // pasture, crop_field, facility_compound, quarantine_zone, effluent_lagoon, milking_center, feed_pad, calving_yard
            $table->decimal('area_size', 8, 2)->nullable();
            $table->string('area_unit', 20)->default('acres');
            $table->string('soil_type', 100)->nullable();
            $table->string('irrigation_type', 50)->nullable();
            $table->string('status', 50)->default('active'); // active, resting, fallow, quarantine, under_maintenance
            $table->json('geojson')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['farm_id', 'code']);
        });

        Schema::create('farm_structures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('farm_id')->constrained()->cascadeOnDelete();
            $table->foreignId('zone_id')->nullable()->constrained('farm_zones')->nullOnDelete();
            $table->foreignId('parent_structure_id')->nullable()->constrained('farm_structures')->nullOnDelete();
            $table->string('name');
            $table->string('code', 50);
            $table->string('structure_type', 50)->default('barn'); // barn, shed, milking_parlor, silo_bunker, feed_alley, pen, stall, isolation_ward, maternity_pen, calf_hutch
            $table->string('target_species', 50)->nullable();
            $table->integer('capacity')->default(0);
            $table->decimal('area_sq_meters', 8, 2)->nullable();
            $table->string('ventilation_type', 50)->nullable();
            $table->boolean('has_automated_feeders')->default(false);
            $table->boolean('has_automated_waterers')->default(false);
            $table->boolean('has_misting_cooling')->default(false);
            $table->integer('current_headcount')->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['farm_id', 'code']);
        });

        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('slug', 100);
            $table->text('description')->nullable();
            $table->boolean('is_system')->default(false);
            $table->timestamps();

            $table->index('slug');
        });

        Schema::create('permissions', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('category', 50);
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('role_permissions', function (Blueprint $table) {
            $table->foreignId('role_id')->constrained()->cascadeOnDelete();
            $table->foreignId('permission_id')->constrained()->cascadeOnDelete();

            $table->primary(['role_id', 'permission_id']);
        });

        Schema::create('user_farm_access', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('farm_id')->constrained()->cascadeOnDelete();
            $table->foreignId('role_id')->constrained()->cascadeOnDelete();
            $table->string('access_level', 50)->default('full'); // full, read_only, operational, veterinary_only
            $table->foreignId('granted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('granted_at')->useCurrent();
            $table->timestamp('expires_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->string('reason')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'farm_id', 'role_id']);
            $table->index(['farm_id', 'is_active']);
        });

        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('farm_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action', 100);
            $table->string('auditable_type', 255);
            $table->unsignedBigInteger('auditable_id');
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index('created_at');
            $table->index(['auditable_type', 'auditable_id']);
            $table->index(['farm_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('user_farm_access');
        Schema::dropIfExists('role_permissions');
        Schema::dropIfExists('permissions');
        Schema::dropIfExists('roles');
        Schema::dropIfExists('farm_structures');
        Schema::dropIfExists('farm_zones');

        Schema::table('farms', function (Blueprint $table) {
            $table->dropColumn([
                'boundary_geojson',
                'elevation_meters',
                'soil_type',
                'water_sources',
                'backup_generator',
            ]);
        });

        Schema::table('organizations', function (Blueprint $table) {
            $table->dropColumn([
                'tier',
                'status',
                'operating_profile',
            ]);
        });
    }
};
