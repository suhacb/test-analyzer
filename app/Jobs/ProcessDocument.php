<?php

namespace App\Jobs;

use App\Models\AcceptanceCriteria;
use App\Models\TestExecution;
use App\Models\TestScenario;
use App\Models\UserStory;
use App\Services\TestReportParser;
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

    public function handle(TestReportParser $parser): void
    {
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

        TestExecution::updateOrCreate(
            [
                'test_scenario_id' => $scenario->id,
                'side'             => $this->side,
            ],
            [
                'outcome'     => $data['outcome'],
                'outcome_raw' => $data['outcome_raw'],
                'comments'    => $data['comments'],
                'tester_name' => $data['tester_name'],
                'browser'     => $data['browser'],
                'tested_at'   => $data['tested_at'],
                'source_file' => $this->path,
            ],
        );

        Log::info('ProcessDocument: done', [
            'file'     => basename($this->path),
            'scenario' => $data['scenario_code'],
            'side'     => $this->side,
            'outcome'  => $data['outcome'],
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
