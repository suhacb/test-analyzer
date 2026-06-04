@extends('layouts.app')
@section('title', 'Review — Blank')
@section('content')

<h1>Review Queue</h1>
@include('review._tabs')

<div class="section-header">
    <h2>Blank executions <span class="badge-pending" style="font-size:.85rem">{{ $blankExecutions->total() }}</span></h2>
    <p style="color:#64748b;font-size:.85rem;margin-top:.3rem">Imported from templates that were submitted without any data. Resolve with an outcome or delete permanently.</p>
</div>

@if(session('success'))
<div class="card" style="margin-bottom:1rem;border-left:4px solid #22c55e;padding:.75rem 1rem;color:#166534">
    {{ session('success') }}
</div>
@endif

@if($blankExecutions->isEmpty())
    <div class="card" style="color:#64748b">No blank executions. All done!</div>
@else
<div class="card" style="padding:0;overflow:hidden">
<table>
    <thead>
        <tr>
            <th>Scenario</th>
            <th>Side</th>
            <th>Source file</th>
            <th style="width:400px">Decision</th>
        </tr>
    </thead>
    <tbody>
    @foreach($blankExecutions as $ex)
    <tr>
        <td>
            <a href="{{ route('test-scenarios.show', $ex->testScenario) }}" style="font-weight:600;font-size:.85rem">{{ $ex->testScenario->code }}</a><br>
            <span style="font-size:.78rem;color:#64748b">
                {{ $ex->testScenario->acceptanceCriteria->userStory->code }}
                / {{ $ex->testScenario->acceptanceCriteria->code }}
            </span>
        </td>
        <td><span class="badge-{{ $ex->side }}">{{ $ex->side }}</span></td>
        <td style="font-size:.78rem;color:#64748b;word-break:break-all">{{ basename($ex->source_file) }}</td>
        <td>
            <form method="POST" action="{{ route('review.executions.update', $ex) }}">
                @csrf @method('PATCH')
                <div style="display:flex;gap:.4rem;flex-direction:column">
                    <div style="display:flex;gap:.4rem;align-items:center;flex-wrap:wrap">
                        <select name="outcome" style="width:auto">
                            <option value="pass">✓ Pass</option>
                            <option value="soft_pass">~ Soft pass</option>
                            <option value="fail">✗ Fail</option>
                        </select>
                        <input type="datetime-local" name="tested_at"
                               style="font-size:.8rem;padding:4px 6px;border:1px solid #cbd5e1;border-radius:4px"
                               title="Test date (optional)">
                        <button type="submit" class="btn btn-primary btn-sm">Resolve</button>
                    </div>
                    <textarea name="review_notes" rows="2" placeholder="Review notes (optional)"></textarea>
                </div>
            </form>
            <form method="POST" action="{{ route('review.executions.destroy', $ex) }}" style="margin-top:.5rem"
                  onsubmit="return confirm('Permanently delete execution #{{ $ex->id }}? This cannot be undone.')">
                @csrf @method('DELETE')
                <button type="submit" class="btn btn-sm" style="background:#ef4444;color:#fff">Delete</button>
            </form>
        </td>
    </tr>
    @endforeach
    </tbody>
</table>
</div>
<div class="pagination">{{ $blankExecutions->links() }}</div>
@endif

@endsection
