@extends('layouts.app')
@section('title', 'Test Executions')
@section('content')

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
    @if(request()->hasAny(['side','outcome','test_scenario_id']))
        <a href="{{ route('test-executions.index') }}" class="btn btn-sm" style="background:#f1f5f9;color:#475569">Clear</a>
    @endif
</form>

<div class="card" style="padding:0;overflow:hidden">
<table>
    <thead>
        <tr>
            <th style="width:200px">Scenario</th>
            <th>Title</th>
            <th style="width:80px">AC</th>
            <th style="width:80px">US</th>
            <th style="width:110px">Side</th>
            <th style="width:120px">Outcome</th>
            <th style="width:120px">Tester</th>
            <th style="width:100px">Tested</th>
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
        <td><x-outcome :outcome="$ex->outcome" /></td>
        <td style="font-size:.82rem;color:#64748b">{{ $ex->tester_name ?? '—' }}</td>
        <td style="font-size:.82rem;color:#64748b">{{ $ex->tested_at?->format('d.m.Y') ?? '—' }}</td>
    </tr>
    @endforeach
    </tbody>
</table>
</div>
<div class="pagination">{{ $testExecutions->links() }}</div>

@endsection
