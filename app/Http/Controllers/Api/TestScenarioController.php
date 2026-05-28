<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\TestScenarioResource;
use App\Models\TestScenario;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class TestScenarioController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = TestScenario::query()->withCount('executions')->with('executions');

        if ($request->filled('acceptance_criteria_id')) {
            $query->where('acceptance_criteria_id', $request->integer('acceptance_criteria_id'));
        }

        if ($request->boolean('with_ac')) {
            $query->with('acceptanceCriteria.userStory');
        }

        return TestScenarioResource::collection($query->orderBy('code')->paginate(50));
    }

    public function store(Request $request): TestScenarioResource
    {
        $data = $request->validate([
            'acceptance_criteria_id' => 'required|integer|exists:acceptance_criteria,id',
            'code'                   => 'required|string|unique:test_scenarios,code',
            'title'                  => 'required|string',
            'user_role'              => 'nullable|string',
            'preconditions'          => 'nullable|string',
            'test_steps'             => 'nullable|string',
            'expected_result'        => 'nullable|string',
        ]);

        return new TestScenarioResource(TestScenario::create($data));
    }

    public function show(TestScenario $testScenario): TestScenarioResource
    {
        $testScenario->load(['acceptanceCriteria.userStory', 'executions']);

        return new TestScenarioResource($testScenario);
    }

    public function update(Request $request, TestScenario $testScenario): TestScenarioResource
    {
        $data = $request->validate([
            'acceptance_criteria_id' => 'sometimes|integer|exists:acceptance_criteria,id',
            'code'                   => 'sometimes|string|unique:test_scenarios,code,' . $testScenario->id,
            'title'                  => 'sometimes|string',
            'user_role'              => 'nullable|string',
            'preconditions'          => 'nullable|string',
            'test_steps'             => 'nullable|string',
            'expected_result'        => 'nullable|string',
        ]);

        $testScenario->update($data);

        return new TestScenarioResource($testScenario->fresh('executions'));
    }

    public function destroy(TestScenario $testScenario): \Illuminate\Http\Response
    {
        $testScenario->delete();

        return response()->noContent();
    }
}
