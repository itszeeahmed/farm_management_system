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
        Schema::create('suppliers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('farm_id')->constrained()->cascadeOnDelete();
            $table->string('name', 150);
            $table->string('contact_person', 100)->nullable();
            $table->string('phone', 50)->nullable();
            $table->string('email', 100)->nullable();
            $table->string('category', 50)->default('feed'); // feed, medicine, semen, equipment, general
            $table->string('address')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('financial_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('farm_id')->constrained()->cascadeOnDelete();
            $table->enum('type', ['income', 'expense'])->default('income');
            $table->enum('category', [
                'milk_sale',
                'animal_sale',
                'manure_sale',
                'other_income',
                'feed_purchase',
                'veterinary_medicine',
                'breeding_ai',
                'labor_wages',
                'fuel_utilities',
                'equipment_maintenance',
                'supplies',
                'other_expense',
            ]);
            $table->decimal('amount', 12, 2); // Decimal for money (DAT-002)
            $table->date('transaction_date');
            $table->string('reference_number', 100)->nullable();
            $table->foreignId('supplier_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('animal_id')->nullable()->constrained()->nullOnDelete();
            $table->string('payment_method', 50)->default('cash'); // cash, bank_transfer, jazzcash, easypaisa, mobile_money
            $table->text('description')->nullable();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['farm_id', 'transaction_date']);
            $table->index(['farm_id', 'type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('financial_transactions');
        Schema::dropIfExists('suppliers');
    }
};
