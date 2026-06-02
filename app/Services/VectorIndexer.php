<?php

namespace App\Services;

use App\Models\TestExecution;
use App\Models\TestScenario;

class VectorIndexer
{
    public const EXECUTIONS = 'test_executions';
    public const SCENARIOS  = 'test_scenarios';

    public function __construct(
        private readonly OllamaService $ollama,
        private readonly QdrantService $qdrant,
    ) {}

    // ── Collection setup ──────────────────────────────────────────────────────

    public function ensureCollections(): void
    {
        $this->qdrant->ensureCollection(self::EXECUTIONS);
        $this->qdrant->ensureCollection(self::SCENARIOS);
    }

    // ── Indexing ──────────────────────────────────────────────────────────────

    /**
     * Embed and upsert one execution. Silently logs and returns on any error
     * so callers are not interrupted by indexing failures.
     */
    public function indexExecution(TestExecution $execution): void
    {
        $execution->loadMissing('testScenario.acceptanceCriteria.userStory');
        $scenario = $execution->testScenario;
        $ac       = $scenario->acceptanceCriteria;

        $vector = $this->ollama->embedText($this->executionText($execution, $scenario));

        $this->qdrant->upsert(self::EXECUTIONS, [[
            'id'      => $execution->id,
            'vector'  => $vector,
            'payload' => [
                'execution_id'  => $execution->id,
                'scenario_id'   => $scenario->id,
                'ac_id'         => $ac->id,
                'us_id'         => $ac->userStory->id,
                'ac_code'       => $ac->code,
                'side'          => $execution->side,
                'outcome'       => $execution->outcome,
                'failure_cause' => $execution->failure_cause,
                'tested_at'     => $execution->tested_at?->toDateString(),
            ],
        ]]);
    }

    public function indexScenario(TestScenario $scenario): void
    {
        $scenario->loadMissing('acceptanceCriteria.userStory');
        $ac = $scenario->acceptanceCriteria;

        $vector = $this->ollama->embedText($this->scenarioText($scenario));

        $this->qdrant->upsert(self::SCENARIOS, [[
            'id'      => $scenario->id,
            'vector'  => $vector,
            'payload' => [
                'scenario_id' => $scenario->id,
                'ac_id'       => $ac->id,
                'us_id'       => $ac->userStory->id,
                'code'  => $scenario->code,
                'title' => $scenario->title,
            ],
        ]]);
    }

    // ── Search ────────────────────────────────────────────────────────────────

    /**
     * Find executions semantically similar to a piece of text.
     *
     * @param  array<string, mixed>  $filter  Qdrant filter DSL (optional)
     * @return array<int, array{id: mixed, score: float, payload: array}>
     */
    public function findSimilarExecutions(string $text, int $limit = 5, array $filter = []): array
    {
        $vector = $this->ollama->embedText($text);
        return $this->qdrant->search(self::EXECUTIONS, $vector, $limit, $filter);
    }

    /**
     * Find scenarios semantically similar to a piece of text.
     *
     * @return array<int, array{id: mixed, score: float, payload: array}>
     */
    public function findSimilarScenarios(string $text, int $limit = 5, array $filter = []): array
    {
        $vector = $this->ollama->embedText($text);
        return $this->qdrant->search(self::SCENARIOS, $vector, $limit, $filter);
    }

    // ── Text builders ─────────────────────────────────────────────────────────

    public function executionText(TestExecution $execution, TestScenario $scenario): string
    {
        return implode(' | ', array_filter([
            $scenario->title,
            $scenario->expected_result,
            $execution->outcome,
            $execution->outcome_comment,
            $execution->comments,
        ]));
    }

    public function scenarioText(TestScenario $scenario): string
    {
        return implode("\n", array_filter([
            "{$scenario->code}: {$scenario->title}",
            $scenario->expected_result,
            $scenario->test_steps,
        ]));
    }
}
