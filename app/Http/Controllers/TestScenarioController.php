<?php

namespace App\Http\Controllers;

use App\Models\TestScenario;
use App\Models\UserStory;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

class TestScenarioController extends Controller
{
    public function index(Request $request): View
    {
        $query = TestScenario::with(['executions', 'acceptanceCriteria.userStory']);

        if ($request->filled('user_story_id')) {
            $query->whereHas('acceptanceCriteria', fn($q) =>
                $q->where('user_story_id', $request->integer('user_story_id'))
            );
        }

        if ($request->filled('acceptance_criteria_id')) {
            $query->where('acceptance_criteria_id', $request->integer('acceptance_criteria_id'));
        }

        if ($request->filled('outcome')) {
            $query->whereHas('executions', fn($q) =>
                $q->where('outcome', $request->string('outcome'))
            );
        }

        if ($request->boolean('mismatch')) {
            $query
                ->whereHas('executions', fn($q) =>
                    $q->where('side', 'provider')->whereIn('outcome', ['pass', 'soft_pass'])
                )
                ->where(fn($q) =>
                    $q->whereDoesntHave('executions', fn($q2) => $q2->where('side', 'client'))
                      ->orWhereHas('executions', fn($q2) =>
                            $q2->where('side', 'client')->whereNotIn('outcome', ['pass', 'soft_pass'])
                      )
                );
        }

        $testScenarios = $query->orderBy('code')->paginate(50)->withQueryString();
        $userStories   = UserStory::orderBy('code')->get();

        return view('test-scenarios.index', compact('testScenarios', 'userStories'));
    }

    public function export(Request $request): Response
    {
        $query = TestScenario::with(['executions', 'acceptanceCriteria.userStory']);

        if ($request->filled('user_story_id')) {
            $query->whereHas('acceptanceCriteria', fn($q) =>
                $q->where('user_story_id', $request->integer('user_story_id'))
            );
        }

        if ($request->filled('acceptance_criteria_id')) {
            $query->where('acceptance_criteria_id', $request->integer('acceptance_criteria_id'));
        }

        if ($request->filled('outcome')) {
            $query->whereHas('executions', fn($q) =>
                $q->where('outcome', $request->string('outcome'))
            );
        }

        if ($request->boolean('mismatch')) {
            $query
                ->whereHas('executions', fn($q) =>
                    $q->where('side', 'provider')->whereIn('outcome', ['pass', 'soft_pass'])
                )
                ->where(fn($q) =>
                    $q->whereDoesntHave('executions', fn($q2) => $q2->where('side', 'client'))
                      ->orWhereHas('executions', fn($q2) =>
                            $q2->where('side', 'client')->whereNotIn('outcome', ['pass', 'soft_pass'])
                      )
                );
        }

        $rows = $query->orderBy('code')->get();

        $csv  = implode(',', ['Code', 'Title', 'AC', 'User Story', 'Provider outcome', 'Client outcome']) . "\n";
        foreach ($rows as $ts) {
            $bySlide  = $ts->executions->keyBy('side');
            $provider = $bySlide['provider']->outcome ?? '';
            $client   = $bySlide['client']->outcome   ?? '';
            $csv .= implode(',', array_map(
                fn($v) => '"' . str_replace('"', '""', $v) . '"',
                [
                    $ts->code,
                    $ts->title,
                    $ts->acceptanceCriteria->code,
                    $ts->acceptanceCriteria->userStory->code,
                    $provider,
                    $client,
                ]
            )) . "\n";
        }

        $filename = 'test-scenarios-' . now()->format('Ymd-His') . '.csv';

        return response($csv, 200, [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    public function show(TestScenario $testScenario): View
    {
        $testScenario->load(['acceptanceCriteria.userStory', 'executions']);

        return view('test-scenarios.show', compact('testScenario'));
    }
}
