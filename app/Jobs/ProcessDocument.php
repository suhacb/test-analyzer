<?php

namespace App\Jobs;

use App\Models\AcceptanceCriteria;
use App\Models\TestExecution;
use App\Models\TestScenario;
use App\Models\UserStory;
use App\Services\AiOutcomeParser;
use App\Services\TestReportParser;
use App\Services\VectorIndexer;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class ProcessDocument implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public function __construct(
        public readonly string $path,
        public readonly string $side,
    ) {
        $this->onQueue('documents');
    }

    public function handle(TestReportParser $parser, AiOutcomeParser $aiParser, VectorIndexer $indexer): void
    {
        $hash = md5_file($this->path);

        if ($hash === false) {
            throw new \RuntimeException("Cannot read file for hashing: {$this->path}");
        }

        $data = $parser->parse($this->path);

        $userStory = UserStory::firstOrCreate(
            ['code' => $data['user_story_code']],
            ['title' => $data['user_story_title']],
        );
        if (!$userStory->wasRecentlyCreated && is_null($userStory->title) && $data['user_story_title']) {
            $userStory->update(['title' => $data['user_story_title']]);
        }

        $ac = AcceptanceCriteria::firstOrCreate(
            ['code' => $data['ac_code']],
            [
                'user_story_id' => $userStory->id,
                'title'         => $data['ac_title'],
            ],
        );
        if (!$ac->wasRecentlyCreated && is_null($ac->title) && $data['ac_title']) {
            $ac->update(['title' => $data['ac_title']]);
        }

        $scenario = TestScenario::firstOrCreate(
            ['code' => $data['scenario_code']],
            [
                'acceptance_criteria_id' => $ac->id,
                'title'                  => $data['scenario_title'],
                'user_role'              => $data['user_role'],
                'preconditions'          => $data['preconditions'],
                'test_steps'             => $data['test_steps'],
                'expected_result'        => $data['expected_result'],
            ],
        );

        $alreadyImported = TestExecution::where('test_scenario_id', $scenario->id)
            ->where('side', $this->side)
            ->where('source_file_hash', $hash)
            ->exists();

        if ($alreadyImported) {
            Log::info('ProcessDocument: skipped (already imported)', [
                'file'     => basename($this->path),
                'scenario' => $data['scenario_code'],
                'side'     => $this->side,
                'hash'     => $hash,
            ]);
            return;
        }

        // Enhanced outcome parsing: regex first, AI fallback for unclear cells
        $parsed = $aiParser->parseOutcomeCell(
            $data['outcome_raw'] ?? '',
            $data['comments'],
        );

        // If AI couldn't determine outcome, leave as pending (goes to review queue)
        $outcome        = $parsed['outcome'];
        $outcomeComment = $parsed['outcome_comment'];

        // Classify failure cause for definitive fails
        $failureCause = null;
        if ($outcome === 'fail') {
            $failureCause = $aiParser->classifyFailureCause(
                $scenario->expected_result,
                $data['outcome_raw'] ?? '',
                $outcomeComment,
                $data['comments'],
            );
        }

        $execution = TestExecution::create([
            'test_scenario_id' => $scenario->id,
            'side'             => $this->side,
            'outcome'          => $outcome,
            'outcome_raw'      => $data['outcome_raw'],
            'outcome_comment'  => $outcomeComment,
            'failure_cause'    => $failureCause,
            'comments'         => $data['comments'],
            'tester_name'      => $data['tester_name'],
            'browser'          => $data['browser'],
            'tested_at'        => $data['tested_at'],
            'source_file'      => $this->path,
            'source_file_hash' => $hash,
        ]);

        try {
            $indexer->indexExecution($execution);
        } catch (\Throwable $e) {
            Log::warning('ProcessDocument: Qdrant indexing failed', [
                'execution_id' => $execution->id,
                'error'        => $e->getMessage(),
            ]);
        }

        Log::info('ProcessDocument: done', [
            'file'         => basename($this->path),
            'scenario'     => $data['scenario_code'],
            'side'         => $this->side,
            'outcome'      => $outcome,
            'failure_cause' => $failureCause,
            'needs_review' => $parsed['needs_review'],
            'hash'         => $hash,
        ]);
    }

    public function failed(\Throwable $e): void
    {
        Log::error('ProcessDocument: failed', [
            'file'  => basename($this->path),
            'side'  => $this->side,
            'error' => $e->getMessage(),
        ]);
    }
}
