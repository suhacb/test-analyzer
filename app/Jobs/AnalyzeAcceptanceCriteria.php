<?php

namespace App\Jobs;

use App\Models\AcceptanceCriteria;
use App\Services\OllamaService;
use App\Services\VectorIndexer;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class AnalyzeAcceptanceCriteria implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 2;
    public int $timeout = 300;

    public function __construct(public readonly AcceptanceCriteria $ac) {}

    public function handle(OllamaService $ollama, VectorIndexer $indexer): void
    {
        $ac = $this->ac->loadMissing(['userStory', 'testScenarios.executions']);

        $similarContext = $this->fetchSimilarContext($ac, $indexer);
        $prompt         = $this->buildPrompt($ac, $similarContext);

        $summary = $ollama->chatSmart([
            [
                'role'    => 'system',
                'content' => <<<'SYS'
                You are a professional software test analyst writing in Slovenian.
                Analyse the provided acceptance criteria test results and write a clear, concise narrative.
                Write exclusively in Slovenian. Be specific and actionable.
                SYS,
            ],
            [
                'role'    => 'user',
                'content' => $prompt,
            ],
        ]);

        $ac->update([
            'ai_summary'              => trim($summary),
            'ai_summary_generated_at' => now(),
        ]);

        Log::info('AnalyzeAcceptanceCriteria: done', ['ac' => $ac->code]);
    }

    public function failed(\Throwable $e): void
    {
        Log::error('AnalyzeAcceptanceCriteria: failed', [
            'ac'    => $this->ac->code,
            'error' => $e->getMessage(),
        ]);
    }

    // ── Prompt building ───────────────────────────────────────────────────────

    private function fetchSimilarContext(AcceptanceCriteria $ac, VectorIndexer $indexer): string
    {
        $failedExecs = $ac->testScenarios
            ->flatMap(fn ($ts) => $ts->executions)
            ->where('outcome', 'fail');

        if ($failedExecs->isEmpty()) {
            return '';
        }

        $queryText = $failedExecs
            ->map(fn ($e) => implode(' ', array_filter([$e->outcome_comment, $e->comments])))
            ->filter()
            ->implode(' | ');

        if (! $queryText) {
            return '';
        }

        try {
            $hits = $indexer->findSimilarExecutions(
                $queryText,
                limit: 4,
                filter: ['must_not' => [['key' => 'ac_id', 'match' => ['value' => $ac->id]]]],
            );
        } catch (\Throwable) {
            return '';
        }

        if (empty($hits)) {
            return '';
        }

        $lines = ['', '== Podobni primeri napak iz drugih kriterijev =='];
        foreach ($hits as $hit) {
            $p       = $hit['payload'] ?? [];
            $score   = round($hit['score'] ?? 0, 2);
            $outcome = strtoupper($p['outcome'] ?? '');
            $cause   = $p['failure_cause'] ? " (vzrok: {$p['failure_cause']})" : '';
            $lines[] = "  [{$p['ac_code']} · {$p['side']}] {$outcome}{$cause} — podobnost {$score}";
        }

        return implode("\n", $lines);
    }

    private function buildPrompt(AcceptanceCriteria $ac, string $similarContext = ''): string
    {
        $us       = $ac->userStory;
        $scenarios = $ac->testScenarios;

        $lines = [];
        $lines[] = "KRITERIJ SPREJEMLJIVOSTI: {$ac->code} — {$ac->title}";
        $lines[] = "UPORABNIŠKA ZGODBA: {$us->code} — {$us->title}";
        $lines[] = "Skupaj scenarijev: {$scenarios->count()}";
        $lines[] = '';

        // Per-scenario execution history
        foreach ($scenarios as $ts) {
            $lines[] = "── Scenarij: {$ts->code} — {$ts->title}";
            if ($ts->expected_result) {
                $lines[] = "   Pričakovan rezultat: {$ts->expected_result}";
            }

            $executions = $ts->executions->sortBy('id');

            if ($executions->isEmpty()) {
                $lines[] = '   Izvajanja: nobeno';
            } else {
                foreach ($executions as $ex) {
                    $date    = $ex->tested_at?->format('d.m.Y') ?? '—';
                    $outcome = strtoupper(str_replace('_', ' ', $ex->outcome));
                    $comment = implode(' / ', array_filter([
                        trim($ex->outcome_comment ?? ''),
                        trim($ex->comments ?? ''),
                    ]));

                    $line = "   [{$ex->side}] {$date}: {$outcome}";
                    if ($comment) {
                        $line .= " — {$comment}";
                    }
                    if ($ex->failure_cause) {
                        $line .= " (vzrok: {$ex->failure_cause})";
                    }
                    $lines[] = $line;
                }
            }
            $lines[] = '';
        }

        // Aggregate summary
        $allExecs       = $scenarios->flatMap(fn ($ts) => $ts->executions);
        $latestBySide   = $allExecs->sortByDesc('id')->unique(fn ($e) => $e->test_scenario_id . '_' . $e->side);

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
            $lines[] = ucfirst($side) . ' (zadnje stanje): ' . implode(', ', $parts);
        }

        if ($similarContext) {
            $lines[] = $similarContext;
        }

        $lines[] = '';
        $lines[] = <<<'INSTR'
        Napiši strokovno analizo v slovenščini, ki zajema:
        1. Pregled testiranja po scenarijih — kaj je prešlo in kaj ne
        2. Ugotovljene težave in njihove vzroke
        3. Vzorce ali ponavljajoče se napake (če obstajajo)
        4. Skupna ocena pripravljenosti za sprejem kriterija
        5. Priporočila za nadaljnje korake (če so potrebni)

        Če so na voljo podobni primeri iz drugih kriterijev, jih upoštevaj pri iskanju vzorcev.
        Analiza naj bo jedrnata (največ 300 besed), strokovna in neposredno uporabna.
        INSTR;

        return implode("\n", $lines);
    }
}
