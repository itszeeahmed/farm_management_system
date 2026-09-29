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
        Schema::create('medicines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('name', 150);
            $table->string('active_ingredient', 150)->nullable();
            $table->enum('category', [
                'antibiotic',
                'anti_inflammatory',
                'vaccine',
                'anthelmintic_dewormer',
                'hormone',
                'vitamin_mineral',
                'antiseptic',
                'other',
            ])->default('antibiotic');
            $table->boolean('is_antimicrobial')->default(false);
            $table->string('default_dosage', 100)->nullable();
            $table->string('dosage_unit', 50)->default('ml');
            $table->string('route_of_administration', 50)->nullable(); // IM, SC, IV, oral, intramammary

            // Withdrawal periods (HLT-013, HLT-018, HLT-020)
            $table->unsignedInteger('milk_withdrawal_days')->default(0);
            $table->unsignedInteger('meat_withdrawal_days')->default(0);

            $table->decimal('unit_cost', 10, 2)->default(0);
            $table->decimal('current_stock', 8, 2)->default(0);
            $table->string('stock_unit', 20)->default('vial');
            $table->timestamps();
        });

        Schema::create('health_cases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('farm_id')->constrained()->cascadeOnDelete();
            $table->foreignId('animal_id')->constrained()->cascadeOnDelete();
            $table->string('case_number', 50)->nullable();
            $table->date('symptom_observed_at');
            $table->string('diagnosis', 150);
            $table->text('symptoms_description')->nullable();
            $table->enum('severity', ['mild', 'moderate', 'severe', 'critical'])->default('mild');
            $table->enum('status', ['open', 'under_treatment', 'resolved', 'chronic', 'fatal'])->default('open');
            $table->string('attending_vet_name', 100)->nullable();
            $table->date('resolved_at')->nullable();
            $table->text('outcome_notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['farm_id', 'status']);
        });

        Schema::create('treatments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('farm_id')->constrained()->cascadeOnDelete();
            $table->foreignId('animal_id')->constrained()->cascadeOnDelete();
            $table->foreignId('health_case_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('medicine_id')->constrained()->cascadeOnDelete();

            $table->dateTime('administered_at');
            $table->decimal('dosage', 6, 2);
            $table->string('dosage_unit', 50)->default('ml');
            $table->string('route', 50)->nullable();

            // Computed active withdrawal period timestamps
            $table->dateTime('milk_withdrawal_until')->nullable();
            $table->dateTime('meat_withdrawal_until')->nullable();

            $table->string('administered_by', 100)->nullable();
            $table->decimal('cost', 10, 2)->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['animal_id', 'milk_withdrawal_until']);
        });

        Schema::create('vaccinations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('farm_id')->constrained()->cascadeOnDelete();
            $table->foreignId('animal_id')->constrained()->cascadeOnDelete();
            $table->string('vaccine_name', 150);
            $table->string('batch_lot_number', 50)->nullable();
            $table->date('administered_date');
            $table->date('booster_due_date')->nullable();
            $table->string('disease_targeted', 100); // FMD, Brucellosis, Anthrax, PPR, Enterotoxemia
            $table->string('administered_by', 100)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['animal_id', 'booster_due_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vaccinations');
        Schema::dropIfExists('treatments');
        Schema::dropIfExists('health_cases');
        Schema::dropIfExists('medicines');
    }
};
