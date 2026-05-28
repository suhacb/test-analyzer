<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\TestExecutionResource;
use App\Models\TestExecution;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class TestExecutionController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = TestExecution::query();

        if ($request->filled('test_scenario_id')) {
            $query->where('test_scenario_id', $request->integer('test_scenario_id'));
        }

        if ($request->filled('side')) {
            $query->where('side', $request->string('side'));
        }

        if ($request->filled('outcome')) {
            $query->where('outcome', $request->string('outcome'));
        }

        if ($request->boolean('with_scenario')) {
            $query->with('testScenario.acceptanceCriteria.userStory');
        }

        return TestExecutionResource::collection($query->paginate(50));
    }

    public function store(Request $request): TestExecutionResource
    {
        $data = $request->validate([
            'test_scenario_id' => 'required|integer|exists:test_scenarios,id',
            'side'             => 'required|in:provider,client',
            'outcome'          => 'required|in:pass,soft_pass,fail,pending',
            'outcome_raw'      => 'nullable|string',
            'comments'         => 'nullable|string',
            'tester_name'      => 'nullable|string',
            'browser'          => 'nullable|string',
            'tested_at'        => 'nullable|date',
            'source_file'      => 'nullable|string',
        ]);

        return new TestExecutionResource(TestExecution::create($data));
    }

    public function show(TestExecution $testExecution): TestExecutionResource
    {
        $testExecution->load('testScenario.acceptanceCriteria.userStory');

        return new TestExecutionResource($testExecution);
    }

    public function update(Request $request, TestExecution $testExecution): TestExecutionResource
    {
        $data = $request->validate([
            'side'         => 'sometimes|in:provider,client',
            'outcome'      => 'sometimes|in:pass,soft_pass,fail,pending',
            'outcome_raw'  => 'nullable|string',
            'comments'     => 'nullable|string',
            'tester_name'  => 'nullable|string',
            'browser'      => 'nullable|string',
            'tested_at'    => 'nullable|date',
            'review_notes' => 'nullable|string',
        ]);

        if (isset($data['outcome']) && $data['outcome'] !== 'pending') {
            $data['reviewed_at'] = now();
        }

        $testExecution->update($data);

        return new TestExecutionResource($testExecution);
    }

    public function destroy(TestExecution $testExecution): \Illuminate\Http\Response
    {
        $testExecution->delete();

        return response()->noContent();
    }
}
