@extends('layouts.app')
@section('title', 'User Stories')
@section('content')

<h1>User Stories</h1>

<div class="card" style="padding:0;overflow:hidden">
<table>
    <thead>
        <tr>
            <th style="width:80px">Code</th>
            <th>Title</th>
            <th style="width:200px">AC progress</th>
            <th style="width:100px">Status</th>
        </tr>
    </thead>
    <tbody>
    @foreach($userStories as $us)
    @php
        $total    = $us->acceptance_criteria_count;
        $accepted = $acceptedByUs[$us->id] ?? 0;
        $pct      = $total ? round($accepted / $total * 100) : 0;
        $statusClass = $accepted === $total && $total > 0 ? 'badge-accepted' : ($accepted > 0 ? 'badge-partial' : 'badge-unstarted');
        $statusLabel = $accepted === $total && $total > 0 ? 'Accepted' : ($accepted > 0 ? "{$accepted}/{$total}" : 'Not started');
    @endphp
    <tr>
        <td><a href="{{ route('user-stories.show', $us) }}" style="font-weight:600">{{ $us->code }}</a></td>
        <td><a href="{{ route('user-stories.show', $us) }}">{{ $us->title }}</a></td>
        <td>
            <div style="display:flex;align-items:center;gap:.6rem">
                <div class="progress-bar-wrap" style="flex:1">
                    <div class="progress-bar" style="width:{{ $pct }}%;background:{{ $pct === 100 ? '#22c55e' : '#6366f1' }}"></div>
                </div>
                <span style="font-size:.78rem;color:#64748b;white-space:nowrap">{{ $accepted }}/{{ $total }}</span>
            </div>
        </td>
        <td><span class="{{ $statusClass }}">{{ $statusLabel }}</span></td>
    </tr>
    @endforeach
    </tbody>
</table>
</div>

@endsection
