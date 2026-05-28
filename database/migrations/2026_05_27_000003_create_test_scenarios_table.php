<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('test_scenarios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('acceptance_criteria_id')->constrained('acceptance_criteria')->cascadeOnDelete();
            $table->string('code')->unique();
            $table->string('title');
            $table->string('user_role')->nullable();
            $table->text('preconditions')->nullable();
            $table->text('test_steps')->nullable();
            $table->text('expected_result')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('test_scenarios');
    }
};
