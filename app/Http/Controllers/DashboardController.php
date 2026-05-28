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
        $acceptedAcIds = DB::table('test_scenarios as ts')
            ->selectRaw('ts.acceptance_criteria_id')
            ->join('test_executions as te', 'te.test_scenario_id', '=', 'ts.id')
            ->whereIn('te.side', ['provider', 'client'])
            ->whereIn('te.outcome', ['pass', 'soft_pass'])
            ->groupBy('ts.id', 'ts.acceptance_criteria_id')
            ->havingRaw('COUNT(DISTINCT te.side) = 2')
            ->pluck('acceptance_criteria_id')
            ->unique();

        $totalUs      = UserStory::count();
        $totalAc      = AcceptanceCriteria::count();
        $acceptedAc   = $acceptedAcIds->count();
        $acceptedUs   = UserStory::whereDoesntHave('acceptanceCriteria', fn($q) =>
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
                'total'   => TestExecution::count(),
                'pass'    => TestExecution::where('outcome', 'pass')->count(),
                'fail'    => TestExecution::where('outcome', 'fail')->count(),
                'pending' => TestExecution::where('outcome', 'pending')->count(),
                'provider' => TestExecution::where('side', 'provider')->count(),
                'client'   => TestExecution::where('side', 'client')->count(),
            ],
        ];

        $reviewCount     = TestExecution::where('outcome', 'pending')->count();
        $failedJobsCount = DB::table('failed_jobs')->count();

        return view('dashboard', compact('stats', 'reviewCount', 'failedJobsCount'));
    }
}
