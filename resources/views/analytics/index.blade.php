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

@php use App\Services\AiOutcomeParser; @endphp

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

{{-- AC progress over time --------------------------------------------------}}
@if(!empty($acProgress))
@php
    $acLabels        = collect($acProgress);
    $sideLabel       = request('side') ? ucfirst(request('side')) : 'Both sides';
@endphp
<div class="card" style="margin-top:1rem">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1rem">
        <h2 style="margin:0">Acceptance criteria progress</h2>
        <span style="font-size:.8rem;color:#94a3b8">{{ $sideLabel }} · cumulative state at end of each period</span>
    </div>
    <canvas id="acProgressChart" style="max-height:300px"></canvas>
</div>

<script>
(function () {
    const labels = @json($buckets->values());
    const raw    = @json($acProgress);

    new Chart(document.getElementById('acProgressChart'), {
        type: 'line',
        data: {
            labels,
            datasets: [
                {
                    label:           'Success',
                    data:            raw.map(d => d.success),
                    fill:            true,
                    backgroundColor: 'rgba(34,197,94,.45)',
                    borderColor:     '#16a34a',
                    borderWidth:     1.5,
                    tension:         0.3,
                    pointRadius:     labels.length > 60 ? 0 : 2,
                    order:           4,
                },
                {
                    label:           'Partial success',
                    data:            raw.map(d => d.partial_success),
                    fill:            true,
                    backgroundColor: 'rgba(245,158,11,.45)',
                    borderColor:     '#d97706',
                    borderWidth:     1.5,
                    tension:         0.3,
                    pointRadius:     labels.length > 60 ? 0 : 2,
                    order:           3,
                },
                {
                    label:           'Fail',
                    data:            raw.map(d => d.fail),
                    fill:            true,
                    backgroundColor: 'rgba(239,68,68,.45)',
                    borderColor:     '#dc2626',
                    borderWidth:     1.5,
                    tension:         0.3,
                    pointRadius:     labels.length > 60 ? 0 : 2,
                    order:           2,
                },
                {
                    label:           'Pending',
                    data:            raw.map(d => d.pending),
                    fill:            true,
                    backgroundColor: 'rgba(203,213,225,.45)',
                    borderColor:     '#94a3b8',
                    borderWidth:     1.5,
                    tension:         0.3,
                    pointRadius:     labels.length > 60 ? 0 : 2,
                    order:           1,
                },
            ],
        },
        options: {
            responsive: true,
            interaction: { mode: 'index', intersect: false },
            plugins: { legend: { position: 'top' } },
            scales: {
                x: { stacked: true },
                y: {
                    stacked: true,
                    beginAtZero: true,
                    ticks: { stepSize: 1 },
                    title: { display: true, text: '# ACs', font: { size: 11 } },
                },
            },
        },
    });
}());
</script>
@endif

{{-- Failure cause breakdown ------------------------------------------------}}
@if($failureCauses->isNotEmpty())
@php
    $totalFails = $failureCauses->sum();
    $causeColors = [
        'software_bug'        => 'rgba(239,68,68,.75)',
        'lack_of_data'        => 'rgba(245,158,11,.75)',
        'no_integration_kis'  => 'rgba(139,92,246,.75)',
        'no_integration_euez' => 'rgba(59,130,246,.75)',
        'not_accessible'      => 'rgba(100,116,139,.75)',
        'other'               => 'rgba(203,213,225,.75)',
    ];
@endphp
<div class="card" style="margin-top:1rem">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1rem">
        <h2 style="margin:0">Failure cause breakdown</h2>
        <span style="font-size:.8rem;color:#94a3b8">{{ $totalFails }} failed execution(s)</span>
    </div>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:1.5rem;align-items:center">
        <canvas id="causeChart" style="max-height:260px"></canvas>
        <table>
            <thead>
                <tr><th>Cause</th><th style="text-align:right">Count</th><th style="text-align:right">%</th></tr>
            </thead>
            <tbody>
            @foreach(AiOutcomeParser::CAUSE_LABELS as $key => $label)
                @if(($failureCauses[$key] ?? 0) > 0)
                <tr>
                    <td style="font-size:.85rem">
                        <span style="display:inline-block;width:10px;height:10px;border-radius:50%;background:{{ $causeColors[$key] ?? '#cbd5e1' }};margin-right:.4rem"></span>
                        {{ $label }}
                    </td>
                    <td style="text-align:right;font-weight:600">{{ $failureCauses[$key] }}</td>
                    <td style="text-align:right;color:#94a3b8;font-size:.82rem">{{ round($failureCauses[$key] / $totalFails * 100) }}%</td>
                </tr>
                @endif
            @endforeach
            </tbody>
        </table>
    </div>
</div>

<script>
new Chart(document.getElementById('causeChart'), {
    type: 'doughnut',
    data: {
        labels: @json(AiOutcomeParser::CAUSE_LABELS),
        datasets: [{
            data:            @json(collect(AiOutcomeParser::CAUSES)->map(fn($k) => $failureCauses[$k] ?? 0)->values()),
            backgroundColor: @json(array_values($causeColors)),
            borderWidth: 1,
        }],
    },
    options: {
        responsive: true,
        plugins: {
            legend: { position: 'bottom', labels: { font: { size: 11 } } },
        },
    },
});
</script>
@endif

@endsection
