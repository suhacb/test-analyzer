<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class AcceptanceCriteria extends Model
{
    protected $table = 'acceptance_criteria';

    protected $fillable = ['user_story_id', 'code', 'title'];

    public function userStory(): BelongsTo
    {
        return $this->belongsTo(UserStory::class);
    }

    public function testScenarios(): HasMany
    {
        return $this->hasMany(TestScenario::class);
    }

    public static function acceptedIdsBySide(string $side): Collection
    {
        $totalByAc = DB::table('test_scenarios')
            ->selectRaw('acceptance_criteria_id, COUNT(*) as total')
            ->groupBy('acceptance_criteria_id')
            ->pluck('total', 'acceptance_criteria_id');

        if ($totalByAc->isEmpty()) {
            return collect();
        }

        // Latest execution per scenario for this side
        $latest = DB::table('test_executions')
            ->selectRaw('MAX(id) as id, test_scenario_id')
            ->where('side', $side)
            ->groupBy('test_scenario_id');

        $passingByAc = DB::table('test_scenarios as ts')
            ->selectRaw('ts.acceptance_criteria_id, COUNT(DISTINCT ts.id) as cnt')
            ->joinSub($latest, 'l', 'l.test_scenario_id', '=', 'ts.id')
            ->join('test_executions as e', fn ($j) => $j
                ->on('e.id', '=', 'l.id')
                ->whereIn('e.outcome', ['pass', 'soft_pass']))
            ->groupBy('ts.acceptance_criteria_id')
            ->pluck('cnt', 'acceptance_criteria_id');

        return $totalByAc
            ->filter(fn ($total, $acId) => ($passingByAc[$acId] ?? 0) >= $total)
            ->keys();
    }

    public static function acceptedIds(): Collection
    {
        $totalByAc = DB::table('test_scenarios')
            ->selectRaw('acceptance_criteria_id, COUNT(*) as total')
            ->groupBy('acceptance_criteria_id')
            ->pluck('total', 'acceptance_criteria_id');

        if ($totalByAc->isEmpty()) {
            return collect();
        }

        // Latest execution per scenario per side
        $latestProvider = DB::table('test_executions')
            ->selectRaw('MAX(id) as id, test_scenario_id')
            ->where('side', 'provider')
            ->groupBy('test_scenario_id');

        $latestClient = DB::table('test_executions')
            ->selectRaw('MAX(id) as id, test_scenario_id')
            ->where('side', 'client')
            ->groupBy('test_scenario_id');

        $passingByAc = DB::table('test_scenarios as ts')
            ->selectRaw('ts.acceptance_criteria_id, COUNT(DISTINCT ts.id) as cnt')
            ->joinSub($latestProvider, 'lp', 'lp.test_scenario_id', '=', 'ts.id')
            ->join('test_executions as p', fn ($j) => $j
                ->on('p.id', '=', 'lp.id')
                ->whereIn('p.outcome', ['pass', 'soft_pass']))
            ->joinSub($latestClient, 'lc', 'lc.test_scenario_id', '=', 'ts.id')
            ->join('test_executions as c', fn ($j) => $j
                ->on('c.id', '=', 'lc.id')
                ->whereIn('c.outcome', ['pass', 'soft_pass']))
            ->groupBy('ts.acceptance_criteria_id')
            ->pluck('cnt', 'acceptance_criteria_id');

        return $totalByAc
            ->filter(fn ($total, $acId) => ($passingByAc[$acId] ?? 0) >= $total)
            ->keys();
    }

    public function isAccepted(): bool
    {
        $scenarios = $this->testScenarios()->with('executions')->get();

        if ($scenarios->isEmpty()) {
            return false;
        }

        return $scenarios->every(function (TestScenario $scenario) {
            $latest  = $scenario->executions->sortByDesc('id')->unique('side')->keyBy('side');
            $passing = ['pass', 'soft_pass'];

            return isset($latest['provider'], $latest['client'])
                && in_array($latest['provider']->outcome, $passing)
                && in_array($latest['client']->outcome, $passing);
        });
    }
}
