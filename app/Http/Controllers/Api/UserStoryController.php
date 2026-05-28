<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserStoryResource;
use App\Models\UserStory;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class UserStoryController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = UserStory::query()->withCount('acceptanceCriteria');

        if ($request->boolean('with_ac')) {
            $query->with('acceptanceCriteria');
        }

        return UserStoryResource::collection($query->orderBy('code')->paginate(50));
    }

    public function store(Request $request): UserStoryResource
    {
        $data = $request->validate([
            'code'  => 'required|string|unique:user_stories,code',
            'title' => 'required|string',
        ]);

        return new UserStoryResource(UserStory::create($data));
    }

    public function show(UserStory $userStory): UserStoryResource
    {
        $userStory->load(['acceptanceCriteria.testScenarios.executions']);

        return new UserStoryResource($userStory);
    }

    public function update(Request $request, UserStory $userStory): UserStoryResource
    {
        $data = $request->validate([
            'code'  => 'sometimes|string|unique:user_stories,code,' . $userStory->id,
            'title' => 'sometimes|string',
        ]);

        $userStory->update($data);

        return new UserStoryResource($userStory);
    }

    public function destroy(UserStory $userStory): \Illuminate\Http\Response
    {
        $userStory->delete();

        return response()->noContent();
    }
}
