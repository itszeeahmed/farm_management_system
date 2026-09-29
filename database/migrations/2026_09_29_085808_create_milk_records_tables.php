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
        Schema::create('milk_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('farm_id')->constrained()->cascadeOnDelete();
            $table->date('session_date');
            $table->enum('shift', ['morning', 'afternoon', 'evening'])->default('morning');
            $table->decimal('total_yield_liters', 8, 2)->default(0);
            $table->decimal('bulk_tank_temperature_c', 4, 1)->nullable();
            $table->foreignId('milker_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['farm_id', 'session_date', 'shift']);
        });

        Schema::create('milk_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('farm_id')->constrained()->cascadeOnDelete();
            $table->foreignId('animal_id')->constrained()->cascadeOnDelete();
            $table->foreignId('milk_session_id')->nullable()->constrained()->nullOnDelete();

            $table->date('recorded_date');
            $table->enum('shift', ['morning', 'afternoon', 'evening'])->default('morning');
            $table->decimal('yield_liters', 5, 2);

            // Dairy quality metrics
            $table->decimal('fat_percentage', 4, 2)->nullable();
            $table->decimal('snf_percentage', 4, 2)->nullable(); // Solids-not-fat
            $table->decimal('protein_percentage', 4, 2)->nullable();
            $table->unsignedInteger('scc')->nullable(); // Somatic Cell Count (cells/ml / 1000)
            $table->decimal('temperature_c', 4, 1)->nullable();

            $table->enum('quality_status', [
                'premium',
                'standard',
                'substandard',
                'discarded_withdrawal',
                'discarded_mastitis',
            ])->default('standard');

            $table->boolean('is_colostrum')->default(false);
            $table->text('operator_notes')->nullable();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['animal_id', 'recorded_date']);
            $table->index(['farm_id', 'recorded_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('milk_records');
        Schema::dropIfExists('milk_sessions');
    }
};
