@extends('layouts.app')
@section('title', 'Acceptance Criteria')
@section('content')

<h1>Acceptance Criteria</h1>

<form class="filters" method="GET">
    <div>
        <label>User Story</label>
        <select name="user_story_id" onchange="this.form.submit()">
            <option value="">All user stories</option>
            @foreach($userStories as $us)
            <option value="{{ $us->id }}" @selected(request('user_story_id') == $us->id)>
                {{ $us->code }} – {{ Str::limit($us->title, 50) }}
            </option>
            @endforeach
        </select>
    </div>
    @if(request()->hasAny(['user_story_id']))
        <a href="{{ route('acceptance-criteria.index') }}" class="btn btn-sm" style="background:#f1f5f9;color:#475569">Clear</a>
    @endif
</form>

<div class="card" style="padding:0;overflow:hidden">
<table>
    <thead>
        <tr>
            <th style="width:160px">Code</th>
            <th>Title</th>
            <th style="width:120px">User Story</th>
            <th style="width:80px">Scenarios</th>
            <th style="width:100px">Status</th>
        </tr>
    </thead>
    <tbody>
    @foreach($acceptanceCriteria as $ac)
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
            @if($acceptedAcIds->contains($ac->id))
                <span class="badge-accepted">Accepted</span>
            @else
                <span class="badge-unstarted">Pending</span>
            @endif
        </td>
    </tr>
    @endforeach
    </tbody>
</table>
</div>
<div class="pagination">{{ $acceptanceCriteria->links() }}</div>

@endsection
