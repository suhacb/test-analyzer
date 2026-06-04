<?php

namespace App\Console\Commands;

use App\Jobs\AnalyzeAcceptanceCriteria;
use App\Jobs\AnalyzeUserStory;
use App\Models\AcceptanceCriteria;
use App\Models\UserStory;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;

class SummariseAnalysis extends Command
{
    protected $signature = 'analysis:summarise
                            {--target=all : What to summarise: criteria, stories, or all}
                            {--force      : Re-analyse entries that already have a summary/report}
                            {--ids=       : Comma-separated IDs to process}';

    protected $description = 'Generate AI narrative summaries for acceptance criteria and/or user stories';

    public function handle(): int
    {
        $target = $this->option('target');

        if (! in_array($target, ['criteria', 'stories', 'all'], true)) {
            $this->error("Invalid --target value. Use: criteria, stories, or all.");
            return self::FAILURE;
        }

        if ($target === 'criteria' || $target === 'all') {
            $this->summariseCriteria();
        }

        if ($target === 'stories' || $target === 'all') {
            $this->summariseStories();
        }

        return self::SUCCESS;
    }

    private function summariseCriteria(): void
    {
        $query = AcceptanceCriteria::withCount('testScenarios')
            ->having('test_scenarios_count', '>', 0)
            ->when($this->option('ids'), fn (Builder $q) => $q->whereIn('id', $this->parseIds()))
            ->when(! $this->option('force') && ! $this->option('ids'), fn (Builder $q) => $q->whereNull('ai_summary'));

        $criteria = $query->get();

        if ($criteria->isEmpty()) {
            $this->info('Criteria — nothing to analyse. Use --force to re-generate existing summaries.');
            return;
        }

        $this->info("Analysing {$criteria->count()} acceptance criteria…");

        $this->withProgressBar($criteria, fn (AcceptanceCriteria $ac) => AnalyzeAcceptanceCriteria::dispatchSync($ac));

        $this->newLine(2);
    }

    private function summariseStories(): void
    {
        $query = UserStory::withCount('testScenarios')
            ->having('test_scenarios_count', '>', 0)
            ->when($this->option('ids'), fn (Builder $q) => $q->whereIn('id', $this->parseIds()))
            ->when(! $this->option('force') && ! $this->option('ids'), fn (Builder $q) => $q->whereNull('ai_report'));

        $stories = $query->get();

        if ($stories->isEmpty()) {
            $this->info('Stories — nothing to analyse. Use --force to re-generate existing reports.');
            return;
        }

        $this->info("Analysing {$stories->count()} user story/stories…");

        $this->withProgressBar($stories, fn (UserStory $us) => AnalyzeUserStory::dispatchSync($us));

        $this->newLine(2);
    }

    private function parseIds(): array
    {
        return array_map('intval', explode(',', $this->option('ids')));
    }
}
