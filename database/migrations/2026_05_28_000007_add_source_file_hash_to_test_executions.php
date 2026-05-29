<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('test_executions', function (Blueprint $table) {
            $table->char('source_file_hash', 32)->nullable()->after('source_file');
            $table->index('source_file_hash');
        });
    }

    public function down(): void
    {
        Schema::table('test_executions', function (Blueprint $table) {
            $table->dropIndex(['source_file_hash']);
            $table->dropColumn('source_file_hash');
        });
    }
};
