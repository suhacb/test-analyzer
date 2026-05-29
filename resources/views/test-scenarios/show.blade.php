@extends('layouts.app')
@section('title', $testScenario->code)
@section('content')

@php
    $ac       = $testScenario->acceptanceCriteria;
    $us       = $ac->userStory;
    $latest   = $testScenario->executions->sortByDesc('id')->unique('side')->keyBy('side');
    $provider = $latest['provider'] ?? null;
    $client   = $latest['client']   ?? null;
    $history  = $testScenario->executions->sortByDesc('id');
@endphp

<div class="breadcrumb">
    <a href="{{ route('user-stories.index') }}">User Stories</a>
    <span>›</span>
    <a href="{{ route('user-stories.show', $us) }}">{{ $us->code }}</a>
    <span>›</span>
    <a href="{{ route('acceptance-criteria.show', $ac) }}">{{ $ac->code }}</a>
    <span>›</span>
    {{ $testScenario->code }}
</div>

<h1 style="margin-bottom:.25rem">{{ $testScenario->code }}</h1>
<p style="color:#64748b;margin-bottom:1.5rem">{{ $testScenario->title }}</p>

<div class="card" style="margin-bottom:1rem">
    @if($testScenario->user_role)
    <div class="exec-field">
        <label>User role</label>
        <p>{{ $testScenario->user_role }}</p>
    </div>
    @endif
    @if($testScenario->preconditions)
    <div class="exec-field">
        <label>Preconditions</label>
        <div class="content-block">{{ $testScenario->preconditions }}</div>
    </div>
    @endif
    @if($testScenario->test_steps)
    <div class="exec-field">
        <label>Test steps</label>
        <div class="content-block">{{ $testScenario->test_steps }}</div>
    </div>
    @endif
    @if($testScenario->expected_result)
    <div class="exec-field">
        <label>Expected result</label>
        <div class="content-block">{{ $testScenario->expected_result }}</div>
    </div>
    @endif
</div>

<h2 style="margin-bottom:.75rem">Executions</h2>
<div class="exec-grid">

    {{-- Provider --}}
    <div class="exec-card">
        <div class="exec-card-header {{ $provider ? 'provider' : 'empty' }}">
            <span>Provider</span>
            @if($provider) <x-outcome :outcome="$provider->outcome" /> @endif
        </div>
        @if($provider)
        <div class="exec-card-body">
            <div class="exec-field">
                <label>Outcome (raw)</label>
                <p>{{ $provider->outcome_raw ?? '—' }}</p>
            </div>
            @if($provider->outcome_comment || $provider->comments)
            <div class="exec-field">
                <label>Comments</label>
                @if($provider->outcome_comment)
                    <p>{{ $provider->outcome_comment }}</p>
                @endif
                @if($provider->comments)
                    <p style="{{ $provider->outcome_comment ? 'margin-top:.35rem;color:#475569' : '' }}">{{ $provider->comments }}</p>
                @endif
            </div>
            @endif
            @if($provider->failure_cause)
            <div class="exec-field">
                <label>Failure cause</label>
                <span style="background:#fee2e2;color:#991b1b;padding:2px 8px;border-radius:4px;font-size:.78rem;font-weight:600">
                    {{ \App\Services\AiOutcomeParser::CAUSE_LABELS[$provider->failure_cause] ?? $provider->failure_cause }}
                </span>
            </div>
            @endif
            @if($provider->review_notes)
            <div class="exec-field">
                <label>Review notes</label>
                <p style="color:#6366f1">{{ $provider->review_notes }}</p>
            </div>
            @endif
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:.5rem;margin-top:.5rem;font-size:.82rem;color:#64748b">
                <div><strong>Tester:</strong> {{ $provider->tester_name ?? '—' }}</div>
                <div><strong>Tested:</strong> {{ $provider->tested_at?->format('d.m.Y') ?? '—' }}</div>
                <div><strong>Browser:</strong> {{ $provider->browser ?? '—' }}</div>
                @if($provider->reviewed_at)
                <div><strong>Reviewed:</strong> {{ $provider->reviewed_at->format('d.m.Y') }}</div>
                @endif
            </div>
        </div>
        @else
        <div class="exec-card-body" style="color:#94a3b8;font-size:.88rem">No provider execution yet.</div>
        @endif
    </div>

    {{-- Client / UAT --}}
    <div class="exec-card">
        <div class="exec-card-header {{ $client ? 'client' : 'empty' }}">
            <span>Client (UAT)</span>
            @if($client) <x-outcome :outcome="$client->outcome" /> @endif
        </div>
        @if($client)
        <div class="exec-card-body">
            <div class="exec-field">
                <label>Outcome (raw)</label>
                <p>{{ $client->outcome_raw ?? '—' }}</p>
            </div>
            @if($client->outcome_comment || $client->comments)
            <div class="exec-field">
                <label>Comments</label>
                @if($client->outcome_comment)
                    <p>{{ $client->outcome_comment }}</p>
                @endif
                @if($client->comments)
                    <p style="{{ $client->outcome_comment ? 'margin-top:.35rem;color:#475569' : '' }}">{{ $client->comments }}</p>
                @endif
            </div>
            @endif
            @if($client->failure_cause)
            <div class="exec-field">
                <label>Failure cause</label>
                <span style="background:#fee2e2;color:#991b1b;padding:2px 8px;border-radius:4px;font-size:.78rem;font-weight:600">
                    {{ \App\Services\AiOutcomeParser::CAUSE_LABELS[$client->failure_cause] ?? $client->failure_cause }}
                </span>
            </div>
            @endif
            @if($client->review_notes)
            <div class="exec-field">
                <label>Review notes</label>
                <p style="color:#6366f1">{{ $client->review_notes }}</p>
            </div>
            @endif
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:.5rem;margin-top:.5rem;font-size:.82rem;color:#64748b">
                <div><strong>Tester:</strong> {{ $client->tester_name ?? '—' }}</div>
                <div><strong>Tested:</strong> {{ $client->tested_at?->format('d.m.Y') ?? '—' }}</div>
                <div><strong>Browser:</strong> {{ $client->browser ?? '—' }}</div>
                @if($client->reviewed_at)
                <div><strong>Reviewed:</strong> {{ $client->reviewed_at->format('d.m.Y') }}</div>
                @endif
            </div>
        </div>
        @else
        <div class="exec-card-body" style="color:#94a3b8;font-size:.88rem">No UAT execution yet.</div>
        @endif
    </div>
</div>


@if($history->count() > 2)
<h2 style="margin:1.5rem 0 .75rem">Execution history ({{ $history->count() }} total)</h2>
<div class="card" style="padding:0;overflow:hidden">
<table>
    <thead>
        <tr>
            <th style="width:90px">Side</th>
            <th style="width:110px">Outcome</th>
            <th style="width:110px">Date</th>
            <th>Tester</th>
            <th>Comments</th>
            <th style="width:80px">Review</th>
        </tr>
    </thead>
    <tbody>
    @foreach($history as $ex)
    <tr style="{{ $ex->id === ($latest[$ex->side]->id ?? null) ? 'background:#f8fafc;font-weight:500' : '' }}">
        <td><span class="badge-{{ $ex->side }}">{{ $ex->side }}</span></td>
        <td><x-outcome :outcome="$ex->outcome" /></td>
        <td style="font-size:.82rem;color:#64748b;white-space:nowrap">{{ $ex->tested_at?->format('d.m.Y') ?? '—' }}</td>
        <td style="font-size:.82rem">{{ $ex->tester_name ?? '—' }}</td>
        <td style="font-size:.82rem;color:#475569">{{ $ex->comments ?? '—' }}</td>
        <td style="font-size:.75rem;color:#94a3b8">
            @if($ex->reviewed_at)
                ✓ {{ $ex->reviewed_at->format('d.m.Y') }}
            @else
                —
            @endif
        </td>
    </tr>
    @endforeach
    </tbody>
</table>
</div>
@endif

@endsection
