<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('test_executions', function (Blueprint $table) {
            // Comment portion extracted from the outcome cell (mixed content by testers)
            $table->text('outcome_comment')->nullable()->after('outcome_raw');
            // AI-classified failure cause — only set when outcome = fail
            $table->string('failure_cause', 30)->nullable()->after('outcome_comment');
        });
    }

    public function down(): void
    {
        Schema::table('test_executions', function (Blueprint $table) {
            $table->dropColumn(['outcome_comment', 'failure_cause']);
        });
    }
};
