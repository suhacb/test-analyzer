@extends('layouts.app')
@section('title', $userStory->code)
@section('content')

<div class="breadcrumb">
    <a href="{{ route('user-stories.index') }}">User Stories</a>
    <span>›</span> {{ $userStory->code }}
</div>

<div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:1.5rem">
    <div>
        <h1 style="margin-bottom:.25rem">{{ $userStory->code }}</h1>
        <p style="color:#64748b;font-size:1rem">{{ $userStory->title }}</p>
    </div>
    @php
        $allAccepted = $userStory->acceptanceCriteria->every(fn($ac) => $acceptedAcIds->contains($ac->id));
        $anyAccepted = $userStory->acceptanceCriteria->some(fn($ac) => $acceptedAcIds->contains($ac->id));
    @endphp
    <span class="{{ $allAccepted ? 'badge-accepted' : ($anyAccepted ? 'badge-partial' : 'badge-unstarted') }}" style="font-size:.9rem;padding:4px 12px">
        {{ $allAccepted ? '✓ Accepted' : ($anyAccepted ? 'In progress' : 'Not started') }}
    </span>
</div>

<div class="card" style="padding:0;overflow:hidden">
<table>
    <thead>
        <tr>
            <th style="width:160px">AC Code</th>
            <th>Title</th>
            <th style="width:80px">Scenarios</th>
            <th style="width:110px">Provider</th>
            <th style="width:110px">Client (UAT)</th>
            <th style="width:100px">Status</th>
        </tr>
    </thead>
    <tbody>
    @foreach($userStory->acceptanceCriteria as $ac)
    @php
        $execsByScenario = $ac->testScenarios->mapWithKeys(fn($ts) => [$ts->id => $ts->executions->keyBy('side')]);
        $providerOutcomes = $ac->testScenarios->map(fn($ts) => $execsByScenario[$ts->id]['provider'] ?? null)->filter();
        $clientOutcomes   = $ac->testScenarios->map(fn($ts) => $execsByScenario[$ts->id]['client'] ?? null)->filter();

        $providerFail = $providerOutcomes->contains(fn($e) => $e->outcome === 'fail');
        $providerPass = $providerOutcomes->count() > 0 && $providerOutcomes->every(fn($e) => in_array($e->outcome, ['pass','soft_pass']));
        $clientFail   = $clientOutcomes->contains(fn($e) => $e->outcome === 'fail');
        $clientPass   = $clientOutcomes->count() > 0 && $clientOutcomes->every(fn($e) => in_array($e->outcome, ['pass','soft_pass']));

        $isAccepted = $acceptedAcIds->contains($ac->id);
    @endphp
    <tr>
        <td><a href="{{ route('acceptance-criteria.show', $ac) }}" style="font-weight:600">{{ $ac->code }}</a></td>
        <td><a href="{{ route('acceptance-criteria.show', $ac) }}">{{ $ac->title }}</a></td>
        <td style="text-align:center;color:#64748b">{{ $ac->testScenarios->count() }}</td>
        <td>
            @if($providerFail) <span class="badge-fail">✗ Fail</span>
            @elseif($providerPass) <span class="badge-pass">✓ Pass</span>
            @elseif($providerOutcomes->count()) <span class="badge-pending">~ Partial</span>
            @else <span class="badge-unstarted">—</span>
            @endif
        </td>
        <td>
            @if($clientFail) <span class="badge-fail">✗ Fail</span>
            @elseif($clientPass) <span class="badge-pass">✓ Pass</span>
            @elseif($clientOutcomes->count()) <span class="badge-pending">~ Partial</span>
            @else <span class="badge-unstarted">—</span>
            @endif
        </td>
        <td>
            @if($isAccepted) <span class="badge-accepted">Accepted</span>
            @else <span class="badge-unstarted">Pending</span>
            @endif
        </td>
    </tr>
    @endforeach
    </tbody>
</table>
</div>

@endsection
