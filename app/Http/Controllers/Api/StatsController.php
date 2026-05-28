<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AcceptanceCriteria;
use App\Models\TestExecution;
use App\Models\TestScenario;
use App\Models\UserStory;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class StatsController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $executionsBySide = TestExecution::query()
            ->selectRaw('side, count(*) as total')
            ->groupBy('side')
            ->pluck('total', 'side');

        $executionsByOutcome = TestExecution::query()
            ->selectRaw('outcome, count(*) as total')
            ->groupBy('outcome')
            ->pluck('total', 'outcome');

        $executionsBySideAndOutcome = TestExecution::query()
            ->selectRaw('side, outcome, count(*) as total')
            ->groupBy('side', 'outcome')
            ->get()
            ->groupBy('side')
            ->map(fn($rows) => $rows->pluck('total', 'outcome'));

        // Accepted AC = all its scenarios have pass from both sides
        $acceptedAcIds = DB::table('test_scenarios as ts')
            ->selectRaw('ts.acceptance_criteria_id')
            ->join('test_executions as te', 'te.test_scenario_id', '=', 'ts.id')
            ->whereIn('te.side', ['provider', 'client'])
            ->whereIn('te.outcome', ['pass', 'soft_pass'])
            ->groupBy('ts.id', 'ts.acceptance_criteria_id')
            ->havingRaw('COUNT(DISTINCT te.side) = 2')
            ->pluck('acceptance_criteria_id')
            ->unique();

        $totalAc = AcceptanceCriteria::count();
        $acceptedAc = $acceptedAcIds->count();

        // Accepted US = all its ACs are accepted
        $totalUs = UserStory::count();
        $acceptedUs = UserStory::whereDoesntHave('acceptanceCriteria', function ($q) use ($acceptedAcIds) {
            $q->whereNotIn('id', $acceptedAcIds);
        })->whereHas('acceptanceCriteria')->count();

        return response()->json([
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
                'total'              => TestExecution::count(),
                'by_side'            => $executionsBySide,
                'by_outcome'         => $executionsByOutcome,
                'by_side_and_outcome' => $executionsBySideAndOutcome,
            ],
        ]);
    }
}
