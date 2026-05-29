<?php

namespace App\Console\Commands;

use App\Models\TestExecution;
use Illuminate\Console\Command;

class HashDocuments extends Command
{
    protected $signature = 'documents:hash';
    protected $description = 'Backfill source_file_hash on executions that were imported before hash tracking was added';

    public function handle(): int
    {
        $executions = TestExecution::whereNotNull('source_file')
            ->whereNull('source_file_hash')
            ->get(['id', 'source_file']);

        if ($executions->isEmpty()) {
            $this->info('Nothing to do — all executions already have a hash.');
            return self::SUCCESS;
        }

        $this->info("Hashing {$executions->count()} execution(s)…");

        $updated = 0;
        $missing = 0;

        $this->withProgressBar($executions, function (TestExecution $ex) use (&$updated, &$missing) {
            $hash = @md5_file($ex->source_file);

            if ($hash === false) {
                $missing++;
                return;
            }

            $ex->updateQuietly(['source_file_hash' => $hash]);
            $updated++;
        });

        $this->newLine(2);
        $this->info("Done. {$updated} hash(es) written.");

        if ($missing > 0) {
            $this->warn("{$missing} file(s) not found on disk — those executions were left without a hash.");
            $this->warn('Re-dispatching those documents will re-import them (once), which will set the hash.');
        }

        return self::SUCCESS;
    }
}
