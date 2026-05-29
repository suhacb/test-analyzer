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
            ⚠ Provider passed, client not
        </a>
        @if(request()->hasAny(['user_story_id','outcome','mismatch']))
            <a href="{{ route('test-scenarios.index') }}" class="btn btn-sm" style="background:#f1f5f9;color:#475569">Clear</a>
        @endif
    </div>
</form>

{{-- Legend --}}
@php
    $legendCircle = fn(string $fill, string $border, string $label) =>
        '<span style="display:inline-flex;align-items:center;gap:4px">'
        . '<span style="display:inline-block;width:11px;height:11px;border-radius:50%;background:' . $fill . ';border:2px solid ' . $border . ';flex-shrink:0"></span>'
        . '<span>' . e($label) . '</span>'
        . '</span>';
@endphp
<div style="display:flex;flex-wrap:wrap;gap:.75rem 1.25rem;font-size:.73rem;color:#64748b;margin-bottom:.9rem;align-items:center">
    <span style="font-weight:600;color:#94a3b8">Outcome:</span>
    {!! $legendCircle('#22c55e', '#94a3b8', 'Pass') !!}
    {!! $legendCircle('#86efac', '#94a3b8', 'Soft pass') !!}
    {!! $legendCircle('#ef4444', '#94a3b8', 'Fail') !!}
    {!! $legendCircle('#d1d5db', '#94a3b8', 'Pending') !!}
    <span style="width:1px;height:14px;background:#e2e8f0;display:inline-block"></span>
    <span style="font-weight:600;color:#94a3b8">Side (border):</span>
    {!! $legendCircle('#e2e8f0', '#7c3aed', 'Provider') !!}
    {!! $legendCircle('#e2e8f0', '#2563eb', 'Client') !!}
    <span style="color:#94a3b8;margin-left:.25rem">Ordered left → right by test date.</span>
</div>

<div class="card" style="padding:0;overflow:hidden">
<table>
    <thead>
        <tr>
            <th style="width:190px">Code</th>
            <th>Title</th>
            <th style="width:100px">Executions</th>
            <th style="width:60px">US</th>
            <th style="width:120px">Provider</th>
            <th style="width:120px">Client</th>
        </tr>
    </thead>
    <tbody>
    @foreach($testScenarios as $ts)
    @php
        $execsSorted = $ts->executions->sortBy(
            fn($e) => $e->tested_at?->timestamp ?? PHP_INT_MAX
        );

        $outcomeColor = [
            'pass'      => '#22c55e',
            'soft_pass' => '#86efac',
            'fail'      => '#ef4444',
            'pending'   => '#d1d5db',
        ];
        $sideColor = [
            'provider' => '#7c3aed',
            'client'   => '#2563eb',
        ];
    @endphp
    @php $bySide = $ts->executions->sortByDesc('id')->unique('side')->keyBy('side'); @endphp
    <tr>
        <td><a href="{{ route('test-scenarios.show', $ts) }}" style="font-weight:600;font-size:.83rem">{{ $ts->code }}</a></td>
        <td>
            <a href="{{ route('test-scenarios.show', $ts) }}">{{ $ts->title }}</a>
            <div style="font-size:.75rem;color:#94a3b8">
                <a href="{{ route('acceptance-criteria.show', $ts->acceptanceCriteria) }}" style="color:#94a3b8">{{ $ts->acceptanceCriteria->code }}</a>
            </div>
        </td>
        <td>
            @if($execsSorted->isEmpty())
                <span style="color:#94a3b8;font-size:.78rem">—</span>
            @else
            <div style="display:flex;gap:5px;align-items:center;flex-wrap:wrap">
                @foreach($execsSorted as $ex)
                @php
                    $fill   = $outcomeColor[$ex->outcome] ?? '#d1d5db';
                    $border = $sideColor[$ex->side]       ?? '#94a3b8';
                    $causeLabel = $ex->failure_cause
                        ? (\App\Services\AiOutcomeParser::CAUSE_LABELS[$ex->failure_cause] ?? $ex->failure_cause)
                        : null;
                    $tip = ucfirst($ex->side)
                         . ' · ' . ucfirst(str_replace('_', ' ', $ex->outcome))
                         . ($ex->tester_name    ? ' · ' . $ex->tester_name : '')
                         . ($ex->tested_at      ? ' · ' . $ex->tested_at->format('d.m.Y') : '')
                         . ($causeLabel         ? ' · ' . $causeLabel : '')
                         . ($ex->outcome_comment ? "\n" . Str::limit($ex->outcome_comment, 80) : '');
                @endphp
                <span title="{{ $tip }}"
                      style="display:inline-block;width:14px;height:14px;border-radius:50%;
                             background:{{ $fill }};border:2.5px solid {{ $border }};
                             cursor:default;flex-shrink:0"></span>
                @endforeach
            </div>
            @endif
        </td>
        <td>
            <a href="{{ route('user-stories.show', $ts->acceptanceCriteria->userStory) }}" style="font-size:.82rem">
                {{ $ts->acceptanceCriteria->userStory->code }}
            </a>
        </td>
        <td><x-outcome :outcome="$bySide['provider']->outcome ?? null" /></td>
        <td><x-outcome :outcome="$bySide['client']->outcome ?? null" /></td>
    </tr>
    @endforeach
    </tbody>
</table>
</div>
<div class="pagination">{{ $testScenarios->links() }}</div>

@endsection
