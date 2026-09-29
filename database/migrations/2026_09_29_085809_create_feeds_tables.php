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
        Schema::create('feed_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('farm_id')->constrained()->cascadeOnDelete();
            $table->string('name', 100);
            $table->string('code', 50)->nullable();
            $table->enum('category', [
                'forage_green',
                'silage',
                'hay_dry',
                'concentrate',
                'grains',
                'mineral_premix',
                'byproduct',
                'milk_replacer',
            ])->default('concentrate');
            $table->string('unit', 20)->default('kg');
            $table->decimal('current_stock', 10, 2)->default(0);
            $table->decimal('minimum_stock_alert', 10, 2)->default(50);
            $table->decimal('cost_per_unit', 10, 2)->default(0); // Decimal for money (DAT-002)
            $table->decimal('dry_matter_percentage', 5, 2)->nullable();
            $table->decimal('crude_protein_percentage', 5, 2)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['farm_id', 'category']);
        });

        Schema::create('feed_consumptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('farm_id')->constrained()->cascadeOnDelete();
            $table->foreignId('feed_item_id')->constrained()->cascadeOnDelete();
            $table->foreignId('pen_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('animal_id')->nullable()->constrained()->nullOnDelete();

            $table->date('consumption_date');
            $table->decimal('quantity_consumed', 8, 2);
            $table->decimal('unit_cost', 10, 2);
            $table->decimal('total_cost', 10, 2);
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['farm_id', 'consumption_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('feed_consumptions');
        Schema::dropIfExists('feed_items');
    }
};
