<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('test_executions', function (Blueprint $table) {
            $table->timestamp('ai_flag_dismissed_at')->nullable()->after('ai_flag_reason');
        });
    }

    public function down(): void
    {
        Schema::table('test_executions', function (Blueprint $table) {
            $table->dropColumn('ai_flag_dismissed_at');
        });
    }
};
