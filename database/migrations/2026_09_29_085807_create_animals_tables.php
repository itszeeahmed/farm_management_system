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
        Schema::create('animals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('farm_id')->constrained()->cascadeOnDelete();
            $table->foreignId('species_id')->constrained();
            $table->foreignId('breed_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('pen_id')->nullable()->constrained()->nullOnDelete();

            $table->string('tag_number', 50);
            $table->string('electronic_id', 50)->nullable()->index(); // RFID / EID
            $table->string('qr_code_identifier', 100)->nullable()->unique();
            $table->string('name', 100)->nullable();
            $table->enum('sex', ['female', 'male'])->default('female');
            $table->date('birth_date')->nullable();

            $table->decimal('birth_weight_kg', 6, 2)->nullable();
            $table->decimal('current_weight_kg', 6, 2)->nullable();
            $table->date('weaning_date')->nullable();

            $table->enum('acquisition_type', ['born_on_farm', 'purchased', 'gifted', 'leased'])->default('born_on_farm');
            $table->date('acquisition_date')->nullable();

            $table->enum('status', [
                'active',
                'lactating',
                'dry',
                'pregnant',
                'open',
                'sick',
                'quarantine',
                'sold',
                'culled',
                'deceased',
            ])->default('active');

            $table->unsignedInteger('parity')->default(0); // lactation number / calving count
            $table->foreignId('sire_id')->nullable()->constrained('animals')->nullOnDelete();
            $table->foreignId('dam_id')->nullable()->constrained('animals')->nullOnDelete();

            $table->string('photo_url')->nullable();
            $table->text('notes')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();

            $table->unique(['farm_id', 'tag_number']);
            $table->index(['farm_id', 'status']);
            $table->index(['farm_id', 'species_id']);
        });

        Schema::create('weight_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('animal_id')->constrained()->cascadeOnDelete();
            $table->decimal('weight_kg', 6, 2);
            $table->date('recorded_at');
            $table->string('recorded_by_name')->nullable();
            $table->decimal('body_condition_score', 3, 1)->nullable(); // BCS 1.0 to 5.0
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['animal_id', 'recorded_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('weight_records');
        Schema::dropIfExists('animals');
    }
};
