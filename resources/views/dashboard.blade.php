@extends('layouts.app')
@section('title', 'Dashboard')
@section('content')

<h1>Project Test Status</h1>

<div class="stat-grid">
    <div class="stat-card {{ $stats['user_stories']['accepted'] === $stats['user_stories']['total'] ? 'green' : 'amber' }}">
        <div class="label">User Stories</div>
        <div class="value">{{ $stats['user_stories']['accepted'] }}<span style="font-size:1rem;color:#94a3b8">/{{ $stats['user_stories']['total'] }}</span></div>
        <div class="sub">accepted</div>
        <div class="progress-bar-wrap" style="margin-top:.6rem">
            <div class="progress-bar" style="width:{{ $stats['user_stories']['total'] ? round($stats['user_stories']['accepted']/$stats['user_stories']['total']*100) : 0 }}%"></div>
        </div>
    </div>
    <div class="stat-card {{ $stats['acceptance_criteria']['pending'] === 0 ? 'green' : 'amber' }}">
        <div class="label">Acceptance Criteria</div>
        <div class="value">{{ $stats['acceptance_criteria']['accepted'] }}<span style="font-size:1rem;color:#94a3b8">/{{ $stats['acceptance_criteria']['total'] }}</span></div>
        <div class="sub">accepted</div>
        <div class="progress-bar-wrap" style="margin-top:.6rem">
            <div class="progress-bar" style="width:{{ $stats['acceptance_criteria']['total'] ? round($stats['acceptance_criteria']['accepted']/$stats['acceptance_criteria']['total']*100) : 0 }}%"></div>
        </div>
    </div>
    <div class="stat-card">
        <div class="label">Test Scenarios</div>
        <div class="value">{{ $stats['test_scenarios']['total'] }}</div>
        <div class="sub">{{ $stats['test_executions']['provider'] }} provider · {{ $stats['test_executions']['client'] }} UAT</div>
    </div>
    <div class="stat-card green">
        <div class="label">Passed</div>
        <div class="value">{{ $stats['test_executions']['pass'] }}</div>
        <div class="sub">executions</div>
    </div>
    <div class="stat-card red">
        <div class="label">Failed</div>
        <div class="value">{{ $stats['test_executions']['fail'] }}</div>
        <div class="sub">executions</div>
    </div>
    @if($reviewCount > 0 || $failedJobsCount > 0)
    <div class="stat-card amber">
        <div class="label">Review Queue</div>
        <div class="value">{{ $reviewCount + $failedJobsCount }}</div>
        <div class="sub">
            @if($reviewCount){{ $reviewCount }} pending @endif
            @if($failedJobsCount)· {{ $failedJobsCount }} failed imports @endif
        </div>
    </div>
    @endif
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem">
    <div class="card">
        <h2>Navigate</h2>
        <ul style="list-style:none;line-height:2.2">
            <li>→ <a href="{{ route('user-stories.index') }}">User Stories</a> — {{ $stats['user_stories']['total'] }} total</li>
            <li>→ <a href="{{ route('acceptance-criteria.index') }}">Acceptance Criteria</a> — {{ $stats['acceptance_criteria']['total'] }} total</li>
            <li>→ <a href="{{ route('test-scenarios.index') }}">Test Scenarios</a> — {{ $stats['test_scenarios']['total'] }} total</li>
            <li>→ <a href="{{ route('test-executions.index') }}">Test Executions</a> — {{ $stats['test_executions']['total'] }} total</li>
            @if($reviewCount + $failedJobsCount > 0)
            <li>→ <a href="{{ route('review.index') }}" style="color:#f59e0b;font-weight:600">⚠ Review Queue</a> — {{ $reviewCount + $failedJobsCount }} items need attention</li>
            @endif
        </ul>
    </div>
    <div class="card">
        <h2>Outcome breakdown</h2>
        <table>
            <tr><th>Side</th><th>Pass</th><th>Soft pass</th><th>Fail</th><th>Pending</th></tr>
            @foreach(['provider','client'] as $side)
            <tr>
                <td><span class="badge-{{ $side }}">{{ $side === 'client' ? 'client (UAT)' : $side }}</span></td>
                @foreach(['pass','soft_pass','fail','pending'] as $outcome)
                <td><span class="badge-{{ $outcome }}">{{ \App\Models\TestExecution::where('side',$side)->where('outcome',$outcome)->count() }}</span></td>
                @endforeach
            </tr>
            @endforeach
        </table>
    </div>
</div>

@endsection
