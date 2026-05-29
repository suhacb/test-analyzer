<?php

namespace App\Console\Commands;

use App\Models\TestExecution;
use App\Services\AiOutcomeParser;
use Illuminate\Console\Command;

class ReprocessExecutions extends Command
{
    protected $signature = 'documents:reprocess-outcomes
                            {--force : Re-run even on executions that already have an outcome_comment}
                            {--ids=  : Comma-separated execution IDs to process}';

    protected $description = 'Re-parse outcome cells and classify failure causes on already-imported executions';

    public function handle(AiOutcomeParser $parser): int
    {
        // ── Pass 1: outcome + comment re-parsing ──────────────────────────────

        $outcomeQuery = TestExecution::whereNotNull('outcome_raw');

        if ($this->option('ids')) {
            $ids = array_map('intval', explode(',', $this->option('ids')));
            $outcomeQuery->whereIn('id', $ids);
        } elseif (! $this->option('force')) {
            $outcomeQuery->whereNull('outcome_comment')
                         ->where('outcome', 'pending');
        }

        $forOutcome = $outcomeQuery->get(['id', 'outcome_raw', 'comments']);

        if ($forOutcome->isNotEmpty()) {
            $this->info("Pass 1 — re-parsing outcome cells for {$forOutcome->count()} execution(s)…");

            $reclassified = 0;

            $this->withProgressBar($forOutcome, function (TestExecution $ex) use ($parser, &$reclassified) {
                $result = $parser->parseOutcomeCell(
                    $ex->outcome_raw ?? '',
                    $ex->comments,
                );

                $ex->updateQuietly([
                    'outcome'         => $result['outcome'],
                    'outcome_comment' => $result['outcome_comment'],
                ]);

                if ($result['outcome'] !== 'pending') {
                    $reclassified++;
                }
            });

            $this->newLine(2);
            $this->info("{$reclassified} outcome(s) resolved from pending.");
        } else {
            $this->info('Pass 1 — nothing to reparse (use --force to override).');
        }

        // ── Pass 2: failure cause classification ──────────────────────────────

        $causeQuery = TestExecution::with('testScenario')
            ->where('outcome', 'fail')
            ->whereNull('failure_cause');

        if ($this->option('ids')) {
            $ids = array_map('intval', explode(',', $this->option('ids')));
            $causeQuery->whereIn('id', $ids);
        } elseif ($this->option('force')) {
            // Force mode: re-classify all fails regardless
            $causeQuery = TestExecution::with('testScenario')
                ->where('outcome', 'fail');

            if ($this->option('ids')) {
                $ids = array_map('intval', explode(',', $this->option('ids')));
                $causeQuery->whereIn('id', $ids);
            }
        }

        $forCause = $causeQuery->get(['id', 'outcome_raw', 'outcome_comment', 'comments', 'test_scenario_id']);

        if ($forCause->isNotEmpty()) {
            $this->info("Pass 2 — classifying failure causes for {$forCause->count()} execution(s)…");

            $this->withProgressBar($forCause, function (TestExecution $ex) use ($parser) {
                $cause = $parser->classifyFailureCause(
                    $ex->testScenario->expected_result ?? null,
                    $ex->outcome_raw ?? '',
                    $ex->outcome_comment,
                    $ex->comments,
                );

                $ex->updateQuietly(['failure_cause' => $cause]);
            });

            $this->newLine(2);
            $this->info('Failure cause classification complete.');
        } else {
            $this->info('Pass 2 — no unclassified failures found.');
        }

        return self::SUCCESS;
    }
}
