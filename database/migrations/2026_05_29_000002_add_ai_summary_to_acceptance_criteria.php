<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('acceptance_criteria', function (Blueprint $table) {
            $table->text('ai_summary')->nullable()->after('title');
            $table->timestamp('ai_summary_generated_at')->nullable()->after('ai_summary');
        });
    }

    public function down(): void
    {
        Schema::table('acceptance_criteria', function (Blueprint $table) {
            $table->dropColumn(['ai_summary', 'ai_summary_generated_at']);
        });
    }
};
