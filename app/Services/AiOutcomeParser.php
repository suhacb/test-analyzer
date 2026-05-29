<?php

namespace App\Services;

use App\Exceptions\OllamaException;

class AiOutcomeParser
{
    public const CAUSES = [
        'software_bug',
        'lack_of_data',
        'no_integration_kis',
        'no_integration_euez',
        'not_accessible',
        'other',
    ];

    public const CAUSE_LABELS = [
        'software_bug'        => 'Software bug',
        'lack_of_data'        => 'Lack of data',
        'no_integration_kis'  => 'No KIS integration',
        'no_integration_euez' => 'No EueZ integration',
        'not_accessible'      => 'Not accessible',
        'other'               => 'Other',
    ];

    public function __construct(private readonly OllamaService $ollama) {}

    // ── Public API ────────────────────────────────────────────────────────────

    /**
     * Parse the outcome cell, extracting the normalised outcome and any
     * comment text embedded in the cell alongside the result indicator.
     *
     * @return array{outcome: string, outcome_comment: ?string, needs_review: bool}
     */
    public function parseOutcomeCell(string $outcomeRaw, ?string $commentsCell): array
    {
        $result = $this->tryRegex($outcomeRaw);

        if ($result['confident']) {
            return [
                'outcome'         => $result['outcome'],
                'outcome_comment' => $result['outcome_comment'],
                'needs_review'    => false,
            ];
        }

        try {
            return $this->aiParseOutcome($outcomeRaw, $commentsCell);
        } catch (OllamaException) {
            return ['outcome' => 'pending', 'outcome_comment' => null, 'needs_review' => true];
        }
    }

    /**
     * Classify the most likely cause of a failed test execution.
     * Falls back to 'other' when Ollama is unreachable.
     */
    public function classifyFailureCause(
        ?string $expectedResult,
        string $outcomeRaw,
        ?string $outcomeComment,
        ?string $comments,
    ): string {
        try {
            return $this->aiClassifyFailure($expectedResult, $outcomeRaw, $outcomeComment, $comments);
        } catch (OllamaException) {
            return 'other';
        }
    }

    // ── Regex parsing ─────────────────────────────────────────────────────────

    /**
     * Attempt outcome extraction without AI.
     * Patterns are ordered most-specific → least-specific.
     */
    private function tryRegex(string $outcomeRaw): array
    {
        $patterns = [
            '/TEST\s+NI\s+OK/iu'    => 'fail',
            '/TEST\s+DELNO\s+OK/iu' => 'soft_pass',
            '/TEST\s*:?\s*OK/iu'    => 'pass',
            '/NEUSPE[ŠS]/iu'        => 'fail',
            '/DELNO\s+OK/iu'        => 'soft_pass',
            '/NI\s+OK/iu'           => 'fail',
            '/USPE[ŠS]/iu'          => 'pass',
        ];

        foreach ($patterns as $pattern => $outcome) {
            $parts = preg_split($pattern, $outcomeRaw, 2);

            if (count($parts) === 2) {
                $comment = trim($parts[1], " \t\n\r\0\x0B\u{2013}\u{2014}\u{2012}-:/,;.'\"");

                return [
                    'outcome'         => $outcome,
                    'outcome_comment' => $comment !== '' ? $comment : null,
                    'confident'       => true,
                ];
            }
        }

        return ['outcome' => 'pending', 'outcome_comment' => null, 'confident' => false];
    }

    // ── AI: outcome parsing ───────────────────────────────────────────────────

    private function aiParseOutcome(string $outcomeRaw, ?string $commentsCell): array
    {
        $commentsText = $commentsCell ?: '(empty)';

        $reply = $this->ollama->chatQuick([
            [
                'role'    => 'system',
                'content' => 'You are analysing Slovenian software test reports. Respond ONLY with a valid JSON object — no markdown, no extra text.',
            ],
            [
                'role'    => 'user',
                'content' => <<<PROMPT
                Analyse this test execution record from a Slovenian test report.

                Outcome cell (often contains result indicator + embedded comment): "{$outcomeRaw}"
                Comments cell: "{$commentsText}"

                Tasks:
                1. Determine the test outcome:
                   - "pass"      — test completed successfully
                   - "soft_pass" — test passed with minor/partial issues
                   - "fail"      — test failed
                   - "pending"   — truly cannot be determined from the available text
                2. Extract any comment text that is embedded in the outcome cell but is NOT the result indicator itself (null if none).
                3. Assess confidence: "high" or "low".

                Respond with exactly this JSON and nothing else:
                {"outcome": "pass|soft_pass|fail|pending", "outcome_comment": "text or null", "confidence": "high|low"}
                PROMPT,
            ],
        ]);

        $data   = $this->extractJson($reply);
        $outcome = match ($data['outcome'] ?? '') {
            'pass'      => 'pass',
            'soft_pass' => 'soft_pass',
            'fail'      => 'fail',
            default     => 'pending',
        };

        $outcomeComment = isset($data['outcome_comment']) && is_string($data['outcome_comment'])
            ? (trim($data['outcome_comment']) ?: null)
            : null;

        return [
            'outcome'         => $outcome,
            'outcome_comment' => $outcomeComment,
            'needs_review'    => $outcome === 'pending',
        ];
    }

    // ── AI: failure cause ─────────────────────────────────────────────────────

    private function aiClassifyFailure(
        ?string $expectedResult,
        string $outcomeRaw,
        ?string $outcomeComment,
        ?string $comments,
    ): string {
        $contextLines = [];
        if ($expectedResult) {
            $contextLines[] = "Expected result: {$expectedResult}";
        }
        $contextLines[] = "Raw outcome text: {$outcomeRaw}";
        $combined = implode("\n", array_filter([$outcomeComment, $comments]));
        if ($combined) {
            $contextLines[] = "Tester comments: {$combined}";
        }
        $context = implode("\n", $contextLines);

        $reply = $this->ollama->chatSmart([
            [
                'role'    => 'system',
                'content' => 'You are classifying failed software test cases for a Slovenian ERP/HR system. Respond ONLY with a valid JSON object — no markdown, no extra text.',
            ],
            [
                'role'    => 'user',
                'content' => <<<PROMPT
                Classify the most likely root cause of this test failure.

                {$context}

                Cause categories (pick exactly one):
                - software_bug        — defect in application code or business logic
                - lack_of_data        — missing reference/test data, common in reporting modules
                - no_integration_kis  — KIS (HR management information system) integration not in place
                - no_integration_euez — EueZ (external OpenID authentication provider) integration not in place
                - not_accessible      — application unreachable from public network or mobile device
                - other               — does not fit any of the above

                Respond with exactly this JSON and nothing else:
                {"cause": "software_bug|lack_of_data|no_integration_kis|no_integration_euez|not_accessible|other", "confidence": "high|low"}
                PROMPT,
            ],
        ]);

        $data  = $this->extractJson($reply);
        $cause = $data['cause'] ?? 'other';

        return in_array($cause, self::CAUSES, true) ? $cause : 'other';
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function extractJson(string $content): array
    {
        $clean = preg_replace('/^```(?:json)?\s*/m', '', $content);
        $clean = preg_replace('/\s*```\s*$/m', '', $clean);
        $clean = trim($clean);

        $decoded = json_decode($clean, true);
        if (is_array($decoded)) {
            return $decoded;
        }

        if (preg_match('/\{.*?\}/s', $content, $m)) {
            $decoded = json_decode($m[0], true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }

        return [];
    }
}
