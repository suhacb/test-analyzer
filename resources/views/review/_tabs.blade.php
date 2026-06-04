@php
    $pendingCount = \App\Models\TestExecution::where('outcome', 'pending')->count();
    $flaggedCount = \App\Models\TestExecution::where('flagged_by_ai', true)->whereNull('ai_flag_dismissed_at')->count();
    $blankCount   = \App\Models\TestExecution::blank()->count();
    $failedCount  = \Illuminate\Support\Facades\DB::table('failed_jobs')->count();
@endphp
<div style="display:flex;gap:.25rem;margin-bottom:1.5rem;border-bottom:2px solid #e2e8f0;padding-bottom:0">
    @php
        $tab = fn(string $route, string $label, int $count, string $color = '#64748b') =>
            sprintf(
                '<a href="%s" style="padding:.5rem 1rem;border-radius:6px 6px 0 0;font-size:.88rem;text-decoration:none;border:2px solid transparent;border-bottom:none;margin-bottom:-2px;%s">%s%s</a>',
                route($route),
                request()->routeIs($route) ? 'border-color:#e2e8f0;border-bottom-color:#f5f6fa;background:#f5f6fa;color:#1a1a2e;font-weight:600' : 'color:#64748b',
                e($label),
                $count > 0 ? sprintf(' <span style="background:%s;color:#fff;border-radius:999px;font-size:.7rem;padding:1px 6px;margin-left:4px">%d</span>', e($color), $count) : ''
            );
    @endphp
    {!! $tab('review.pending',          'Pending',       $pendingCount, '#f59e0b') !!}
    {!! $tab('review.blank',            'Blank',         $blankCount,   '#f59e0b') !!}
    {!! $tab('review.flagged',          'AI Flagged',    $flaggedCount, '#ef4444') !!}
    {!! $tab('review.failed-jobs.index','Failed Imports',$failedCount,  '#ef4444') !!}
</div>
