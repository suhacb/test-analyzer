<?php

namespace App\Console\Commands;

use App\Jobs\FlagDoubtfulExecution;
use App\Models\TestExecution;
use Illuminate\Console\Command;

class FlagDoubtfulExecutions extends Command
{
    protected $signature = 'analysis:flag-doubtful
                            {--force : Re-analyse executions that were already flagged}';

    protected $description = 'Use the quick AI model to flag non-passing test executions with doubtful or missing descriptions';

    public function handle(): int
    {
        $query = TestExecution::with('testScenario')
            ->whereIn('outcome', ['fail', 'pending']);

        if (! $this->option('force')) {
            $query->whereNull('flagged_by_ai');
        }

        $executions = $query->get();

        if ($executions->isEmpty()) {
            $this->info('Nothing to analyse. Use --force to re-run on already-analysed executions.');
            return self::SUCCESS;
        }

        $this->info("Analysing {$executions->count()} execution(s) synchronously…");

        $flagged = 0;

        $this->withProgressBar($executions, function (TestExecution $execution) use (&$flagged) {
            FlagDoubtfulExecution::dispatchSync($execution);
            $execution->refresh();
            if ($execution->flagged_by_ai) {
                $flagged++;
            }
        });

        $this->newLine(2);
        $this->info("Done. {$flagged} execution(s) flagged as doubtful.");

        return self::SUCCESS;
    }
}
