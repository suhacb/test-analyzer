<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('test_executions', function (Blueprint $table) {
            $table->text('source_file')->change();
            $table->text('tester_name')->nullable()->change();
            $table->text('browser')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('test_executions', function (Blueprint $table) {
            $table->string('source_file')->change();
            $table->string('tester_name')->nullable()->change();
            $table->string('browser')->nullable()->change();
        });
    }
};
