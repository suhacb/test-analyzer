<?php

namespace App\Jobs;

use App\Models\UserStory;
use App\Services\OllamaService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class AnalyzeUserStory implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 2;
    public int $timeout = 300;

    public function __construct(public readonly UserStory $userStory) {}

    public function handle(OllamaService $ollama): void
    {
        $us = $this->userStory->loadMissing(['acceptanceCriteria.testScenarios.executions']);

        $prompt = $this->buildPrompt($us);

        $report = $ollama->chatSmart([
            [
                'role'    => 'system',
                'content' => <<<'SYS'
                You are a professional software test analyst writing in Slovenian.
                Analyse the provided user story test results across all its acceptance criteria and write a clear, executive-level narrative report.
                Write exclusively in Slovenian. Be specific and actionable.
                SYS,
            ],
            [
                'role'    => 'user',
                'content' => $prompt,
            ],
        ]);

        $us->update([
            'ai_report'              => trim($report),
            'ai_report_generated_at' => now(),
        ]);

        Log::info('AnalyzeUserStory: done', ['us' => $us->code]);
    }

    public function failed(\Throwable $e): void
    {
        Log::error('AnalyzeUserStory: failed', [
            'us'    => $this->userStory->code,
            'error' => $e->getMessage(),
        ]);
    }

    // ── Prompt building ───────────────────────────────────────────────────────

    private function buildPrompt(UserStory $us): string
    {
        $lines = [];
        $lines[] = "UPORABNIŠKA ZGODBA: {$us->code} — {$us->title}";
        $lines[] = "Skupaj kriterijev sprejemljivosti: {$us->acceptanceCriteria->count()}";
        $lines[] = '';

        foreach ($us->acceptanceCriteria as $ac) {
            $lines[] = "══ Kriterij: {$ac->code} — {$ac->title}";

            // If an AI summary already exists, include it as context
            if ($ac->ai_summary) {
                $lines[] = "   AI analiza kriterija:";
                foreach (explode("\n", $ac->ai_summary) as $summaryLine) {
                    $lines[] = "   {$summaryLine}";
                }
            } else {
                // Fall back to raw execution data
                foreach ($ac->testScenarios as $ts) {
                    $lines[] = "   ── Scenarij: {$ts->code} — {$ts->title}";

                    $executions = $ts->executions->sortBy('id');
                    if ($executions->isEmpty()) {
                        $lines[] = '      Izvajanja: nobeno';
                    } else {
                        foreach ($executions as $ex) {
                            $date    = $ex->tested_at?->format('d.m.Y') ?? '—';
                            $outcome = strtoupper(str_replace('_', ' ', $ex->outcome));
                            $comment = implode(' / ', array_filter([
                                trim($ex->outcome_comment ?? ''),
                                trim($ex->comments ?? ''),
                            ]));

                            $line = "      [{$ex->side}] {$date}: {$outcome}";
                            if ($comment) {
                                $line .= " — {$comment}";
                            }
                            if ($ex->failure_cause) {
                                $line .= " (vzrok: {$ex->failure_cause})";
                            }
                            $lines[] = $line;
                        }
                    }
                }
            }

            // Aggregate latest state per side
            $allExecs     = $ac->testScenarios->flatMap(fn ($ts) => $ts->executions);
            $latestBySide = $allExecs->sortByDesc('id')->unique(fn ($e) => $e->test_scenario_id . '_' . $e->side);

            foreach (['provider', 'client'] as $side) {
                $forSide = $latestBySide->where('side', $side);
                if ($forSide->isEmpty()) continue;

                $counts = $forSide->groupBy('outcome')->map->count();
                $parts  = [];
                foreach (['pass' => 'OK', 'soft_pass' => 'DELNO OK', 'fail' => 'NI OK', 'pending' => 'ČAKA'] as $key => $label) {
                    if ($counts->has($key)) {
                        $parts[] = "{$label}: {$counts[$key]}";
                    }
                }
                $lines[] = "   " . ucfirst($side) . " (zadnje stanje): " . implode(', ', $parts);
            }

            $lines[] = '';
        }

        $lines[] = <<<'INSTR'
        Napiši strokovno poročilo v slovenščini za celotno uporabniško zgodbo, ki zajema:
        1. Skupni pregled testiranja — koliko kriterijev je sprejeto, koliko je odprtih napak
        2. Ključne ugotovljene težave in vzorce napak po kriterijih
        3. Skupna ocena pripravljenosti uporabniške zgodbe za zaključek
        4. Prioritizirana priporočila za nadaljnje korake

        Poročilo naj bo jedrnato (največ 400 besed), na ravni vodstva in neposredno uporabno.
        INSTR;

        return implode("\n", $lines);
    }
}
