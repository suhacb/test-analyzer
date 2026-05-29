<?php

namespace App\Http\Controllers;

use App\Models\UserStory;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AnalyticsController extends Controller
{
    public function index(Request $request): View
    {
        $side        = $request->input('side');
        $userStoryId = $request->integer('user_story_id') ?: null;
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

        // Summary totals
        $totals        = (clone $base)->selectRaw('te.outcome, COUNT(*) as cnt')->groupBy('te.outcome')->pluck('cnt', 'outcome');
        $untestedCount = (clone $base)->whereNull('te.tested_at')->count();

        // Timeline buckets
        $bucketExpr = match ($granularity) {
            'week'  => "DATE_FORMAT(te.tested_at, '%Y-W%u')",
            'month' => "DATE_FORMAT(te.tested_at, '%Y-%m')",
            default => "DATE(te.tested_at)",
        };

        $rows    = (clone $base)->whereNotNull('te.tested_at')->selectRaw("{$bucketExpr} as bucket, te.outcome, COUNT(*) as cnt")->groupByRaw("{$bucketExpr}, te.outcome")->orderByRaw("{$bucketExpr}")->get();
        $buckets = $rows->pluck('bucket')->unique()->values();

        $pivoted     = [];
        $chartData   = [];
        $cumulative  = [];
        $runningPass = 0;

        foreach ($rows as $row) {
            $pivoted[$row->bucket][$row->outcome] = (int) $row->cnt;
        }

        foreach ($buckets as $b) {
            $pass      = $pivoted[$b]['pass']      ?? 0;
            $soft_pass = $pivoted[$b]['soft_pass'] ?? 0;
            $fail      = $pivoted[$b]['fail']      ?? 0;
            $pending   = $pivoted[$b]['pending']   ?? 0;

            $chartData[]  = compact('pass', 'soft_pass', 'fail', 'pending');
            $runningPass += $pass + $soft_pass;
            $cumulative[] = $runningPass;
        }

        // Failure cause breakdown
        $failureCauses = (clone $base)->where('te.outcome', 'fail')->selectRaw('COALESCE(te.failure_cause, "other") as cause, COUNT(*) as cnt')->groupBy('cause')->orderByDesc('cnt')->pluck('cnt', 'cause');

        // AC progress over time
        $acProgress = $this->buildAcProgress(clone $base, $buckets, $bucketExpr, $side, $userStoryId);

        $userStories = UserStory::orderBy('code')->get();

        return view('analytics.index', compact(
            'chartData', 'cumulative', 'buckets',
            'totals', 'untestedCount', 'userStories',
            'failureCauses', 'acProgress',
        ));
    }

    // ── AC progress ───────────────────────────────────────────────────────────

    /**
     * For each bucket, count ACs by state (pending / partial_success / fail / success).
     * State is determined by the cumulative latest execution per (scenario, side)
     * up to and including that bucket.
     */
    private function buildAcProgress(
        Builder $base,
        Collection $buckets,
        string $bucketExpr,
        ?string $sideFilter,
        ?int $userStoryId,
    ): array {
        if ($buckets->isEmpty()) {
            return [];
        }

        // Scenarios grouped by AC (with optional user-story filter)
        $scenariosPerAc = DB::table('test_scenarios as ts')
            ->join('acceptance_criteria as ac', 'ac.id', '=', 'ts.acceptance_criteria_id')
            ->select('ts.acceptance_criteria_id', 'ts.id as scenario_id')
            ->when($userStoryId, fn ($q) => $q->where('ac.user_story_id', $userStoryId))
            ->get()
            ->groupBy('acceptance_criteria_id')
            ->map(fn ($rows) => $rows->pluck('scenario_id')->all());

        if ($scenariosPerAc->isEmpty()) {
            return [];
        }

        $allAcIds = $scenariosPerAc->keys()->all();

        // All executions within the filtered range, labelled with the bucket they belong to.
        // Side filter is already baked into $base; for 'both' mode $base returns both sides.
        $execs = $base
            ->selectRaw("te.id, te.test_scenario_id, te.side, te.outcome, {$bucketExpr} as bucket")
            ->whereNotNull('te.tested_at')
            ->whereNotIn('te.outcome', ['pending'])
            ->orderBy('te.tested_at')
            ->orderBy('te.id')
            ->get();

        $execsByBucket = $execs->groupBy('bucket');

        // Rolling state: "scenarioId_side" => outcome of latest exec seen so far
        $latestOutcomes = [];
        $latestIds      = [];
        $result         = [];

        foreach ($buckets as $bucket) {
            // Advance state to include executions in this bucket
            foreach ($execsByBucket->get($bucket, collect()) as $exec) {
                $key = $exec->test_scenario_id . '_' . $exec->side;
                if (!isset($latestIds[$key]) || $exec->id > $latestIds[$key]) {
                    $latestOutcomes[$key] = $exec->outcome;
                    $latestIds[$key]      = $exec->id;
                }
            }

            // Snapshot: count ACs in each state at end of this bucket
            $counts = ['pending' => 0, 'success' => 0, 'partial_success' => 0, 'fail' => 0];

            foreach ($allAcIds as $acId) {
                $state = $this->computeAcState(
                    $scenariosPerAc[$acId] ?? [],
                    $latestOutcomes,
                    $sideFilter,
                );
                $counts[$state]++;
            }

            $result[] = $counts;
        }

        return $result;
    }

    /**
     * Determine the aggregate state of one AC given the current latest-outcome map.
     *
     * Priority: fail > partial_success > success > pending
     * Partial if: any scenario/side untested, or any soft_pass present.
     */
    private function computeAcState(array $scenarioIds, array $latestOutcomes, ?string $sideFilter): string
    {
        if (empty($scenarioIds)) {
            return 'pending';
        }

        $sides    = $sideFilter ? [$sideFilter] : ['provider', 'client'];
        $outcomes = [];

        foreach ($scenarioIds as $sid) {
            foreach ($sides as $side) {
                $key = $sid . '_' . $side;
                if (isset($latestOutcomes[$key])) {
                    $outcomes[] = $latestOutcomes[$key];
                }
            }
        }

        $totalExpected = count($scenarioIds) * count($sides);
        $testedCount   = count($outcomes);

        if ($testedCount === 0)                     return 'pending';
        if (in_array('fail', $outcomes, true))      return 'fail';
        if ($testedCount < $totalExpected)          return 'partial_success';
        if (in_array('soft_pass', $outcomes, true)) return 'partial_success';

        return 'success';
    }
}
