<?php

namespace App\Http\Controllers;

use App\Models\UserStory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AnalyticsController extends Controller
{
    public function index(Request $request): View
    {
        $side        = $request->input('side');
        $userStoryId = $request->input('user_story_id');
        $from        = $request->input('from');
        $to          = $request->input('to');
        $granularity = $request->input('granularity', 'day');

        $base = DB::table('test_executions as te')
            ->join('test_scenarios as ts', 'ts.id', '=', 'te.test_scenario_id')
            ->join('acceptance_criteria as ac', 'ac.id', '=', 'ts.acceptance_criteria_id');

        if ($side)        $base->where('te.side', $side);
        if ($userStoryId) $base->where('ac.user_story_id', $userStoryId);
        if ($from)        $base->whereDate('te.tested_at', '>=', $from);
        if ($to)          $base->whereDate('te.tested_at', '<=', $to);

        // Summary totals (all records, including untested)
        $totals = (clone $base)
            ->selectRaw('te.outcome, COUNT(*) as cnt')
            ->groupBy('te.outcome')
            ->pluck('cnt', 'outcome');

        $untestedCount = (clone $base)->whereNull('te.tested_at')->count();

        // Timeline: only records with a tested_at date
        $bucketExpr = match($granularity) {
            'week'  => "DATE_FORMAT(te.tested_at, '%Y-W%u')",
            'month' => "DATE_FORMAT(te.tested_at, '%Y-%m')",
            default => "DATE(te.tested_at)",
        };

        $rows = (clone $base)
            ->whereNotNull('te.tested_at')
            ->selectRaw("{$bucketExpr} as bucket, te.outcome, COUNT(*) as cnt")
            ->groupByRaw("{$bucketExpr}, te.outcome")
            ->orderByRaw("{$bucketExpr}")
            ->get();

        $buckets = $rows->pluck('bucket')->unique()->values();

        $pivoted = [];
        foreach ($rows as $row) {
            $pivoted[$row->bucket][$row->outcome] = (int) $row->cnt;
        }

        $chartData   = [];
        $cumulative  = [];
        $runningPass = 0;

        foreach ($buckets as $b) {
            $pass      = $pivoted[$b]['pass']      ?? 0;
            $soft_pass = $pivoted[$b]['soft_pass'] ?? 0;
            $fail      = $pivoted[$b]['fail']      ?? 0;
            $pending   = $pivoted[$b]['pending']   ?? 0;

            $chartData[] = compact('pass', 'soft_pass', 'fail', 'pending');

            $runningPass  += $pass + $soft_pass;
            $cumulative[]  = $runningPass;
        }

        $userStories = UserStory::orderBy('code')->get();

        return view('analytics.index', compact(
            'chartData', 'cumulative', 'buckets',
            'totals', 'untestedCount', 'userStories'
        ));
    }
}
