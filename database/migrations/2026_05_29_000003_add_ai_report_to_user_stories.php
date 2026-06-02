<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_stories', function (Blueprint $table) {
            $table->text('ai_report')->nullable()->after('title');
            $table->timestamp('ai_report_generated_at')->nullable()->after('ai_report');
        });
    }

    public function down(): void
    {
        Schema::table('user_stories', function (Blueprint $table) {
            $table->dropColumn(['ai_report', 'ai_report_generated_at']);
        });
    }
};
