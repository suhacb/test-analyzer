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
        // Compute acceptance sets once — reused for filters, KPIs, and table badges
        $allAcceptedIds      = AcceptanceCriteria::acceptedIds();
        $providerAcceptedIds = AcceptanceCriteria::acceptedIdsBySide('provider');
        $clientAcceptedIds   = AcceptanceCriteria::acceptedIdsBySide('client');

        // Base filter (user story) — applied to both KPIs and the table
        $userStoryFilter = fn ($q) => $q->when(
            $request->filled('user_story_id'),
            fn ($q) => $q->where('user_story_id', $request->integer('user_story_id'))
        );

        // KPIs — count within the filtered base set, ignoring the status filter
        $baseIds = AcceptanceCriteria::tap($userStoryFilter)->pluck('id');

        $kpis = [
            'total'    => $baseIds->count(),
            'provider' => $baseIds->intersect($providerAcceptedIds)->count(),
            'client'   => $baseIds->intersect($clientAcceptedIds)->count(),
            'both'     => $baseIds->intersect($allAcceptedIds)->count(),
        ];

        // Table query — base filter + optional status filter
        $query = AcceptanceCriteria::with('userStory')
            ->withCount('testScenarios')
            ->tap($userStoryFilter);

        match ($request->input('status')) {
            'provider' => $query->whereIn('id', $providerAcceptedIds->all()),
            'client'   => $query->whereIn('id', $clientAcceptedIds->all()),
            'both'     => $query->whereIn('id', $allAcceptedIds->all()),
            'none'     => $query->whereNotIn('id', $allAcceptedIds->all()),
            default    => null,
        };

        $acceptanceCriteria = $query->orderBy('code')->paginate(50)->withQueryString();
        $userStories        = UserStory::orderBy('code')->get();

        return view('acceptance-criteria.index', compact(
            'acceptanceCriteria', 'userStories', 'kpis',
            'allAcceptedIds', 'providerAcceptedIds', 'clientAcceptedIds',
        ));
    }

    public function show(AcceptanceCriteria $acceptanceCriteria): View
    {
        $acceptanceCriteria->load(['userStory', 'testScenarios.executions']);

        return view('acceptance-criteria.show', compact('acceptanceCriteria'));
    }
}
