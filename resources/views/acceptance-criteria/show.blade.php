@extends('layouts.app')
@section('title', $acceptanceCriteria->code)
@push('chat-context')
<script>window.ChatContext = { type: 'acceptance_criteria', id: {{ $acceptanceCriteria->id }}, label: '{{ addslashes($acceptanceCriteria->code) }} — {{ addslashes($acceptanceCriteria->title) }}' };</script>
@endpush
@section('content')

<div class="breadcrumb">
    <a href="{{ route('user-stories.index') }}">User Stories</a>
    <span>›</span>
    <a href="{{ route('user-stories.show', $acceptanceCriteria->userStory) }}">{{ $acceptanceCriteria->userStory->code }}</a>
    <span>›</span>
    {{ $acceptanceCriteria->code }}
</div>

<div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:1.5rem">
    <div>
        <h1 style="margin-bottom:.25rem">{{ $acceptanceCriteria->code }}</h1>
        <p style="color:#64748b">{{ $acceptanceCriteria->title }}</p>
    </div>
    <div style="display:flex;gap:.6rem;align-items:center">
        @if($acceptanceCriteria->isAccepted())
            <span class="badge-accepted" style="font-size:.9rem;padding:4px 12px">✓ Accepted</span>
        @else
            <span class="badge-unstarted" style="font-size:.9rem;padding:4px 12px">Pending</span>
        @endif
        <form method="POST" action="{{ route('acceptance-criteria.analyse', $acceptanceCriteria) }}">
            @csrf
            <button type="submit" class="btn btn-sm" style="background:#6366f1;color:#fff">
                {{ $acceptanceCriteria->ai_summary ? 'Re-analyse' : 'Analyse with AI' }}
            </button>
        </form>
    </div>
</div>

{{-- AI summary --}}
@if($acceptanceCriteria->ai_summary)
<div class="card" style="margin-bottom:1.25rem;border-left:4px solid #6366f1">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:.75rem">
        <h2 style="margin:0;font-size:1rem">AI Analysis</h2>
        <span style="font-size:.75rem;color:#94a3b8">
            Generated {{ $acceptanceCriteria->ai_summary_generated_at?->format('d.m.Y H:i') }}
        </span>
    </div>
    <div style="font-size:.9rem;color:#334155;white-space:pre-wrap;line-height:1.65">{{ $acceptanceCriteria->ai_summary }}</div>
</div>
@endif

<div class="card" style="padding:0;overflow:hidden">
<table>
    <thead>
        <tr>
            <th style="width:200px">Scenario</th>
            <th>Title</th>
            <th style="width:140px">Provider</th>
            <th style="width:140px">Client (UAT)</th>
        </tr>
    </thead>
    <tbody>
    @foreach($acceptanceCriteria->testScenarios as $ts)
    @php $latest = $ts->executions->sortByDesc('id')->unique('side')->keyBy('side'); @endphp
    <tr>
        <td><a href="{{ route('test-scenarios.show', $ts) }}" style="font-weight:600;font-size:.85rem">{{ $ts->code }}</a></td>
        <td><a href="{{ route('test-scenarios.show', $ts) }}">{{ $ts->title }}</a></td>
        <td>
            @if(isset($latest['provider']))
                <x-outcome :outcome="$latest['provider']->outcome" />
                @if($latest['provider']->reviewed_at)
                    <span style="font-size:.7rem;color:#94a3b8;display:block">reviewed</span>
                @endif
            @else
                <span class="badge-unstarted">—</span>
            @endif
        </td>
        <td>
            @if(isset($latest['client']))
                <x-outcome :outcome="$latest['client']->outcome" />
            @else
                <span class="badge-unstarted">—</span>
            @endif
        </td>
    </tr>
    @endforeach
    </tbody>
</table>
</div>

@endsection
