<?php

namespace App\Console\Commands;

use App\Models\TestExecution;
use App\Services\AiOutcomeParser;
use App\Services\TestReportParser;
use App\Services\VectorIndexer;
use Illuminate\Console\Command;

class ReprocessExecutions extends Command
{
    protected $signature = 'documents:reprocess-outcomes
                            {--regex-only : Re-apply regex normalization only, skip AI and Qdrant re-indexing}
                            {--force      : Re-run even on executions that already have an outcome_comment}
                            {--ids=       : Comma-separated execution IDs to process}';

    protected $description = 'Re-parse outcome cells and classify failure causes on already-imported executions';

    public function handle(AiOutcomeParser $aiParser, TestReportParser $parser, VectorIndexer $indexer): int
    {
        if ($this->option('regex-only')) {
            return $this->runRegexOnly($parser);
        }

        return $this->runFull($aiParser, $indexer);
    }

    // ── Regex-only pass ───────────────────────────────────────────────────────

    private function runRegexOnly(TestReportParser $parser): int
    {
        $executions = TestExecution::whereNotNull('outcome_raw')
            ->when($this->option('ids'), fn ($q) => $q->whereIn('id', $this->parseIds()))
            ->get(['id', 'outcome_raw', 'outcome']);

        $this->info("Processing {$executions->count()} execution(s)…");

        $changed = 0;

        $this->withProgressBar($executions, function (TestExecution $ex) use ($parser, &$changed) {
            $newOutcome = $parser->normaliseOutcome($ex->outcome_raw);
            if ($newOutcome !== $ex->outcome) {
                $ex->updateQuietly(['outcome' => $newOutcome]);
                $changed++;
            }
        });

        $this->newLine(2);
        $this->info("Done. {$changed} outcome(s) updated.");

        return self::SUCCESS;
    }

    // ── Full AI pass ──────────────────────────────────────────────────────────

    private function runFull(AiOutcomeParser $aiParser, VectorIndexer $indexer): int
    {
        // Pass 1: outcome + comment re-parsing
        $outcomeQuery = TestExecution::whereNotNull('outcome_raw');

        if ($this->option('ids')) {
            $outcomeQuery->whereIn('id', $this->parseIds());
        } elseif (! $this->option('force')) {
            $outcomeQuery->whereNull('outcome_comment')->where('outcome', 'pending');
        }

        $forOutcome = $outcomeQuery->get(['id', 'outcome_raw', 'comments']);

        if ($forOutcome->isNotEmpty()) {
            $this->info("Pass 1 — re-parsing outcome cells for {$forOutcome->count()} execution(s)…");

            $reclassified = 0;

            $this->withProgressBar($forOutcome, function (TestExecution $ex) use ($aiParser, &$reclassified) {
                $result = $aiParser->parseOutcomeCell($ex->outcome_raw ?? '', $ex->comments);
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

        // Pass 2: failure cause classification
        $causeQuery = TestExecution::with('testScenario')->where('outcome', 'fail');

        if ($this->option('ids')) {
            $causeQuery->whereIn('id', $this->parseIds());
        } elseif (! $this->option('force')) {
            $causeQuery->whereNull('failure_cause');
        }

        $forCause = $causeQuery->get(['id', 'outcome_raw', 'outcome_comment', 'comments', 'test_scenario_id']);

        if ($forCause->isNotEmpty()) {
            $this->info("Pass 2 — classifying failure causes for {$forCause->count()} execution(s)…");

            $this->withProgressBar($forCause, function (TestExecution $ex) use ($aiParser) {
                $ex->updateQuietly([
                    'failure_cause' => $aiParser->classifyFailureCause(
                        $ex->testScenario->expected_result ?? null,
                        $ex->outcome_raw ?? '',
                        $ex->outcome_comment,
                        $ex->comments,
                    ),
                ]);
            });

            $this->newLine(2);
            $this->info('Failure cause classification complete.');
        } else {
            $this->info('Pass 2 — no unclassified failures found.');
        }

        // Pass 3: re-index in Qdrant
        $toIndex = TestExecution::with('testScenario.acceptanceCriteria.userStory');

        if ($this->option('ids')) {
            $toIndex->whereIn('id', $this->parseIds());
        } elseif (! $this->option('force')) {
            $touchedIds = $forOutcome->pluck('id')->merge($forCause->pluck('id'))->unique();
            if ($touchedIds->isEmpty()) {
                $this->info('Pass 3 — nothing to re-index.');
                return self::SUCCESS;
            }
            $toIndex->whereIn('id', $touchedIds);
        }

        $executions = $toIndex->get();

        if ($executions->isNotEmpty()) {
            $this->info("Pass 3 — re-indexing {$executions->count()} execution(s) in Qdrant…");
            $this->withProgressBar($executions, fn (TestExecution $ex) => $indexer->indexExecution($ex));
            $this->newLine(2);
            $this->info('Qdrant index updated.');
        }

        return self::SUCCESS;
    }

    private function parseIds(): array
    {
        return array_map('intval', explode(',', $this->option('ids')));
    }
}
