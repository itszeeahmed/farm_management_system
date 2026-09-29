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
        Schema::create('species', function (Blueprint $table) {
            $table->id();
            $table->string('code', 30)->unique();
            $table->string('name', 100);
            $table->string('scientific_name', 150)->nullable();
            $table->integer('default_gestation_days')->default(280);
            $table->integer('default_lactation_days')->default(305);
            $table->decimal('typical_birth_weight_kg', 6, 2)->nullable();
            $table->decimal('typical_adult_weight_kg', 6, 2)->nullable();
            $table->boolean('is_milk_producing')->default(true);
            $table->boolean('is_meat_producing')->default(true);
            $table->timestamps();
        });

        Schema::create('breeds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('species_id')->constrained()->cascadeOnDelete();
            $table->string('code', 50);
            $table->string('name', 100);
            $table->string('origin_country', 100)->nullable();
            $table->decimal('standard_mature_weight_kg', 6, 2)->nullable();
            $table->decimal('target_daily_yield_liters', 5, 2)->nullable();
            $table->timestamps();

            $table->unique(['species_id', 'code']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('breeds');
        Schema::dropIfExists('species');
    }
};
