<?php

namespace App\Console\Commands;

use App\Models\TestExecution;
use Illuminate\Console\Command;

class PruneDocuments extends Command
{
    protected $signature = 'documents:prune {--dry-run : List orphaned executions without deleting}';
    protected $description = 'Soft-delete test executions whose source file no longer exists on disk';

    public function handle(): int
    {
        $orphaned = TestExecution::whereNotNull('source_file')
            ->get(['id', 'source_file'])
            ->filter(fn ($e) => !file_exists($e->source_file));

        if ($orphaned->isEmpty()) {
            $this->info('No orphaned executions found.');
            return self::SUCCESS;
        }

        if ($this->option('dry-run')) {
            $this->warn("Found {$orphaned->count()} orphaned execution(s) (dry run — nothing deleted):");
            $orphaned->each(fn ($e) => $this->line("  [{$e->id}] {$e->source_file}"));
            return self::SUCCESS;
        }

        $ids = $orphaned->pluck('id');
        TestExecution::whereIn('id', $ids)->delete();

        $this->info("Soft-deleted {$orphaned->count()} orphaned execution(s).");
        $orphaned->each(fn ($e) => $this->line("  [{$e->id}] " . basename($e->source_file)));

        return self::SUCCESS;
    }
}
