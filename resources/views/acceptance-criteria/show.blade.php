@extends('layouts.app')
@section('title', $acceptanceCriteria->code)
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
    @if($acceptanceCriteria->isAccepted())
        <span class="badge-accepted" style="font-size:.9rem;padding:4px 12px">✓ Accepted</span>
    @else
        <span class="badge-unstarted" style="font-size:.9rem;padding:4px 12px">Pending</span>
    @endif
</div>

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
    @php $bySlide = $ts->executions->keyBy('side'); @endphp
    <tr>
        <td><a href="{{ route('test-scenarios.show', $ts) }}" style="font-weight:600;font-size:.85rem">{{ $ts->code }}</a></td>
        <td><a href="{{ route('test-scenarios.show', $ts) }}">{{ $ts->title }}</a></td>
        <td>
            @if(isset($bySlide['provider']))
                <x-outcome :outcome="$bySlide['provider']->outcome" />
                @if($bySlide['provider']->reviewed_at)
                    <span style="font-size:.7rem;color:#94a3b8;display:block">reviewed</span>
                @endif
            @else
                <span class="badge-unstarted">—</span>
            @endif
        </td>
        <td>
            @if(isset($bySlide['client']))
                <x-outcome :outcome="$bySlide['client']->outcome" />
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
