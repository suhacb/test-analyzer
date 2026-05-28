@extends('layouts.app')
@section('title', 'Analytics')
@section('content')

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>

<h1>Analytics</h1>

<form class="filters" method="GET">
    <div>
        <label>Side</label>
        <select name="side" onchange="this.form.submit()">
            <option value="">Both</option>
            <option value="provider" @selected(request('side') === 'provider')>Provider</option>
            <option value="client"   @selected(request('side') === 'client')>Client (UAT)</option>
        </select>
    </div>
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
        <label>From</label>
        <input type="date" name="from" value="{{ request('from') }}" onchange="this.form.submit()">
    </div>
    <div>
        <label>To</label>
        <input type="date" name="to" value="{{ request('to') }}" onchange="this.form.submit()">
    </div>
    <div>
        <label>Granularity</label>
        <select name="granularity" onchange="this.form.submit()">
            <option value="day"   @selected(request('granularity','day') === 'day')>Day</option>
            <option value="week"  @selected(request('granularity','day') === 'week')>Week</option>
            <option value="month" @selected(request('granularity','day') === 'month')>Month</option>
        </select>
    </div>
    @if(request()->hasAny(['side','user_story_id','from','to','granularity']))
        <a href="{{ route('analytics.index') }}" class="btn btn-sm" style="background:#f1f5f9;color:#475569;align-self:flex-end">Clear</a>
    @endif
</form>

{{-- Summary stat cards --}}
@php
    $pass      = $totals['pass']      ?? 0;
    $soft_pass = $totals['soft_pass'] ?? 0;
    $fail      = $totals['fail']      ?? 0;
    $pending   = $totals['pending']   ?? 0;
    $total     = $pass + $soft_pass + $fail + $pending;
    $passRate  = $total ? round(($pass + $soft_pass) / $total * 100) : 0;
@endphp

<div class="stat-grid" style="margin-bottom:1.5rem">
    <div class="stat-card green">
        <div class="label">Pass (hard)</div>
        <div class="value">{{ $pass }}</div>
        <div class="sub">out of {{ $total }}</div>
    </div>
    <div class="stat-card" style="border-color:#6ee7b7">
        <div class="label">Soft pass</div>
        <div class="value">{{ $soft_pass }}</div>
        <div class="sub">with improvement notes</div>
    </div>
    <div class="stat-card red">
        <div class="label">Failed</div>
        <div class="value">{{ $fail }}</div>
        <div class="sub">out of {{ $total }}</div>
    </div>
    <div class="stat-card amber">
        <div class="label">Pending</div>
        <div class="value">{{ $pending }}</div>
        <div class="sub">no outcome recorded</div>
    </div>
    <div class="stat-card {{ $passRate >= 80 ? 'green' : ($passRate >= 50 ? 'amber' : 'red') }}">
        <div class="label">Pass rate</div>
        <div class="value">{{ $passRate }}<span style="font-size:1rem;color:#94a3b8">%</span></div>
        <div class="sub">pass + soft pass</div>
    </div>
    @if($untestedCount > 0)
    <div class="stat-card" style="border-color:#cbd5e1">
        <div class="label">No date recorded</div>
        <div class="value">{{ $untestedCount }}</div>
        <div class="sub">excluded from timeline</div>
    </div>
    @endif
</div>

@if($buckets->isEmpty())
    <div class="card" style="color:#94a3b8;text-align:center;padding:3rem">
        No timeline data for the selected filters.
    </div>
@else

<div class="card" style="margin-bottom:1rem">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1rem">
        <h2 style="margin:0">Executions per period</h2>
        <span style="font-size:.8rem;color:#94a3b8">{{ $buckets->count() }} {{ request('granularity','day') }}(s)</span>
    </div>
    <canvas id="barChart" style="max-height:320px"></canvas>
</div>

<div class="card">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1rem">
        <h2 style="margin:0">Cumulative passes over time</h2>
        <span style="font-size:.8rem;color:#94a3b8">pass + soft pass</span>
    </div>
    <canvas id="lineChart" style="max-height:280px"></canvas>
</div>

<script>
const labels      = @json($buckets->values());
const chartData   = @json($chartData);
const cumulative  = @json($cumulative);

const COLORS = {
    pass:      { bg: 'rgba(34,197,94,.75)',   border: '#16a34a' },
    soft_pass: { bg: 'rgba(110,231,183,.75)', border: '#059669' },
    fail:      { bg: 'rgba(239,68,68,.75)',   border: '#dc2626' },
    pending:   { bg: 'rgba(245,158,11,.75)',  border: '#d97706' },
};
const LABELS = { pass: 'Pass', soft_pass: 'Soft pass', fail: 'Fail', pending: 'Pending' };

// ── Stacked bar chart ──────────────────────────────────────────────────────
new Chart(document.getElementById('barChart'), {
    type: 'bar',
    data: {
        labels,
        datasets: ['pending','fail','soft_pass','pass'].map(key => ({
            label:           LABELS[key],
            data:            chartData.map(d => d[key]),
            backgroundColor: COLORS[key].bg,
            borderColor:     COLORS[key].border,
            borderWidth:     1,
        })),
    },
    options: {
        responsive: true,
        plugins: { legend: { position: 'top' } },
        scales: {
            x: { stacked: true },
            y: { stacked: true, beginAtZero: true, ticks: { stepSize: 1 } },
        },
    },
});

// ── Cumulative line chart ──────────────────────────────────────────────────
new Chart(document.getElementById('lineChart'), {
    type: 'line',
    data: {
        labels,
        datasets: [{
            label:           'Cumulative passes',
            data:            cumulative,
            borderColor:     '#6366f1',
            backgroundColor: 'rgba(99,102,241,.1)',
            fill:            true,
            tension:         0.3,
            pointRadius:     labels.length > 60 ? 0 : 3,
        }],
    },
    options: {
        responsive: true,
        plugins: { legend: { position: 'top' } },
        scales: {
            y: { beginAtZero: true },
        },
    },
});
</script>

@endif

@endsection
