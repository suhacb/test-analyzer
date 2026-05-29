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
            @if(!is_null($ex->flagged_by_ai))
                @if($ex->flagged_by_ai)
                    <br><span class="badge-fail" style="font-size:.72rem;margin-top:.3rem;display:inline-block">⚠ AI: doubtful</span>
                    <div style="font-size:.72rem;color:#94a3b8;margin-top:.2rem">{{ $ex->ai_flag_reason }}</div>
                @else
                    <br><span style="font-size:.72rem;color:#22c55e;margin-top:.3rem;display:inline-block">✓ AI: ok</span>
                @endif
            @endif
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
            @if(is_null($ex->flagged_by_ai))
            <form method="POST" action="{{ route('review.executions.analyse', $ex) }}" style="margin-top:.4rem">
                @csrf
                <button type="submit" class="btn btn-sm" style="background:#6366f1;color:#fff;font-size:.75rem">AI check</button>
            </form>
            @endif
        </td>
    </tr>
    @endforeach
    </tbody>
</table>
</div>
<div class="pagination">{{ $pendingExecutions->appends(['job_page' => request('job_page')])->links() }}</div>
@endif

{{-- AI-flagged executions --}}
<div class="section-header" style="margin-top:2rem">
    <h2>AI-flagged executions <span class="badge-fail" style="font-size:.85rem">{{ $flaggedExecutions->total() }}</span></h2>
</div>

@if($flaggedExecutions->isEmpty())
    <div class="card" style="color:#64748b">No executions flagged as doubtful.</div>
@else
<div class="card" style="padding:0;overflow:hidden">
<table>
    <thead>
        <tr>
            <th>Scenario</th>
            <th>Side</th>
            <th>Outcome</th>
            <th>Tester comment</th>
            <th>AI reason</th>
        </tr>
    </thead>
    <tbody>
    @foreach($flaggedExecutions as $ex)
    <tr>
        <td>
            <a href="{{ route('test-scenarios.show', $ex->testScenario) }}">{{ $ex->testScenario->code }}</a><br>
            <span style="font-size:.78rem;color:#64748b">{{ $ex->testScenario->acceptanceCriteria->userStory->code }}</span>
        </td>
        <td><span class="badge-{{ $ex->side }}">{{ $ex->side }}</span></td>
        <td>
            <span class="badge-{{ $ex->outcome === 'pending' ? 'pending' : 'fail' }}" style="font-size:.8rem">{{ $ex->outcome }}</span><br>
            <span style="font-size:.75rem;color:#94a3b8">{{ $ex->outcome_raw }}</span>
        </td>
        <td style="font-size:.82rem;max-width:240px">{{ $ex->comments ?? '—' }}</td>
        <td style="font-size:.82rem;color:#f59e0b;max-width:260px">{{ $ex->ai_flag_reason }}</td>
    </tr>
    @endforeach
    </tbody>
</table>
</div>
<div class="pagination">{{ $flaggedExecutions->appends(request()->except('flag_page'))->links() }}</div>
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
