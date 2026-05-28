<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\AcceptanceCriteriaResource;
use App\Models\AcceptanceCriteria;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class AcceptanceCriteriaController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = AcceptanceCriteria::query()->withCount('testScenarios');

        if ($request->filled('user_story_id')) {
            $query->where('user_story_id', $request->integer('user_story_id'));
        }

        if ($request->boolean('with_scenarios')) {
            $query->with('testScenarios.executions');
        }

        if ($request->boolean('with_user_story')) {
            $query->with('userStory');
        }

        return AcceptanceCriteriaResource::collection($query->orderBy('code')->paginate(50));
    }

    public function store(Request $request): AcceptanceCriteriaResource
    {
        $data = $request->validate([
            'user_story_id' => 'required|integer|exists:user_stories,id',
            'code'          => 'required|string|unique:acceptance_criteria,code',
            'title'         => 'required|string',
        ]);

        return new AcceptanceCriteriaResource(AcceptanceCriteria::create($data));
    }

    public function show(AcceptanceCriteria $acceptanceCriteria): AcceptanceCriteriaResource
    {
        $acceptanceCriteria->load(['userStory', 'testScenarios.executions']);

        return new AcceptanceCriteriaResource($acceptanceCriteria);
    }

    public function update(Request $request, AcceptanceCriteria $acceptanceCriteria): AcceptanceCriteriaResource
    {
        $data = $request->validate([
            'user_story_id' => 'sometimes|integer|exists:user_stories,id',
            'code'          => 'sometimes|string|unique:acceptance_criteria,code,' . $acceptanceCriteria->id,
            'title'         => 'sometimes|string',
        ]);

        $acceptanceCriteria->update($data);

        return new AcceptanceCriteriaResource($acceptanceCriteria);
    }

    public function destroy(AcceptanceCriteria $acceptanceCriteria): \Illuminate\Http\Response
    {
        $acceptanceCriteria->delete();

        return response()->noContent();
    }
}
