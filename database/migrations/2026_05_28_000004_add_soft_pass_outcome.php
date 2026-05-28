<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Widen enum first, then backfill existing records
        DB::statement("ALTER TABLE test_executions MODIFY COLUMN outcome ENUM('pass','soft_pass','fail','pending') NOT NULL DEFAULT 'pending'");

        DB::statement("
            UPDATE test_executions
            SET outcome = 'soft_pass'
            WHERE outcome = 'pending'
            AND UPPER(outcome_raw) LIKE '%DELNO OK%'
        ");
    }

    public function down(): void
    {
        DB::statement("
            UPDATE test_executions SET outcome = 'pending' WHERE outcome = 'soft_pass'
        ");

        DB::statement("ALTER TABLE test_executions MODIFY COLUMN outcome ENUM('pass','fail','pending') NOT NULL DEFAULT 'pending'");
    }
};
