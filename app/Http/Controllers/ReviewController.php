<?php

namespace App\Http\Controllers;

use App\Models\TestExecution;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ReviewController extends Controller
{
    public function index(): View
    {
        $pendingExecutions = TestExecution::with('testScenario.acceptanceCriteria.userStory')
            ->where('outcome', 'pending')
            ->orderBy('id')
            ->paginate(30, ['*'], 'exec_page');

        $failedJobs = DB::table('failed_jobs')
            ->orderByDesc('failed_at')
            ->paginate(20, ['*'], 'job_page');

        $failedJobs->through(function ($job) {
            $job->parsed = $this->parseJobPayload($job->payload);
            $job->short_exception = strtok($job->exception, "\n");
            return $job;
        });

        return view('review.index', compact('pendingExecutions', 'failedJobs'));
    }

    public function updateExecution(Request $request, TestExecution $testExecution): RedirectResponse
    {
        $data = $request->validate([
            'outcome'      => 'required|in:pass,soft_pass,fail',
            'review_notes' => 'nullable|string|max:2000',
        ]);

        $testExecution->update([
            'outcome'      => $data['outcome'],
            'review_notes' => $data['review_notes'] ?? null,
            'reviewed_at'  => now(),
        ]);

        return back()->with('success', "Execution #{$testExecution->id} marked as {$data['outcome']}.");
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
