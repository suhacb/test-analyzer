<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('test_executions', function (Blueprint $table) {
            // MySQL uses the unique index as the backing index for the FK —
            // drop the FK first, then the unique index, then re-add both.
            $table->dropForeign(['test_scenario_id']);
            $table->dropUnique(['test_scenario_id', 'side']);
            $table->index('test_scenario_id');
            $table->foreign('test_scenario_id')->references('id')->on('test_scenarios')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('test_executions', function (Blueprint $table) {
            $table->dropForeign(['test_scenario_id']);
            $table->dropIndex(['test_scenario_id']);
            $table->unique(['test_scenario_id', 'side']);
            $table->foreign('test_scenario_id')->references('id')->on('test_scenarios')->cascadeOnDelete();
        });
    }
};
