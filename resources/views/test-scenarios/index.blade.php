@extends('layouts.app')
@section('title', 'Test Scenarios')
@section('content')

<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1.5rem">
    <h1 style="margin:0">Test Scenarios</h1>
    <a href="{{ route('test-scenarios.export', request()->query()) }}" class="btn btn-sm" style="background:#16a34a;color:#fff">
        ↓ Export CSV
    </a>
</div>

<form class="filters" method="GET">
    <div>
        <label>User Story</label>
        <select name="user_story_id" onchange="this.form.submit()">
            <option value="">All</option>
            @foreach($userStories as $us)
            <option value="{{ $us->id }}" @selected(request('user_story_id') == $us->id)>{{ $us->code }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label>Outcome (any side)</label>
        <select name="outcome" onchange="this.form.submit()">
            <option value="">All</option>
            @foreach(['pass' => '✓ Pass', 'soft_pass' => '~ Soft pass', 'fail' => '✗ Fail', 'pending' => '? Pending'] as $val => $label)
            <option value="{{ $val }}" @selected(request('outcome') === $val)>{{ $label }}</option>
            @endforeach
        </select>
    </div>
    <div style="margin-left:auto;display:flex;gap:.5rem;align-items:flex-end">
        <a href="{{ route('test-scenarios.index', ['mismatch' => 1]) }}"
           class="btn btn-sm {{ request()->boolean('mismatch') ? 'btn-primary' : '' }}"
           style="{{ request()->boolean('mismatch') ? '' : 'background:#fef3c7;color:#92400e;border:1px solid #fcd34d' }}">
            ⚠ Provider passed, client not passed
        </a>
        @if(request()->hasAny(['user_story_id','outcome','mismatch']))
            <a href="{{ route('test-scenarios.index') }}" class="btn btn-sm" style="background:#f1f5f9;color:#475569">Clear</a>
        @endif
    </div>
</form>

<div class="card" style="padding:0;overflow:hidden">
<table>
    <thead>
        <tr>
            <th style="width:200px">Code</th>
            <th>Title</th>
            <th style="width:80px">US</th>
            <th style="width:130px">Provider</th>
            <th style="width:130px">Client (UAT)</th>
        </tr>
    </thead>
    <tbody>
    @foreach($testScenarios as $ts)
    @php $bySlide = $ts->executions->keyBy('side'); @endphp
    <tr>
        <td><a href="{{ route('test-scenarios.show', $ts) }}" style="font-weight:600;font-size:.83rem">{{ $ts->code }}</a></td>
        <td>
            <a href="{{ route('test-scenarios.show', $ts) }}">{{ $ts->title }}</a>
            <div style="font-size:.75rem;color:#94a3b8">
                <a href="{{ route('acceptance-criteria.show', $ts->acceptanceCriteria) }}" style="color:#94a3b8">{{ $ts->acceptanceCriteria->code }}</a>
            </div>
        </td>
        <td>
            <a href="{{ route('user-stories.show', $ts->acceptanceCriteria->userStory) }}" style="font-size:.82rem">
                {{ $ts->acceptanceCriteria->userStory->code }}
            </a>
        </td>
        <td><x-outcome :outcome="$bySlide['provider']->outcome ?? null" /></td>
        <td><x-outcome :outcome="$bySlide['client']->outcome ?? null" /></td>
    </tr>
    @endforeach
    </tbody>
</table>
</div>
<div class="pagination">{{ $testScenarios->links() }}</div>

@endsection
