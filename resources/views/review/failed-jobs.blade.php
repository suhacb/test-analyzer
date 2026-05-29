@extends('layouts.app')
@section('title', 'Review — Failed Imports')
@section('content')

<h1>Review Queue</h1>
@include('review._tabs')

<div class="section-header">
    <h2>Failed import jobs <span class="badge-fail" style="font-size:.85rem">{{ $failedJobs->total() }}</span></h2>
</div>

@if($failedJobs->isEmpty())
    <div class="card" style="color:#64748b">No failed import jobs.</div>
@else
<div class="card" style="padding:0;overflow:hidden">
<table>
    <thead>
        <tr>
            <th>File</th>
            <th>Side</th>
            <th>Error</th>
            <th>Failed at</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
    @foreach($failedJobs as $job)
    <tr>
        <td style="font-size:.82rem;word-break:break-all">{{ $job->parsed['file'] }}</td>
        <td><span class="badge-{{ $job->parsed['side'] }}">{{ $job->parsed['side'] }}</span></td>
        <td style="font-size:.78rem;color:#64748b;max-width:300px">{{ $job->short_exception }}</td>
        <td style="font-size:.8rem;white-space:nowrap">{{ $job->failed_at }}</td>
        <td style="white-space:nowrap">
            <form class="inline" method="POST" action="{{ route('review.failed-jobs.retry', $job->uuid) }}">
                @csrf
                <button class="btn btn-success btn-sm">Retry</button>
            </form>
            <form class="inline" method="POST" action="{{ route('review.failed-jobs.dismiss', $job->uuid) }}"
                  onsubmit="return confirm('Dismiss this job?')">
                @csrf @method('DELETE')
                <button class="btn btn-danger btn-sm">Dismiss</button>
            </form>
        </td>
    </tr>
    @endforeach
    </tbody>
</table>
</div>
<div class="pagination">{{ $failedJobs->links() }}</div>
@endif

@endsection
