<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('test_executions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('test_scenario_id')->constrained('test_scenarios')->cascadeOnDelete();
            $table->enum('side', ['provider', 'client']);
            $table->enum('outcome', ['pass', 'fail', 'pending'])->default('pending');
            $table->string('outcome_raw')->nullable();
            $table->text('comments')->nullable();
            $table->string('tester_name')->nullable();
            $table->string('browser')->nullable();
            $table->datetime('tested_at')->nullable();
            $table->string('source_file');
            $table->timestamps();

            $table->unique(['test_scenario_id', 'side']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('test_executions');
    }
};
