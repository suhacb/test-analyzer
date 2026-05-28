@extends('layouts.app')
@section('title', $testScenario->code)
@section('content')

@php
    $ac      = $testScenario->acceptanceCriteria;
    $us      = $ac->userStory;
    $bySlide = $testScenario->executions->keyBy('side');
    $provider = $bySlide['provider'] ?? null;
    $client   = $bySlide['client'] ?? null;
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
            @if($provider->comments)
            <div class="exec-field">
                <label>Comments</label>
                <p>{{ $provider->comments }}</p>
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
            @if($client->comments)
            <div class="exec-field">
                <label>Comments</label>
                <p>{{ $client->comments }}</p>
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

@endsection
