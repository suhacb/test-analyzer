@extends('layouts.app')
@section('title', $testExecution->testScenario->code . ' [' . $testExecution->side . ']')
@section('content')

@php
    $ts = $testExecution->testScenario;
    $ac = $ts->acceptanceCriteria;
    $us = $ac->userStory;
@endphp

<div class="breadcrumb">
    <a href="{{ route('user-stories.index') }}">User Stories</a>
    <span>›</span>
    <a href="{{ route('user-stories.show', $us) }}">{{ $us->code }}</a>
    <span>›</span>
    <a href="{{ route('acceptance-criteria.show', $ac) }}">{{ $ac->code }}</a>
    <span>›</span>
    <a href="{{ route('test-scenarios.show', $ts) }}">{{ $ts->code }}</a>
    <span>›</span>
    {{ $testExecution->side === 'provider' ? 'Provider' : 'Client (UAT)' }}
</div>

<div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:1.5rem">
    <div>
        <h1 style="margin-bottom:.25rem">{{ $ts->code }} — {{ $testExecution->side === 'provider' ? 'Provider' : 'Client (UAT)' }}</h1>
        <p style="color:#64748b">{{ $ts->title }}</p>
    </div>
    <x-outcome :outcome="$testExecution->outcome" />
</div>

<div class="exec-grid" style="grid-template-columns:1fr 1fr;margin-bottom:1rem">
    <div class="card">
        <div style="font-size:.72rem;font-weight:600;text-transform:uppercase;letter-spacing:.06em;color:#94a3b8;margin-bottom:.75rem">Execution details</div>

        <div class="exec-field">
            <label>Outcome (raw)</label>
            <p>{{ $testExecution->outcome_raw ?? '—' }}</p>
        </div>

        @if($testExecution->comments)
        <div class="exec-field">
            <label>Comments</label>
            <div class="content-block">{{ $testExecution->comments }}</div>
        </div>
        @endif

        @if($testExecution->review_notes)
        <div class="exec-field">
            <label>Review notes</label>
            <p style="color:#6366f1">{{ $testExecution->review_notes }}</p>
        </div>
        @endif
    </div>

    <div class="card">
        <div style="font-size:.72rem;font-weight:600;text-transform:uppercase;letter-spacing:.06em;color:#94a3b8;margin-bottom:.75rem">Meta</div>

        <div class="exec-field">
            <label>Side</label>
            <p><span class="badge-{{ $testExecution->side }}">{{ $testExecution->side === 'provider' ? 'Provider' : 'Client (UAT)' }}</span></p>
        </div>
        <div class="exec-field">
            <label>Tester</label>
            <p>{{ $testExecution->tester_name ?? '—' }}</p>
        </div>
        <div class="exec-field">
            <label>Tested</label>
            <p>{{ $testExecution->tested_at?->format('d.m.Y') ?? '—' }}</p>
        </div>
        <div class="exec-field">
            <label>Browser</label>
            <p>{{ $testExecution->browser ?? '—' }}</p>
        </div>
        @if($testExecution->reviewed_at)
        <div class="exec-field">
            <label>Reviewed</label>
            <p>{{ $testExecution->reviewed_at->format('d.m.Y') }}</p>
        </div>
        @endif
        <div class="exec-field">
            <label>Source file</label>
            <p style="font-size:.78rem;color:#94a3b8;word-break:break-all">{{ $testExecution->source_file ?? '—' }}</p>
        </div>
    </div>
</div>

<div class="card" style="padding:0;overflow:hidden">
    <div style="padding:.75rem 1rem;font-size:.72rem;font-weight:600;text-transform:uppercase;letter-spacing:.06em;color:#94a3b8;border-bottom:1px solid #f1f5f9">
        Scenario
    </div>
    <div style="padding:1rem">
        @if($ts->user_role)
        <div class="exec-field">
            <label>User role</label>
            <p>{{ $ts->user_role }}</p>
        </div>
        @endif
        @if($ts->preconditions)
        <div class="exec-field">
            <label>Preconditions</label>
            <div class="content-block">{{ $ts->preconditions }}</div>
        </div>
        @endif
        @if($ts->test_steps)
        <div class="exec-field">
            <label>Test steps</label>
            <div class="content-block">{{ $ts->test_steps }}</div>
        </div>
        @endif
        @if($ts->expected_result)
        <div class="exec-field">
            <label>Expected result</label>
            <div class="content-block">{{ $ts->expected_result }}</div>
        </div>
        @endif
        <div style="margin-top:.75rem">
            <a href="{{ route('test-scenarios.show', $ts) }}" class="btn btn-sm" style="background:#f1f5f9;color:#475569">View full scenario →</a>
        </div>
    </div>
</div>

@endsection
