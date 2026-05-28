@extends('layouts.app')
@section('title', 'Review Queue')
@section('content')

<h1>Review Queue</h1>

{{-- Pending executions --}}
<div class="section-header">
    <h2>Pending executions <span class="badge-pending" style="font-size:.85rem">{{ $pendingExecutions->total() }}</span></h2>
</div>

@if($pendingExecutions->isEmpty())
    <div class="card" style="color:#64748b">No pending executions. All done!</div>
@else
<div class="card" style="padding:0;overflow:hidden">
<table>
    <thead>
        <tr>
            <th>Scenario</th>
            <th>Side</th>
            <th>Raw outcome</th>
            <th>Tester</th>
            <th style="width:340px">Decision</th>
        </tr>
    </thead>
    <tbody>
    @foreach($pendingExecutions as $ex)
    <tr>
        <td>
            <a href="{{ route('test-scenarios.show', $ex->testScenario) }}">{{ $ex->testScenario->code }}</a><br>
            <span style="font-size:.78rem;color:#64748b">{{ $ex->testScenario->acceptanceCriteria->userStory->code }}</span>
        </td>
        <td><span class="badge-{{ $ex->side }}">{{ $ex->side }}</span></td>
        <td><div class="raw-text">{{ $ex->outcome_raw }}</div></td>
        <td style="font-size:.8rem">{{ $ex->tester_name ?? '—' }}</td>
        <td>
            <form method="POST" action="{{ route('review.executions.update', $ex) }}">
                @csrf @method('PATCH')
                <div style="display:flex;gap:.4rem;align-items:flex-start;flex-direction:column">
                    <div style="display:flex;gap:.4rem">
                        <select name="outcome" style="width:auto">
                            <option value="pass">✓ Pass</option>
                            <option value="soft_pass">~ Soft pass</option>
                            <option value="fail">✗ Fail</option>
                        </select>
                        <button type="submit" class="btn btn-primary btn-sm">Save</button>
                    </div>
                    <textarea name="review_notes" rows="2" placeholder="Review notes (optional)">{{ $ex->review_notes }}</textarea>
                </div>
            </form>
        </td>
    </tr>
    @endforeach
    </tbody>
</table>
</div>
<div class="pagination">{{ $pendingExecutions->appends(['job_page' => request('job_page')])->links() }}</div>
@endif

{{-- Failed import jobs --}}
<div class="section-header" style="margin-top:2rem">
    <h2>Failed import jobs <span class="badge-fail" style="font-size:.85rem">{{ $failedJobs->total() }}</span></h2>
</div>

@if($failedJobs->isEmpty())
    <div class="card" style="color:#64748b">No failed import jobs.</div>
@else
<div class="card" style="padding:0;overflow:hidden">
<table>
    <thead>
        <tr>
            <th>File</th>
            <th>Side</th>
            <th>Error</th>
            <th>Failed at</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
    @foreach($failedJobs as $job)
    <tr>
        <td style="font-size:.82rem;word-break:break-all">{{ $job->parsed['file'] }}</td>
        <td><span class="badge-{{ $job->parsed['side'] }}">{{ $job->parsed['side'] }}</span></td>
        <td style="font-size:.78rem;color:#64748b;max-width:300px">{{ $job->short_exception }}</td>
        <td style="font-size:.8rem;white-space:nowrap">{{ $job->failed_at }}</td>
        <td style="white-space:nowrap">
            <form class="inline" method="POST" action="{{ route('review.failed-jobs.retry', $job->uuid) }}">
                @csrf
                <button class="btn btn-success btn-sm">Retry</button>
            </form>
            <form class="inline" method="POST" action="{{ route('review.failed-jobs.dismiss', $job->uuid) }}" onsubmit="return confirm('Dismiss this job?')">
                @csrf @method('DELETE')
                <button class="btn btn-danger btn-sm">Dismiss</button>
            </form>
        </td>
    </tr>
    @endforeach
    </tbody>
</table>
</div>
<div class="pagination">{{ $failedJobs->appends(['exec_page' => request('exec_page')])->links() }}</div>
@endif

@endsection
