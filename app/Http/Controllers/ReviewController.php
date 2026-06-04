<?php

namespace App\Http\Controllers;

use App\Jobs\FlagDoubtfulExecution;
use App\Models\AcceptanceCriteria;
use App\Models\TestExecution;
use App\Models\UserStory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ReviewController extends Controller
{
    public function index(): RedirectResponse
    {
        return redirect()->route('review.pending');
    }

    public function pending(): View
    {
        $pendingExecutions = TestExecution::with('testScenario.acceptanceCriteria.userStory')
            ->where('outcome', 'pending')
            ->orderBy('id')
            ->paginate(30, ['*'], 'exec_page');

        return view('review.pending', compact('pendingExecutions'));
    }

    public function flagged(Request $request): View
    {
        $query = TestExecution::with('testScenario.acceptanceCriteria.userStory')
            ->where('flagged_by_ai', true)
            ->whereNull('ai_flag_dismissed_at');

        if ($request->filled('user_story_id')) {
            $query->whereHas('testScenario.acceptanceCriteria', fn ($q) =>
                $q->where('user_story_id', $request->integer('user_story_id'))
            );
        }

        if ($request->filled('acceptance_criteria_id')) {
            $query->whereHas('testScenario', fn ($q) =>
                $q->where('acceptance_criteria_id', $request->integer('acceptance_criteria_id'))
            );
        }

        if ($request->filled('side')) {
            $query->where('side', $request->input('side'));
        }

        $flaggedExecutions = $query->orderBy('id')->paginate(30, ['*'], 'flag_page');

        $userStories       = UserStory::orderBy('code')->get(['id', 'code', 'title']);
        $acceptanceCriteria = AcceptanceCriteria::orderBy('code')->get(['id', 'code', 'title', 'user_story_id']);

        return view('review.flagged', compact('flaggedExecutions', 'userStories', 'acceptanceCriteria'));
    }

    public function failedJobs(): View
    {
        $failedJobs = DB::table('failed_jobs')
            ->orderByDesc('failed_at')
            ->paginate(20, ['*'], 'job_page');

        $failedJobs->through(function ($job) {
            $job->parsed = $this->parseJobPayload($job->payload);
            $job->short_exception = strtok($job->exception, "\n");
            return $job;
        });

        return view('review.failed-jobs', compact('failedJobs'));
    }

    public function blank(): View
    {
        $blankExecutions = TestExecution::with('testScenario.acceptanceCriteria.userStory')
            ->blank()
            ->orderBy('id')
            ->paginate(30, ['*'], 'blank_page');

        return view('review.blank', compact('blankExecutions'));
    }

    public function updateExecution(Request $request, TestExecution $testExecution): RedirectResponse
    {
        $data = $request->validate([
            'outcome'      => 'required|in:pass,soft_pass,fail',
            'review_notes' => 'nullable|string|max:2000',
            'tested_at'    => 'nullable|date',
        ]);

        $update = [
            'outcome'      => $data['outcome'],
            'review_notes' => $data['review_notes'] ?? null,
            'reviewed_at'  => now(),
        ];

        if ($request->has('tested_at')) {
            $update['tested_at'] = $data['tested_at'];
        }

        $testExecution->update($update);

        return back()->with('success', "Execution #{$testExecution->id} marked as {$data['outcome']}.");
    }

    public function destroyExecution(TestExecution $testExecution): RedirectResponse
    {
        $id = $testExecution->id;
        $testExecution->delete();

        return back()->with('success', "Execution #{$id} deleted.");
    }

    public function analyseExecution(TestExecution $testExecution): RedirectResponse
    {
        FlagDoubtfulExecution::dispatch($testExecution);

        return back()->with('success', "AI analysis queued for execution #{$testExecution->id}.");
    }

    public function bulkDismissFlags(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'ids'   => 'required|array|min:1',
            'ids.*' => 'integer|exists:test_executions,id',
        ]);

        $count = TestExecution::whereIn('id', $data['ids'])
            ->where('flagged_by_ai', true)
            ->whereNull('ai_flag_dismissed_at')
            ->update(['ai_flag_dismissed_at' => now()]);

        return back()->with('success', "{$count} flag(s) dismissed.");
    }

    public function dismissFlag(TestExecution $testExecution): RedirectResponse
    {
        $testExecution->update(['ai_flag_dismissed_at' => now()]);

        return back()->with('success', "Flag dismissed for execution #{$testExecution->id}.");
    }

    public function retryJob(string $uuid): RedirectResponse
    {
        Artisan::call('queue:retry', ['id' => [$uuid]]);

        return back()->with('success', "Job {$uuid} re-queued.");
    }

    public function dismissJob(string $uuid): RedirectResponse
    {
        DB::table('failed_jobs')->where('uuid', $uuid)->delete();

        return back()->with('success', "Job {$uuid} dismissed.");
    }

    private function parseJobPayload(string $payload): array
    {
        try {
            $decoded = json_decode($payload, true);
            $command = unserialize($decoded['data']['command']);
            return [
                'file' => basename($command->path),
                'side' => $command->side,
            ];
        } catch (\Throwable) {
            return ['file' => 'Unknown', 'side' => '—'];
        }
    }
}
