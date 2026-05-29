@extends('layouts.app')
@section('title', 'Review — AI Flagged')
@section('content')

<h1>Review Queue</h1>
@include('review._tabs')

<div class="section-header">
    <h2>AI-flagged executions <span class="badge-fail" style="font-size:.85rem">{{ $flaggedExecutions->total() }}</span></h2>
</div>

{{-- Filters --}}
<form method="GET" action="{{ route('review.flagged') }}" class="filters">
    <div>
        <label>User story</label>
        <select name="user_story_id">
            <option value="">All</option>
            @foreach($userStories as $us)
                <option value="{{ $us->id }}" {{ request('user_story_id') == $us->id ? 'selected' : '' }}>
                    {{ $us->code }} — {{ $us->title }}
                </option>
            @endforeach
        </select>
    </div>
    <div>
        <label>Acceptance criteria</label>
        <select name="acceptance_criteria_id">
            <option value="">All</option>
            @foreach($acceptanceCriteria as $ac)
                <option value="{{ $ac->id }}" {{ request('acceptance_criteria_id') == $ac->id ? 'selected' : '' }}>
                    {{ $ac->code }} — {{ $ac->title }}
                </option>
            @endforeach
        </select>
    </div>
    <div>
        <label>Side</label>
        <select name="side">
            <option value="">Both</option>
            <option value="provider" {{ request('side') === 'provider' ? 'selected' : '' }}>Provider</option>
            <option value="client"   {{ request('side') === 'client'   ? 'selected' : '' }}>Client</option>
        </select>
    </div>
    <div style="display:flex;gap:.4rem;align-items:flex-end">
        <button type="submit" class="btn btn-primary btn-sm">Filter</button>
        <a href="{{ route('review.flagged') }}" class="btn btn-sm" style="background:#e2e8f0;color:#475569">Reset</a>
    </div>
</form>

@if($flaggedExecutions->isEmpty())
    <div class="card" style="color:#64748b">No flagged executions match the current filter.</div>
@else

{{-- Bulk form wraps the table --}}
<form id="bulk-form" method="POST" action="{{ route('review.executions.bulk-dismiss') }}">
    @csrf
<div class="card" style="padding:0;overflow:hidden">
<table>
    <thead>
        <tr>
            <th style="width:2rem"><input type="checkbox" id="select-all" title="Select all"></th>
            <th>Scenario</th>
            <th>Side</th>
            <th>Outcome</th>
            <th>Tester comment</th>
            <th>AI reason</th>
            <th></th>
        </tr>
    </thead>
    <tbody>
    @foreach($flaggedExecutions as $ex)
    <tr>
        <td><input type="checkbox" class="row-check" name="ids[]" value="{{ $ex->id }}"></td>
        <td>
            <a href="{{ route('test-scenarios.show', $ex->testScenario) }}">{{ $ex->testScenario->code }}</a><br>
            <span style="font-size:.78rem;color:#64748b">{{ $ex->testScenario->acceptanceCriteria->code }}</span><br>
            <span style="font-size:.75rem;color:#94a3b8">{{ $ex->testScenario->acceptanceCriteria->userStory->code }}</span>
        </td>
        <td><span class="badge-{{ $ex->side }}">{{ $ex->side }}</span></td>
        <td>
            <span class="badge-{{ $ex->outcome === 'pending' ? 'pending' : 'fail' }}">{{ $ex->outcome }}</span><br>
            <span style="font-size:.75rem;color:#94a3b8">{{ $ex->outcome_raw }}</span>
        </td>
        <td style="font-size:.82rem;max-width:220px">{{ $ex->comments ?? '—' }}</td>
        <td style="font-size:.82rem;color:#b45309;max-width:240px">{{ $ex->ai_flag_reason }}</td>
        <td>
            {{-- form= links this button to the per-row dismiss form below, not the bulk form --}}
            <button type="submit" form="dismiss-{{ $ex->id }}"
                    class="btn btn-sm" style="background:#e2e8f0;color:#475569;white-space:nowrap"
                    onclick="return confirm('Dismiss this flag?')">Dismiss</button>
        </td>
    </tr>
    @endforeach
    </tbody>
</table>
</div>
</form>

{{-- Per-row dismiss forms (outside the bulk form to avoid nesting) --}}
@foreach($flaggedExecutions as $ex)
<form id="dismiss-{{ $ex->id }}" method="POST" action="{{ route('review.executions.dismiss-flag', $ex) }}" style="display:none">
    @csrf
</form>
@endforeach

<div class="pagination">{{ $flaggedExecutions->appends(request()->query())->links() }}</div>

{{-- Floating bulk action bar --}}
<div id="bulk-bar" style="display:none;position:fixed;bottom:0;left:0;right:0;background:#1a1a2e;color:#fff;padding:.85rem 2rem;display:none;align-items:center;gap:1rem;z-index:100;box-shadow:0 -2px 12px #0003">
    <span id="bulk-count" style="font-size:.9rem;flex:1"></span>
    <button type="submit" form="bulk-form" class="btn btn-danger">Dismiss selected</button>
    <button type="button" id="deselect-all" class="btn btn-sm" style="background:#ffffff22;color:#fff">Deselect all</button>
</div>

<script>
(function () {
    const selectAll  = document.getElementById('select-all');
    const bulkBar    = document.getElementById('bulk-bar');
    const bulkCount  = document.getElementById('bulk-count');
    const deselectBtn = document.getElementById('deselect-all');

    function checked() { return [...document.querySelectorAll('.row-check:checked')]; }
    function all()     { return [...document.querySelectorAll('.row-check')]; }

    function update() {
        const n = checked().length;
        const total = all().length;
        bulkBar.style.display = n > 0 ? 'flex' : 'none';
        bulkCount.textContent = n + ' of ' + total + ' selected';
        selectAll.checked       = n === total && total > 0;
        selectAll.indeterminate = n > 0 && n < total;
    }

    selectAll.addEventListener('change', function () {
        all().forEach(c => c.checked = this.checked);
        update();
    });

    document.addEventListener('change', function (e) {
        if (e.target.classList.contains('row-check')) update();
    });

    deselectBtn.addEventListener('click', function () {
        all().forEach(c => c.checked = false);
        selectAll.checked = false;
        update();
    });
}());
</script>
@endif

@endsection
