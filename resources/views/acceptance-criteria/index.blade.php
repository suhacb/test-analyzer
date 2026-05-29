@extends('layouts.app')
@section('title', 'Acceptance Criteria')
@section('content')

<h1>Acceptance Criteria</h1>

{{-- KPI cards --}}
<div class="stat-grid">
    <div class="stat-card">
        <div class="label">Total</div>
        <div class="value">{{ $kpis['total'] }}</div>
        <div class="sub">matching filter</div>
    </div>
    <div class="stat-card" style="border-color:#8b5cf6">
        <div class="label">Accepted — provider</div>
        <div class="value" style="color:#7c3aed">{{ $kpis['provider'] }}</div>
        <div class="sub">{{ $kpis['total'] ? round($kpis['provider'] / $kpis['total'] * 100) : 0 }}% of total</div>
    </div>
    <div class="stat-card" style="border-color:#3b82f6">
        <div class="label">Accepted — client</div>
        <div class="value" style="color:#1d4ed8">{{ $kpis['client'] }}</div>
        <div class="sub">{{ $kpis['total'] ? round($kpis['client'] / $kpis['total'] * 100) : 0 }}% of total</div>
    </div>
    <div class="stat-card green">
        <div class="label">Accepted — both</div>
        <div class="value" style="color:#15803d">{{ $kpis['both'] }}</div>
        <div class="sub">{{ $kpis['total'] ? round($kpis['both'] / $kpis['total'] * 100) : 0 }}% of total</div>
    </div>
</div>

{{-- Filters --}}
<form class="filters" method="GET">
    <div>
        <label>User story</label>
        <select name="user_story_id" onchange="this.form.submit()">
            <option value="">All</option>
            @foreach($userStories as $us)
            <option value="{{ $us->id }}" @selected(request('user_story_id') == $us->id)>
                {{ $us->code }} – {{ Str::limit($us->title, 50) }}
            </option>
            @endforeach
        </select>
    </div>
    <div>
        <label>Acceptance status</label>
        <select name="status" onchange="this.form.submit()">
            <option value="">All</option>
            <option value="both"     @selected(request('status') === 'both')>Accepted by both</option>
            <option value="provider" @selected(request('status') === 'provider')>Accepted by provider</option>
            <option value="client"   @selected(request('status') === 'client')>Accepted by client</option>
            <option value="none"     @selected(request('status') === 'none')>Not yet accepted</option>
        </select>
    </div>
    @if(request()->hasAny(['user_story_id', 'status']))
        <a href="{{ route('acceptance-criteria.index') }}" class="btn btn-sm" style="background:#f1f5f9;color:#475569;align-self:flex-end">Clear</a>
    @endif
</form>

{{-- Table --}}
<div class="card" style="padding:0;overflow:hidden">
<table>
    <thead>
        <tr>
            <th style="width:160px">Code</th>
            <th>Title</th>
            <th style="width:120px">User story</th>
            <th style="width:70px;text-align:center">Scenarios</th>
            <th style="width:150px">Status</th>
        </tr>
    </thead>
    <tbody>
    @foreach($acceptanceCriteria as $ac)
    @php
        $byProvider = $providerAcceptedIds->contains($ac->id);
        $byClient   = $clientAcceptedIds->contains($ac->id);
        $byBoth     = $allAcceptedIds->contains($ac->id);
    @endphp
    <tr>
        <td><a href="{{ route('acceptance-criteria.show', $ac) }}" style="font-weight:600">{{ $ac->code }}</a></td>
        <td><a href="{{ route('acceptance-criteria.show', $ac) }}">{{ $ac->title }}</a></td>
        <td>
            <a href="{{ route('user-stories.show', $ac->userStory) }}" style="font-size:.82rem">
                {{ $ac->userStory->code }}
            </a>
        </td>
        <td style="text-align:center;color:#64748b">{{ $ac->test_scenarios_count }}</td>
        <td>
            <div style="display:flex;gap:.25rem;flex-wrap:wrap;align-items:center">
                <span style="font-size:.7rem;padding:1px 6px;border-radius:4px;background:{{ $byProvider ? '#ede9fe' : '#f1f5f9' }};color:{{ $byProvider ? '#5b21b6' : '#94a3b8' }}">
                    P {{ $byProvider ? '✓' : '✗' }}
                </span>
                <span style="font-size:.7rem;padding:1px 6px;border-radius:4px;background:{{ $byClient ? '#dbeafe' : '#f1f5f9' }};color:{{ $byClient ? '#1e40af' : '#94a3b8' }}">
                    C {{ $byClient ? '✓' : '✗' }}
                </span>
                @if($byBoth)
                    <span class="badge-accepted" style="font-size:.7rem">Accepted</span>
                @else
                    <span class="badge-unstarted" style="font-size:.7rem">Pending</span>
                @endif
            </div>
        </td>
    </tr>
    @endforeach
    </tbody>
</table>
</div>
<div class="pagination">{{ $acceptanceCriteria->links() }}</div>

@endsection
