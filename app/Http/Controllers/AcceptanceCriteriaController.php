<?php

namespace App\Http\Controllers;

use App\Models\AcceptanceCriteria;
use App\Models\UserStory;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AcceptanceCriteriaController extends Controller
{
    public function index(Request $request): View
    {
        $query = AcceptanceCriteria::with('userStory')->withCount('testScenarios');

        if ($request->filled('user_story_id')) {
            $query->where('user_story_id', $request->integer('user_story_id'));
        }

        $acceptanceCriteria = $query->orderBy('code')->paginate(50)->withQueryString();
        $userStories        = UserStory::orderBy('code')->get();
        $acceptedAcIds      = AcceptanceCriteria::acceptedIds();

        return view('acceptance-criteria.index', compact('acceptanceCriteria', 'userStories', 'acceptedAcIds'));
    }

    public function show(AcceptanceCriteria $acceptanceCriteria): View
    {
        $acceptanceCriteria->load(['userStory', 'testScenarios.executions']);

        return view('acceptance-criteria.show', compact('acceptanceCriteria'));
    }
}
