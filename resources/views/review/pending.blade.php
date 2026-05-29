@extends('layouts.app')
@section('title', 'Review — Pending')
@section('content')

<h1>Review Queue</h1>
@include('review._tabs')

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
<div class="pagination">{{ $pendingExecutions->links() }}</div>
@endif

@endsection
