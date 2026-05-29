<?php

namespace App\Http\Controllers;

use App\Models\AcceptanceCriteria;
use App\Models\TestExecution;
use App\Models\TestScenario;
use App\Models\UserStory;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $acceptedAcIds = AcceptanceCriteria::acceptedIds();

        $totalUs    = UserStory::count();
        $totalAc    = AcceptanceCriteria::count();
        $acceptedAc = $acceptedAcIds->count();
        $acceptedUs = UserStory::whereDoesntHave('acceptanceCriteria', fn ($q) =>
            $q->whereNotIn('id', $acceptedAcIds)
        )->whereHas('acceptanceCriteria')->count();

        $stats = [
            'user_stories' => [
                'total'    => $totalUs,
                'accepted' => $acceptedUs,
                'pending'  => $totalUs - $acceptedUs,
            ],
            'acceptance_criteria' => [
                'total'    => $totalAc,
                'accepted' => $acceptedAc,
                'pending'  => $totalAc - $acceptedAc,
            ],
            'test_scenarios' => [
                'total' => TestScenario::count(),
            ],
            'test_executions' => [
                'total'    => TestExecution::count(),
                'provider' => TestExecution::where('side', 'provider')->count(),
                'client'   => TestExecution::where('side', 'client')->count(),
            ],
        ];

        $outcomeBreakdown = $this->acOutcomeBreakdown();

        $reviewCount     = TestExecution::where('outcome', 'pending')->count();
        $failedJobsCount = DB::table('failed_jobs')->count();

        return view('dashboard', compact('stats', 'outcomeBreakdown', 'reviewCount', 'failedJobsCount'));
    }

    private function acOutcomeBreakdown(): array
    {
        // Latest execution per (scenario, side)
        $latestIds = DB::table('test_executions')
            ->selectRaw('MAX(id) as id, test_scenario_id, side')
            ->groupBy('test_scenario_id', 'side');

        // Outcomes of those latest executions, carrying the AC context
        $latestOutcomes = DB::table('test_scenarios as ts')
            ->select('ts.acceptance_criteria_id', 'e.side', 'e.outcome')
            ->joinSub($latestIds, 'l', 'l.test_scenario_id', '=', 'ts.id')
            ->join('test_executions as e', 'e.id', '=', 'l.id')
            ->get();

        // Number of scenarios per AC (to detect partially-untested ACs)
        $scenarioCountByAc = DB::table('test_scenarios')
            ->selectRaw('acceptance_criteria_id, COUNT(*) as cnt')
            ->groupBy('acceptance_criteria_id')
            ->pluck('cnt', 'acceptance_criteria_id');

        $acIds = AcceptanceCriteria::pluck('id');

        $breakdown = [];

        foreach (['provider', 'client'] as $side) {
            // Map: ac_id → array of latest outcome strings for this side
            $byAc = $latestOutcomes
                ->where('side', $side)
                ->groupBy('acceptance_criteria_id')
                ->map(fn ($rows) => $rows->pluck('outcome')->all());

            $counts = ['pass' => 0, 'soft_pass' => 0, 'fail' => 0, 'pending' => 0];

            foreach ($acIds as $acId) {
                $outcomes = $byAc->get($acId, []);
                $expected = (int) ($scenarioCountByAc[$acId] ?? 0);

                // Determine aggregate AC outcome (worst-case wins)
                if ($expected === 0 || count($outcomes) < $expected) {
                    $agg = 'pending';
                } elseif (in_array('fail', $outcomes)) {
                    $agg = 'fail';
                } elseif (in_array('pending', $outcomes)) {
                    $agg = 'pending';
                } elseif (in_array('soft_pass', $outcomes)) {
                    $agg = 'soft_pass';
                } else {
                    $agg = 'pass';
                }

                $counts[$agg]++;
            }

            $breakdown[$side] = $counts;
        }

        // ── Client backlog ────────────────────────────────────────────────────
        // An AC is in the client backlog when, for every scenario:
        //   - The latest provider execution passes (pass / soft_pass)
        //   - AND the latest client execution either does not exist
        //         OR has a lower ID than the latest provider execution
        //         (i.e. provider moved forward since client last ran the test)
        $latestProvider = DB::table('test_executions')
            ->selectRaw('MAX(id) as id, test_scenario_id')
            ->where('side', 'provider')
            ->groupBy('test_scenario_id');

        $latestClient = DB::table('test_executions')
            ->selectRaw('MAX(id) as id, test_scenario_id')
            ->where('side', 'client')
            ->groupBy('test_scenario_id');

        $backlogEligibleByAc = DB::table('test_scenarios as ts')
            ->selectRaw('ts.acceptance_criteria_id, COUNT(*) as cnt')
            ->joinSub($latestProvider, 'lp', 'lp.test_scenario_id', '=', 'ts.id')
            ->join('test_executions as p', fn ($j) => $j
                ->on('p.id', '=', 'lp.id')
                ->whereIn('p.outcome', ['pass', 'soft_pass']))
            ->leftJoinSub($latestClient, 'lc', 'lc.test_scenario_id', '=', 'ts.id')
            ->where(fn ($q) => $q
                ->whereNull('lc.id')
                ->orWhereColumn('lc.id', '<', 'lp.id'))
            ->groupBy('ts.acceptance_criteria_id')
            ->pluck('cnt', 'acceptance_criteria_id');

        $breakdown['client_backlog'] = $acIds->filter(function ($acId) use ($backlogEligibleByAc, $scenarioCountByAc) {
            $expected = (int) ($scenarioCountByAc[$acId] ?? 0);
            return $expected > 0 && ($backlogEligibleByAc[$acId] ?? 0) >= $expected;
        })->count();

        return $breakdown;
    }
}
