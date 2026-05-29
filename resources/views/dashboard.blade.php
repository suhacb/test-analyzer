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
        <div class="label">AC passed (both)</div>
        <div class="value">{{ ($outcomeBreakdown['provider']['pass'] ?? 0) + ($outcomeBreakdown['provider']['soft_pass'] ?? 0) }}</div>
        <div class="sub">provider · {{ ($outcomeBreakdown['client']['pass'] ?? 0) + ($outcomeBreakdown['client']['soft_pass'] ?? 0) }} client</div>
    </div>
    <div class="stat-card red">
        <div class="label">AC failed</div>
        <div class="value">{{ max($outcomeBreakdown['provider']['fail'] ?? 0, $outcomeBreakdown['client']['fail'] ?? 0) }}</div>
        <div class="sub">{{ $outcomeBreakdown['provider']['fail'] ?? 0 }} provider · {{ $outcomeBreakdown['client']['fail'] ?? 0 }} client</div>
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
        <h2>Outcome breakdown <span style="font-size:.75rem;font-weight:400;color:#94a3b8">by acceptance criteria · latest test</span></h2>
        <table>
            <thead>
                <tr>
                    <th>Side</th>
                    <th>Pass</th>
                    <th>Soft pass</th>
                    <th>Fail</th>
                    <th>Pending</th>
                    <th style="border-left:2px solid #e2e8f0;color:#92400e">Client backlog</th>
                </tr>
            </thead>
            <tbody>
            @foreach(['provider' => 'Provider', 'client' => 'Client (UAT)'] as $side => $label)
            <tr>
                <td><span class="badge-{{ $side }}">{{ $label }}</span></td>
                @foreach(['pass', 'soft_pass', 'fail', 'pending'] as $outcome)
                <td><span class="badge-{{ $outcome }}">{{ $outcomeBreakdown[$side][$outcome] ?? 0 }}</span></td>
                @endforeach
                <td style="border-left:2px solid #e2e8f0">
                    @if($side === 'client')
                        @php $bl = $outcomeBreakdown['client_backlog'] ?? 0 @endphp
                        <span style="background:#fef3c7;color:#92400e;padding:2px 8px;border-radius:4px;font-size:.78rem;font-weight:600">
                            {{ $bl }}
                        </span>
                    @else
                        <span style="color:#cbd5e1">—</span>
                    @endif
                </td>
            </tr>
            @endforeach
            </tbody>
        </table>
        <div style="font-size:.73rem;color:#94a3b8;margin-top:.6rem">
            Outcome: worst-case across scenarios (fail › pending › soft pass › pass).
            Client backlog: AC where provider's latest run passes and client either hasn't tested yet or provider has re-run since client's last test.
        </div>
    </div>
</div>

@endsection
