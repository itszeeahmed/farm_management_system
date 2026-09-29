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
        Schema::create('breeding_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('farm_id')->constrained()->cascadeOnDelete();
            $table->foreignId('animal_id')->constrained()->cascadeOnDelete(); // dam
            $table->foreignId('sire_id')->nullable()->constrained('animals')->nullOnDelete();

            $table->enum('method', ['artificial_insemination', 'natural_service', 'embryo_transfer'])->default('artificial_insemination');
            $table->string('semen_straw_code', 100)->nullable();
            $table->string('sire_breed_code', 50)->nullable();
            $table->dateTime('insemination_datetime');
            $table->string('technician_name', 100)->nullable();
            $table->decimal('cost', 10, 2)->default(0);

            $table->enum('status', ['pending_check', 'conceived', 'failed', 're-bred'])->default('pending_check');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['animal_id', 'insemination_datetime']);
        });

        Schema::create('pregnancies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('farm_id')->constrained()->cascadeOnDelete();
            $table->foreignId('animal_id')->constrained()->cascadeOnDelete();
            $table->foreignId('breeding_event_id')->nullable()->constrained()->nullOnDelete();

            $table->date('check_date');
            $table->enum('method', ['palpation', 'ultrasound', 'blood_progesterone'])->default('palpation');
            $table->enum('status', ['confirmed_pregnant', 'not_pregnant', 'doubtful', 'aborted', 'calved'])->default('confirmed_pregnant');

            $table->date('expected_delivery_date')->nullable();
            $table->date('expected_dry_off_date')->nullable();
            $table->string('checked_by', 100)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['animal_id', 'expected_delivery_date']);
        });

        Schema::create('births', function (Blueprint $table) {
            $table->id();
            $table->foreignId('farm_id')->constrained()->cascadeOnDelete();
            $table->foreignId('dam_id')->constrained('animals')->cascadeOnDelete();
            $table->foreignId('sire_id')->nullable()->constrained('animals')->nullOnDelete();
            $table->foreignId('pregnancy_id')->nullable()->constrained()->nullOnDelete();

            $table->dateTime('calving_datetime');
            $table->enum('calving_ease', [
                'easy_unassisted',
                'easy_slight_assistance',
                'difficult_traction',
                'surgical_caesarean',
                'fetotomy',
            ])->default('easy_unassisted');

            $table->unsignedInteger('offspring_count')->default(1);
            $table->unsignedInteger('live_count')->default(1);
            $table->unsignedInteger('stillborn_count')->default(0);

            $table->boolean('colostrum_fed')->default(true);
            $table->decimal('colostrum_liters', 4, 2)->nullable();
            $table->string('attendant_name', 100)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['dam_id', 'calving_datetime']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('births');
        Schema::dropIfExists('pregnancies');
        Schema::dropIfExists('breeding_events');
    }
};
