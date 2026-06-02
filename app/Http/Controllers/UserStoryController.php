<?php

namespace App\Http\Controllers;

use App\Jobs\AnalyzeUserStory;
use App\Models\AcceptanceCriteria;
use App\Models\UserStory;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class UserStoryController extends Controller
{
    public function index(): View
    {
        $userStories = UserStory::withCount('acceptanceCriteria')->orderBy('code')->get();

        $countByUs = function (array $ids): \Illuminate\Support\Collection {
            return AcceptanceCriteria::whereIn('id', $ids)
                ->selectRaw('user_story_id, COUNT(*) as cnt')
                ->groupBy('user_story_id')
                ->pluck('cnt', 'user_story_id');
        };

        $acceptedByUs         = $countByUs(AcceptanceCriteria::acceptedIds()->all());
        $acceptedByUsProvider = $countByUs(AcceptanceCriteria::acceptedIdsBySide('provider')->all());
        $acceptedByUsClient   = $countByUs(AcceptanceCriteria::acceptedIdsBySide('client')->all());

        return view('user-stories.index', compact('userStories', 'acceptedByUs', 'acceptedByUsProvider', 'acceptedByUsClient'));
    }

    public function show(UserStory $userStory): View
    {
        $userStory->load(['acceptanceCriteria.testScenarios.executions']);
        $acceptedAcIds = AcceptanceCriteria::acceptedIds();

        return view('user-stories.show', compact('userStory', 'acceptedAcIds'));
    }

    public function analyse(UserStory $userStory): RedirectResponse
    {
        AnalyzeUserStory::dispatch($userStory);

        return back()->with('success', "AI report queued for {$userStory->code}.");
    }
}
