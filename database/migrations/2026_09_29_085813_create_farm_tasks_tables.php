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
        Schema::create('farm_tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('farm_id')->constrained()->cascadeOnDelete();
            $table->string('title', 150);
            $table->text('description')->nullable();
            $table->string('category', 50)->default('general');
            $table->string('priority', 20)->default('medium');
            $table->date('due_date');
            $table->time('due_time')->nullable();

            $table->foreignId('animal_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();

            $table->string('status', 30)->default('pending');
            $table->dateTime('completed_at')->nullable();
            $table->text('completion_notes')->nullable();
            $table->timestamps();

            $table->index(['farm_id', 'status', 'due_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('farm_tasks');
    }
};
