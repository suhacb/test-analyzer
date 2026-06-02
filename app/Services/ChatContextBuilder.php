<?php

namespace App\Services;

use App\Models\AcceptanceCriteria;
use App\Models\UserStory;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class ChatContextBuilder
{
    public function build(string $type, int $id): string
    {
        return match ($type) {
            'user_story'          => $this->buildUserStoryContext($id),
            'acceptance_criteria' => $this->buildAcContext($id),
            default               => '',
        };
    }

    // ── Context builders ──────────────────────────────────────────────────────

    private function buildUserStoryContext(int $id): string
    {
        $us = UserStory::with(['acceptanceCriteria.testScenarios.executions'])
            ->findOrFail($id);

        $lines = [];
        $lines[] = "USER STORY: {$us->code} — {$us->title}";
        $lines[] = "Acceptance criteria: {$us->acceptanceCriteria->count()}";
        $lines[] = '';

        foreach ($us->acceptanceCriteria as $ac) {
            $lines[] = "  CRITERION: {$ac->code} — {$ac->title}";

            if ($ac->ai_summary) {
                $lines[] = "  AI Summary:";
                foreach (explode("\n", $ac->ai_summary) as $summaryLine) {
                    $lines[] = "    {$summaryLine}";
                }
                $lines[] = '';
            }

            foreach ($ac->testScenarios as $ts) {
                $lines[] = "    Scenario {$ts->code}: {$ts->title}";
                if ($ts->expected_result) {
                    $lines[] = "      Expected: {$ts->expected_result}";
                }
                if ($ts->executions->isEmpty()) {
                    $lines[] = "      No executions recorded.";
                } else {
                    foreach ($ts->executions->sortBy('id') as $ex) {
                        $date    = $ex->tested_at?->format('d.m.Y') ?? '—';
                        $outcome = strtoupper(str_replace('_', ' ', $ex->outcome));
                        $note    = implode(' / ', array_filter([
                            trim($ex->outcome_comment ?? ''),
                            trim($ex->comments ?? ''),
                        ]));
                        $line = "      [{$ex->side}] {$date}: {$outcome}";
                        if ($note) $line .= " — {$note}";
                        if ($ex->failure_cause) $line .= " (cause: {$ex->failure_cause})";
                        $lines[] = $line;
                    }
                }
            }

            // Aggregate latest per side
            $allExecs     = $ac->testScenarios->flatMap(fn ($ts) => $ts->executions);
            $latestBySide = $allExecs->sortByDesc('id')->unique(fn ($e) => $e->test_scenario_id . '_' . $e->side);

            foreach (['provider', 'client'] as $side) {
                $forSide = $latestBySide->where('side', $side);
                if ($forSide->isEmpty()) continue;
                $counts = $forSide->groupBy('outcome')->map->count();
                $parts  = [];
                foreach (['pass' => 'pass', 'soft_pass' => 'soft pass', 'fail' => 'fail', 'pending' => 'pending'] as $key => $label) {
                    if ($counts->has($key)) $parts[] = "{$counts[$key]} {$label}";
                }
                $lines[] = "  Latest {$side}: " . implode(', ', $parts);
            }

            $lines[] = '';
        }

        return implode("\n", $lines);
    }

    private function buildAcContext(int $id): string
    {
        $ac = AcceptanceCriteria::with(['userStory', 'testScenarios.executions'])
            ->findOrFail($id);

        $lines = [];
        $lines[] = "ACCEPTANCE CRITERION: {$ac->code} — {$ac->title}";
        $lines[] = "User Story: {$ac->userStory->code} — {$ac->userStory->title}";
        $lines[] = "Scenarios: {$ac->testScenarios->count()}";
        $lines[] = '';

        if ($ac->ai_summary) {
            $lines[] = "AI Summary (narrative):";
            $lines[] = $ac->ai_summary;
            $lines[] = '';
        }

        $lines[] = "--- Raw execution data ---";
        $lines[] = '';

        foreach ($ac->testScenarios as $ts) {
            $lines[] = "Scenario {$ts->code}: {$ts->title}";
            if ($ts->expected_result) {
                $lines[] = "  Expected: {$ts->expected_result}";
            }

            foreach ($ts->executions->sortBy('id') as $ex) {
                $date    = $ex->tested_at?->format('d.m.Y') ?? '—';
                $outcome = strtoupper(str_replace('_', ' ', $ex->outcome));
                $note    = implode(' / ', array_filter([
                    trim($ex->outcome_comment ?? ''),
                    trim($ex->comments ?? ''),
                ]));
                $line = "  [{$ex->side}] {$date}: {$outcome}";
                if ($note) $line .= " — {$note}";
                if ($ex->failure_cause) $line .= " (cause: {$ex->failure_cause})";
                $lines[] = $line;
            }
            $lines[] = '';
        }

        return implode("\n", $lines);
    }
}
