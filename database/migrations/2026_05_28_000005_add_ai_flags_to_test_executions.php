<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('test_executions', function (Blueprint $table) {
            // null = not yet analysed | true = flagged | false = cleared
            $table->boolean('flagged_by_ai')->nullable()->after('reviewed_at');
            $table->text('ai_flag_reason')->nullable()->after('flagged_by_ai');
        });
    }

    public function down(): void
    {
        Schema::table('test_executions', function (Blueprint $table) {
            $table->dropColumn(['flagged_by_ai', 'ai_flag_reason']);
        });
    }
};
