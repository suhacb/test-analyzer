@extends('layouts.app')
@section('title', 'Test Executions')
@section('content')

@php
    use App\Services\AiOutcomeParser;
    $causeLabels = AiOutcomeParser::CAUSE_LABELS;
    $causeColors = [
        'software_bug'        => '#fee2e2',
        'lack_of_data'        => '#fef3c7',
        'no_integration_kis'  => '#ede9fe',
        'no_integration_euez' => '#dbeafe',
        'not_accessible'      => '#f1f5f9',
        'other'               => '#f1f5f9',
    ];
    $causeTextColors = [
        'software_bug'        => '#991b1b',
        'lack_of_data'        => '#92400e',
        'no_integration_kis'  => '#5b21b6',
        'no_integration_euez' => '#1e40af',
        'not_accessible'      => '#475569',
        'other'               => '#475569',
    ];
@endphp

<h1>Test Executions</h1>

<form class="filters" method="GET">
    <div>
        <label>Side</label>
        <select name="side" onchange="this.form.submit()">
            <option value="">All</option>
            <option value="provider" @selected(request('side') === 'provider')>Provider</option>
            <option value="client"   @selected(request('side') === 'client')>Client (UAT)</option>
        </select>
    </div>
    <div>
        <label>Outcome</label>
        <select name="outcome" onchange="this.form.submit()">
            <option value="">All</option>
            @foreach(['pass' => '✓ Pass', 'soft_pass' => '~ Soft pass', 'fail' => '✗ Fail', 'pending' => '? Pending'] as $val => $label)
            <option value="{{ $val }}" @selected(request('outcome') === $val)>{{ $label }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label>Failure cause</label>
        <select name="failure_cause" onchange="this.form.submit()">
            <option value="">All</option>
            @foreach($causeLabels as $val => $label)
            <option value="{{ $val }}" @selected(request('failure_cause') === $val)>{{ $label }}</option>
            @endforeach
        </select>
    </div>
    @if(request()->hasAny(['side','outcome','failure_cause','test_scenario_id']))
        <a href="{{ route('test-executions.index') }}" class="btn btn-sm" style="background:#f1f5f9;color:#475569">Clear</a>
    @endif
</form>

<div class="card" style="padding:0;overflow:hidden">
<table>
    <thead>
        <tr>
            <th style="width:200px">Scenario</th>
            <th>Title / Comment</th>
            <th style="width:60px">AC</th>
            <th style="width:60px">US</th>
            <th style="width:90px">Side</th>
            <th style="width:200px">Outcome</th>
            <th style="width:110px">Tester</th>
            <th style="width:90px">Tested</th>
        </tr>
    </thead>
    <tbody>
    @foreach($testExecutions as $ex)
    <tr>
        <td>
            <a href="{{ route('test-executions.show', $ex) }}" style="font-weight:600;font-size:.83rem">{{ $ex->testScenario->code }}</a>
        </td>
        <td>
            <a href="{{ route('test-scenarios.show', $ex->testScenario) }}" style="font-size:.88rem">{{ $ex->testScenario->title }}</a>
            @if($ex->outcome_comment)
                <div style="font-size:.75rem;color:#64748b;margin-top:.2rem;white-space:pre-wrap">{{ Str::limit($ex->outcome_comment, 120) }}</div>
            @endif
        </td>
        <td>
            <a href="{{ route('acceptance-criteria.show', $ex->testScenario->acceptanceCriteria) }}" style="font-size:.82rem;color:#64748b">
                {{ $ex->testScenario->acceptanceCriteria->code }}
            </a>
        </td>
        <td>
            <a href="{{ route('user-stories.show', $ex->testScenario->acceptanceCriteria->userStory) }}" style="font-size:.82rem;color:#64748b">
                {{ $ex->testScenario->acceptanceCriteria->userStory->code }}
            </a>
        </td>
        <td>
            <span class="badge-{{ $ex->side }}">{{ $ex->side === 'provider' ? 'Provider' : 'Client' }}</span>
        </td>
        <td>
            <x-outcome :outcome="$ex->outcome" />
            @if($ex->failure_cause)
                <div style="margin-top:.3rem">
                    <span style="background:{{ $causeColors[$ex->failure_cause] ?? '#f1f5f9' }};color:{{ $causeTextColors[$ex->failure_cause] ?? '#475569' }};padding:1px 7px;border-radius:4px;font-size:.72rem;font-weight:600">
                        {{ $causeLabels[$ex->failure_cause] ?? $ex->failure_cause }}
                    </span>
                </div>
            @endif
        </td>
        <td style="font-size:.82rem;color:#64748b">{{ $ex->tester_name ?? '—' }}</td>
        <td style="font-size:.82rem;color:#64748b">{{ $ex->tested_at?->format('d.m.Y') ?? '—' }}</td>
    </tr>
    @endforeach
    </tbody>
</table>
</div>
<div class="pagination">{{ $testExecutions->links() }}</div>

@endsection
