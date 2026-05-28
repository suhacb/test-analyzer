<?php

namespace App\Http\Controllers;

use App\Models\TestExecution;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TestExecutionController extends Controller
{
    public function index(Request $request): View
    {
        $query = TestExecution::with(['testScenario.acceptanceCriteria.userStory']);

        if ($request->filled('side'))             $query->where('side', $request->string('side'));
        if ($request->filled('outcome'))          $query->where('outcome', $request->string('outcome'));
        if ($request->filled('test_scenario_id')) $query->where('test_scenario_id', $request->integer('test_scenario_id'));

        $testExecutions = $query->orderBy('id')->paginate(50)->withQueryString();

        return view('test-executions.index', compact('testExecutions'));
    }

    public function show(TestExecution $testExecution): View
    {
        $testExecution->load(['testScenario.acceptanceCriteria.userStory']);

        return view('test-executions.show', compact('testExecution'));
    }
}
