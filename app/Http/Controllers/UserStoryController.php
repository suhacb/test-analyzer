<?php

namespace App\Http\Controllers;

use App\Models\AcceptanceCriteria;
use App\Models\UserStory;
use Illuminate\View\View;

class UserStoryController extends Controller
{
    public function index(): View
    {
        $userStories   = UserStory::withCount('acceptanceCriteria')->orderBy('code')->get();
        $acceptedAcIds = AcceptanceCriteria::acceptedIds();

        $acceptedByUs = AcceptanceCriteria::whereIn('id', $acceptedAcIds)
            ->selectRaw('user_story_id, COUNT(*) as cnt')
            ->groupBy('user_story_id')
            ->pluck('cnt', 'user_story_id');

        return view('user-stories.index', compact('userStories', 'acceptedByUs'));
    }

    public function show(UserStory $userStory): View
    {
        $userStory->load(['acceptanceCriteria.testScenarios.executions']);
        $acceptedAcIds = AcceptanceCriteria::acceptedIds();

        return view('user-stories.show', compact('userStory', 'acceptedAcIds'));
    }
}
