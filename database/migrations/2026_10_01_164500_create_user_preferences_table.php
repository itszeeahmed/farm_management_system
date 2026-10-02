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
        Schema::create('user_preferences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('locale', 10)->default('en');
            $table->string('timezone', 50)->default('Asia/Karachi');
            $table->string('date_format', 30)->default('Y-m-d');
            $table->string('time_format', 10)->default('24h');
            $table->string('currency', 10)->default('PKR');
            $table->string('currency_symbol_position', 10)->default('before'); // before, after
            $table->string('decimal_separator', 5)->default('.');
            $table->string('thousands_separator', 5)->default(',');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_preferences');
    }
};
