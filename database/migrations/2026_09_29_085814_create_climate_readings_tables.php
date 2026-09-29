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
        Schema::create('climate_readings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('farm_id')->constrained()->cascadeOnDelete();
            $table->foreignId('barn_id')->nullable()->constrained()->nullOnDelete();

            $table->dateTime('recorded_at');
            $table->decimal('temperature_c', 4, 1);
            $table->decimal('relative_humidity_percent', 5, 2);
            $table->decimal('thi_index', 5, 2); // Temperature Humidity Index

            $table->enum('heat_stress_level', [
                'comfortable',
                'mild_stress',
                'moderate_stress',
                'severe_stress',
                'emergency_danger',
            ])->default('comfortable');

            $table->string('sensor_device_id', 50)->nullable();
            $table->text('mitigation_action_taken')->nullable(); // e.g. fans enabled, misting on
            $table->timestamps();

            $table->index(['farm_id', 'recorded_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('climate_readings');
    }
};
