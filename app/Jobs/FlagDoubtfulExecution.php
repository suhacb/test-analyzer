<?php

namespace App\Jobs;

use App\Models\TestExecution;
use App\Models\TestScenario;
use App\Services\OllamaService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class FlagDoubtfulExecution implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(public readonly TestExecution $execution) {}

    public function handle(OllamaService $ollama): void
    {
        $execution = $this->execution->loadMissing('testScenario');

        $reply = $ollama->chatQuick([
            ['role' => 'system', 'content' => $this->systemPrompt()],
            ['role' => 'user',   'content' => $this->userPrompt($execution)],
        ]);

        ['doubtful' => $doubtful, 'reason' => $reason] = $this->parseReply($reply);

        $execution->update([
            'flagged_by_ai'  => $doubtful,
            'ai_flag_reason' => $reason,
        ]);
    }

    private function systemPrompt(): string
    {
        return <<<'SYS'
        You are a software test review assistant. Your only job is to determine whether a test execution record adequately explains its outcome.
        Respond ONLY with a valid JSON object — no markdown fences, no extra text whatsoever.
        SYS;
    }

    private function userPrompt(TestExecution $execution): string
    {
        $scenario = $execution->testScenario;
        $raw      = trim($execution->outcome_raw ?? '') ?: '(none)';

        // Build combined comment from both sources
        $parts = array_filter([
            trim($execution->outcome_comment ?? ''),
            trim($execution->comments ?? ''),
        ]);
        $combinedComment = $parts ? implode("\n", $parts) : '(no comment)';

        return <<<PROMPT
        Determine whether this test execution is "doubtful" — its outcome is not adequately explained by the available comments.

        Mark as doubtful if ANY of these apply:
        - Outcome is fail or pending but the combined comment does not explain what specifically went wrong
        - Comment is too vague to act on (e.g. "ne dela", "napaka", "error" with no specifics)
        - Combined comment contradicts the recorded outcome

        Test scenario:
        Code: {$scenario->code}
        Title: {$scenario->title}
        Expected result: {$scenario->expected_result}

        Execution:
        Outcome: {$execution->outcome}
        Raw outcome text: "{$raw}"
        Combined tester comment (outcome cell + comments cell): "{$combinedComment}"
        Side: {$execution->side}

        Reply with exactly this JSON and nothing else:
        {"doubtful": true, "reason": "one sentence in English"}
        PROMPT;
    }

    private function parseReply(string $content): array
    {
        // Strip markdown fences
        $clean = preg_replace('/^```(?:json)?\s*/m', '', $content);
        $clean = preg_replace('/\s*```\s*$/m', '', $clean);
        $clean = trim($clean);

        $decoded = json_decode($clean, true);

        if (is_array($decoded) && array_key_exists('doubtful', $decoded)) {
            return [
                'doubtful' => (bool) $decoded['doubtful'],
                'reason'   => (string) ($decoded['reason'] ?? ''),
            ];
        }

        // Fallback: extract first {...} block
        if (preg_match('/\{.*?\}/s', $content, $m)) {
            $decoded = json_decode($m[0], true);
            if (is_array($decoded) && array_key_exists('doubtful', $decoded)) {
                return [
                    'doubtful' => (bool) $decoded['doubtful'],
                    'reason'   => (string) ($decoded['reason'] ?? ''),
                ];
            }
        }

        return [
            'doubtful' => false,
            'reason'   => 'Could not parse AI response: ' . mb_substr($content, 0, 200),
        ];
    }
}
