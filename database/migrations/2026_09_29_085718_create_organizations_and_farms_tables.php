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
        Schema::create('organizations', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('contact_email')->nullable();
            $table->string('contact_phone')->nullable();
            $table->string('country', 3)->default('PAK');
            $table->string('currency', 3)->default('PKR');
            $table->string('timezone')->default('Asia/Karachi');
            $table->json('settings')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('farms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('code', 50)->unique();
            $table->string('type')->default('dairy_mixed'); // dairy, beef, goat, mixed
            $table->string('location')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->decimal('total_area', 8, 2)->nullable();
            $table->string('area_unit', 20)->default('acres');
            $table->string('timezone')->default('Asia/Karachi');
            $table->json('climate_settings')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('barns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('farm_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('code', 50)->nullable();
            $table->string('type')->default('milking'); // milking, dry, maternity, young_stock, isolation
            $table->integer('capacity')->default(20);
            $table->timestamps();
        });

        Schema::create('pens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('barn_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('code', 50)->nullable();
            $table->integer('capacity')->default(10);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pens');
        Schema::dropIfExists('barns');
        Schema::dropIfExists('farms');
        Schema::dropIfExists('organizations');
    }
};
