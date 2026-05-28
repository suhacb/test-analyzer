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

    public static function acceptedIds(): Collection
    {
        $totalByAc = DB::table('test_scenarios')
            ->selectRaw('acceptance_criteria_id, COUNT(*) as total')
            ->groupBy('acceptance_criteria_id')
            ->pluck('total', 'acceptance_criteria_id');

        if ($totalByAc->isEmpty()) {
            return collect();
        }

        $passingByAc = DB::table('test_scenarios as ts')
            ->selectRaw('ts.acceptance_criteria_id, COUNT(DISTINCT ts.id) as cnt')
            ->join('test_executions as p', fn($j) => $j
                ->on('p.test_scenario_id', '=', 'ts.id')
                ->where('p.side', 'provider')
                ->whereIn('p.outcome', ['pass', 'soft_pass']))
            ->join('test_executions as c', fn($j) => $j
                ->on('c.test_scenario_id', '=', 'ts.id')
                ->where('c.side', 'client')
                ->whereIn('c.outcome', ['pass', 'soft_pass']))
            ->groupBy('ts.acceptance_criteria_id')
            ->pluck('cnt', 'acceptance_criteria_id');

        return $totalByAc
            ->filter(fn($total, $acId) => ($passingByAc[$acId] ?? 0) >= $total)
            ->keys();
    }

    public function isAccepted(): bool
    {
        $scenarios = $this->testScenarios()->with('executions')->get();

        if ($scenarios->isEmpty()) {
            return false;
        }

        return $scenarios->every(function (TestScenario $scenario) {
            $executions = $scenario->executions->keyBy('side');

            $passing = ['pass', 'soft_pass'];

            return isset($executions['provider'], $executions['client'])
                && in_array($executions['provider']->outcome, $passing)
                && in_array($executions['client']->outcome, $passing);
        });
    }
}
