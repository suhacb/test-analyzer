<?php

namespace App\Console\Commands;

use App\Jobs\AnalyzeUserStory;
use App\Models\UserStory;
use Illuminate\Console\Command;

class SummariseStories extends Command
{
    protected $signature = 'analysis:summarise-stories
                            {--force : Re-analyse stories that already have a report}
                            {--ids=  : Comma-separated User Story IDs to process}';

    protected $description = 'Generate AI narrative reports for user stories using the smart model';

    public function handle(): int
    {
        $query = UserStory::withCount('testScenarios')
            ->having('test_scenarios_count', '>', 0);

        if ($this->option('ids')) {
            $ids = array_map('intval', explode(',', $this->option('ids')));
            $query->whereIn('id', $ids);
        } elseif (! $this->option('force')) {
            $query->whereNull('ai_report');
        }

        $stories = $query->get();

        if ($stories->isEmpty()) {
            $this->info('Nothing to analyse. Use --force to re-generate existing reports.');
            return self::SUCCESS;
        }

        $this->info("Analysing {$stories->count()} user story/stories synchronously…");
        $this->newLine();

        $this->withProgressBar($stories, function (UserStory $us) {
            AnalyzeUserStory::dispatchSync($us);
        });

        $this->newLine(2);
        $this->info('Done.');

        return self::SUCCESS;
    }
}
