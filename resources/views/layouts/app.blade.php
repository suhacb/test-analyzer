<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <script>window.OllamaModels = { quick: '{{ config("ollama.quick_model") }}', smart: '{{ config("ollama.smart_model") }}' };</script>
    <title>@yield('title', 'Test Analyzer')</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: system-ui, sans-serif; background: #f5f6fa; color: #1a1a2e; }
        nav { background: #1a1a2e; color: #fff; padding: 0 2rem; display: flex; align-items: center; gap: 2rem; height: 56px; }
        nav a { color: #cbd5e1; text-decoration: none; font-size: .9rem; padding: .4rem .6rem; border-radius: 4px; transition: background .15s; }
        nav a:hover, nav a.active { background: #ffffff22; color: #fff; }
        nav .brand { font-weight: 700; font-size: 1.05rem; color: #fff; margin-right: 1rem; }
        nav .badge { background: #ef4444; color: #fff; border-radius: 999px; font-size: .7rem; padding: 1px 6px; margin-left: 4px; vertical-align: middle; }
        .container { max-width: 1200px; margin: 0 auto; padding: 2rem; }
        h1 { font-size: 1.6rem; margin-bottom: 1.5rem; color: #1a1a2e; }
        h2 { font-size: 1.2rem; margin: 1.5rem 0 .75rem; color: #334155; }
        .card { background: #fff; border-radius: 8px; box-shadow: 0 1px 3px #0001; padding: 1.5rem; margin-bottom: 1rem; }
        .stat-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 1rem; margin-bottom: 2rem; }
        .stat-card { background: #fff; border-radius: 8px; padding: 1.25rem 1.5rem; box-shadow: 0 1px 3px #0001; border-left: 4px solid #6366f1; }
        .stat-card .label { font-size: .8rem; color: #64748b; text-transform: uppercase; letter-spacing: .05em; margin-bottom: .4rem; }
        .stat-card .value { font-size: 2rem; font-weight: 700; color: #1a1a2e; line-height: 1; }
        .stat-card .sub { font-size: .8rem; color: #94a3b8; margin-top: .3rem; }
        .stat-card.green { border-color: #22c55e; }
        .stat-card.red { border-color: #ef4444; }
        .stat-card.amber { border-color: #f59e0b; }
        table { width: 100%; border-collapse: collapse; font-size: .9rem; }
        th { background: #f8fafc; text-align: left; padding: .6rem .75rem; border-bottom: 2px solid #e2e8f0; color: #475569; font-size: .8rem; text-transform: uppercase; letter-spacing: .04em; }
        td { padding: .6rem .75rem; border-bottom: 1px solid #f1f5f9; vertical-align: top; }
        tr:hover td { background: #f8fafc; }
        a { color: #6366f1; text-decoration: none; }
        a:hover { text-decoration: underline; }
        .badge-pass       { background: #dcfce7; color: #166534; padding: 2px 8px; border-radius: 4px; font-size: .78rem; font-weight: 600; }
        .badge-soft_pass  { background: #d1fae5; color: #065f46; padding: 2px 8px; border-radius: 4px; font-size: .78rem; font-weight: 600; border: 1px dashed #6ee7b7; }
        .badge-fail       { background: #fee2e2; color: #991b1b; padding: 2px 8px; border-radius: 4px; font-size: .78rem; font-weight: 600; }
        .badge-pending    { background: #fef3c7; color: #92400e; padding: 2px 8px; border-radius: 4px; font-size: .78rem; font-weight: 600; }
        .badge-provider { background: #ede9fe; color: #5b21b6; padding: 2px 8px; border-radius: 4px; font-size: .78rem; }
        .badge-client { background: #dbeafe; color: #1e40af; padding: 2px 8px; border-radius: 4px; font-size: .78rem; }
        .btn { display: inline-block; padding: .4rem .9rem; border-radius: 6px; font-size: .85rem; cursor: pointer; border: none; font-family: inherit; }
        .btn-primary { background: #6366f1; color: #fff; }
        .btn-primary:hover { background: #4f46e5; }
        .btn-success { background: #22c55e; color: #fff; }
        .btn-success:hover { background: #16a34a; }
        .btn-danger  { background: #ef4444; color: #fff; }
        .btn-danger:hover  { background: #dc2626; }
        .btn-sm { padding: .25rem .6rem; font-size: .78rem; }
        /* breadcrumbs */
        .breadcrumb { font-size: .82rem; color: #94a3b8; margin-bottom: 1rem; }
        .breadcrumb a { color: #6366f1; }
        .breadcrumb span { margin: 0 .35rem; }
        /* filters */
        .filters { background: #fff; border-radius: 8px; padding: .9rem 1.25rem; margin-bottom: 1rem; display: flex; gap: .75rem; align-items: flex-end; flex-wrap: wrap; box-shadow: 0 1px 3px #0001; }
        .filters label { font-size: .78rem; color: #64748b; display: block; margin-bottom: .2rem; }
        .filters select, .filters input { padding: .35rem .5rem; border: 1px solid #cbd5e1; border-radius: 4px; font-size: .85rem; font-family: inherit; }
        /* execution card */
        .exec-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-top: 1rem; }
        .exec-card { background: #fff; border-radius: 8px; box-shadow: 0 1px 3px #0001; overflow: hidden; }
        .exec-card-header { padding: .75rem 1.25rem; font-weight: 600; font-size: .9rem; display: flex; justify-content: space-between; align-items: center; }
        .exec-card-header.provider { background: #ede9fe; color: #5b21b6; }
        .exec-card-header.client   { background: #dbeafe; color: #1e40af; }
        .exec-card-header.empty    { background: #f1f5f9; color: #94a3b8; }
        .exec-card-body { padding: 1rem 1.25rem; }
        .exec-field { margin-bottom: .75rem; }
        .exec-field label { font-size: .72rem; text-transform: uppercase; letter-spacing: .05em; color: #94a3b8; display: block; margin-bottom: .15rem; }
        .exec-field p { font-size: .88rem; color: #334155; white-space: pre-wrap; }
        /* scenario content */
        .content-block { background: #f8fafc; border-left: 3px solid #e2e8f0; padding: .75rem 1rem; border-radius: 0 6px 6px 0; margin-bottom: .75rem; font-size: .88rem; white-space: pre-wrap; color: #334155; }
        /* status badge in table */
        .badge-accepted   { background: #dcfce7; color: #166534; padding: 2px 8px; border-radius: 4px; font-size: .78rem; font-weight: 600; }
        .badge-partial    { background: #fef3c7; color: #92400e; padding: 2px 8px; border-radius: 4px; font-size: .78rem; }
        .badge-unstarted  { background: #f1f5f9; color: #64748b; padding: 2px 8px; border-radius: 4px; font-size: .78rem; }
        form.inline { display: inline; }
        select, textarea { font-family: inherit; font-size: .85rem; padding: .35rem .5rem; border: 1px solid #cbd5e1; border-radius: 4px; width: 100%; }
        textarea { resize: vertical; }
        .flash { padding: .75rem 1rem; border-radius: 6px; margin-bottom: 1rem; background: #dcfce7; color: #166534; border: 1px solid #bbf7d0; }
        .pagination { margin-top: 1rem; }
        .raw-text { font-size: .8rem; color: #64748b; white-space: pre-wrap; max-height: 80px; overflow: hidden; }
        .progress-bar-wrap { background: #e2e8f0; border-radius: 999px; height: 8px; overflow: hidden; }
        .progress-bar { height: 8px; border-radius: 999px; background: #22c55e; }
        .section-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: .75rem; }
    </style>
</head>
<body>
<nav>
    <span class="brand">Test Analyzer</span>
    <a href="{{ route('dashboard') }}" class="{{ request()->routeIs('dashboard') ? 'active' : '' }}">Dashboard</a>
    <a href="{{ route('user-stories.index') }}" class="{{ request()->routeIs('user-stories.*') ? 'active' : '' }}">User Stories</a>
    <a href="{{ route('acceptance-criteria.index') }}" class="{{ request()->routeIs('acceptance-criteria.*') ? 'active' : '' }}">AC</a>
    <a href="{{ route('test-scenarios.index') }}" class="{{ request()->routeIs('test-scenarios.*') ? 'active' : '' }}">Scenarios</a>
    <a href="{{ route('test-executions.index') }}" class="{{ request()->routeIs('test-executions.*') ? 'active' : '' }}">Executions</a>
    <a href="{{ route('analytics.index') }}" class="{{ request()->routeIs('analytics.*') ? 'active' : '' }}">Analytics</a>
    <a href="{{ route('review.pending') }}" class="{{ request()->routeIs('review.*') ? 'active' : '' }}">
        Review Queue
        @php $rc = \App\Models\TestExecution::where('outcome','pending')->count()
                 + \App\Models\TestExecution::where('flagged_by_ai', true)->whereNull('ai_flag_dismissed_at')->count()
                 + \Illuminate\Support\Facades\DB::table('failed_jobs')->count(); @endphp
        @if($rc > 0)<span class="badge">{{ $rc }}</span>@endif
    </a>
</nav>
<div class="container">
    @if(session('success'))
        <div class="flash">{{ session('success') }}</div>
    @endif
    @yield('content')
</div>
@stack('chat-context')
@include('components.chat-panel')
</body>
</html>
