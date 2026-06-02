<?php

namespace App\Console\Commands;

use App\Jobs\AnalyzeAcceptanceCriteria;
use App\Models\AcceptanceCriteria;
use Illuminate\Console\Command;

class SummariseCriteria extends Command
{
    protected $signature = 'analysis:summarise-criteria
                            {--force : Re-analyse criteria that already have a summary}
                            {--ids=  : Comma-separated AC IDs to process}';

    protected $description = 'Generate AI narrative summaries for acceptance criteria using the smart model';

    public function handle(): int
    {
        $query = AcceptanceCriteria::withCount('testScenarios')
            ->having('test_scenarios_count', '>', 0);

        if ($this->option('ids')) {
            $ids = array_map('intval', explode(',', $this->option('ids')));
            $query->whereIn('id', $ids);
        } elseif (! $this->option('force')) {
            $query->whereNull('ai_summary');
        }

        $criteria = $query->get();

        if ($criteria->isEmpty()) {
            $this->info('Nothing to analyse. Use --force to re-generate existing summaries.');
            return self::SUCCESS;
        }

        $this->info("Analysing {$criteria->count()} acceptance criteria synchronously…");
        $this->newLine();

        $this->withProgressBar($criteria, function (AcceptanceCriteria $ac) {
            AnalyzeAcceptanceCriteria::dispatchSync($ac);
        });

        $this->newLine(2);
        $this->info('Done.');

        return self::SUCCESS;
    }
}
