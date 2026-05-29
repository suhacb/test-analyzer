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
            <th style="width:240px">AC progress</th>
            <th style="width:100px">Status</th>
        </tr>
    </thead>
    <tbody>
    @foreach($userStories as $us)
    @php
        $total    = $us->acceptance_criteria_count;
        $accepted = $acceptedByUs[$us->id] ?? 0;
        $acceptedP = $acceptedByUsProvider[$us->id] ?? 0;
        $acceptedC = $acceptedByUsClient[$us->id] ?? 0;
        $pct  = $total ? round($accepted  / $total * 100) : 0;
        $pctP = $total ? round($acceptedP / $total * 100) : 0;
        $pctC = $total ? round($acceptedC / $total * 100) : 0;
        $statusClass = $accepted === $total && $total > 0 ? 'badge-accepted' : ($accepted > 0 ? 'badge-partial' : 'badge-unstarted');
        $statusLabel = $accepted === $total && $total > 0 ? 'Accepted' : ($accepted > 0 ? "{$accepted}/{$total}" : 'Not started');
    @endphp
    <tr>
        <td><a href="{{ route('user-stories.show', $us) }}" style="font-weight:600">{{ $us->code }}</a></td>
        <td><a href="{{ route('user-stories.show', $us) }}">{{ $us->title }}</a></td>
        <td>
            @php
                $bar = fn(string $label, string $bg, string $labelBg, string $labelColor, int $p, int $done, int $tot) =>
                    '<div style="display:flex;align-items:center;gap:.4rem">'
                    . '<span style="font-size:.65rem;padding:1px 5px;min-width:1.9rem;text-align:center;border-radius:4px;background:' . $labelBg . ';color:' . $labelColor . '">' . e($label) . '</span>'
                    . '<div class="progress-bar-wrap" style="flex:1"><div class="progress-bar" style="width:' . $p . '%;background:' . $bg . '"></div></div>'
                    . '<span style="font-size:.72rem;color:#64748b;white-space:nowrap">' . $done . '/' . $tot . '</span>'
                    . '</div>';
            @endphp
            <div style="display:flex;flex-direction:column;gap:.3rem">
                {!! $bar('P', '#8b5cf6', '#ede9fe', '#5b21b6', $pctP, $acceptedP, $total) !!}
                {!! $bar('C', '#3b82f6', '#dbeafe', '#1e40af', $pctC, $acceptedC, $total) !!}
                {!! $bar('✓', $pct === 100 ? '#22c55e' : '#6366f1', '#f0fdf4', '#166534', $pct, $accepted, $total) !!}
            </div>
        </td>
        <td><span class="{{ $statusClass }}">{{ $statusLabel }}</span></td>
    </tr>
    @endforeach
    </tbody>
</table>
</div>

@endsection
