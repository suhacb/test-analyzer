<?php

namespace App\Http\Controllers;

use App\Services\ChatContextBuilder;
use App\Services\OllamaService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ChatController extends Controller
{
    public function __construct(
        private readonly OllamaService    $ollama,
        private readonly ChatContextBuilder $contextBuilder,
    ) {}

    public function stream(Request $request): StreamedResponse
    {
        $validated = $request->validate([
            'prompt'            => ['required', 'string', 'max:2000'],
            'context_type'      => ['required', 'string', 'in:user_story,acceptance_criteria'],
            'context_id'        => ['required', 'integer', 'min:1'],
            'history'           => ['array', 'max:20'],
            'history.*.role'    => ['required', 'string', 'in:user,assistant'],
            'history.*.content' => ['required', 'string'],
        ]);

        return response()->stream(function () use ($validated): void {
            $emit = function (array $data): void {
                echo json_encode($data) . "\n";
                if (ob_get_level() > 0) ob_flush();
                flush();
            };

            try {
                $emit(['type' => 'status', 'text' => 'Validating prompt…']);

                if (! $this->isRelevant($validated['prompt'])) {
                    $emit(['type' => 'error', 'text' => 'I can only help with questions about test results, acceptance criteria, test scenarios, defects, or quality assurance for this project.']);
                    return;
                }

                $emit(['type' => 'status', 'text' => 'Loading context…']);
                $context  = $this->contextBuilder->build($validated['context_type'], $validated['context_id']);
                $messages = $this->buildMessages($context, $validated['history'] ?? [], $validated['prompt']);

                $emit(['type' => 'status', 'text' => 'Generating response…']);

                $this->ollama->streamChatSmart($messages, function (string $token) use ($emit): void {
                    $emit(['type' => 'token', 'text' => $token]);
                });

                $emit(['type' => 'done']);
            } catch (\Throwable $e) {
                $emit(['type' => 'error', 'text' => 'An error occurred while processing your request.']);
            }
        }, 200, [
            'Content-Type'      => 'application/x-ndjson',
            'Cache-Control'     => 'no-cache',
            'X-Accel-Buffering' => 'no',
        ]);
    }

    // ── Internals ─────────────────────────────────────────────────────────────

    private function isRelevant(string $prompt): bool
    {
        $reply = $this->ollama->chatSmart([
            [
                'role'    => 'system',
                'content' => 'You are a gate-keeper for a software test analysis assistant. '
                    . 'Decide if the user\'s question relates to: software testing, test results, '
                    . 'acceptance criteria, test scenarios, defects, quality assurance, or test coverage. '
                    . 'Reply with exactly one word: "yes" or "no".',
            ],
            [
                'role'    => 'user',
                'content' => $prompt,
            ],
        ]);

        return str_contains(strtolower(trim($reply)), 'yes');
    }

    private function buildMessages(string $context, array $history, string $prompt): array
    {
        $system = <<<SYSTEM
        You are a professional software test analysis assistant. You help users understand test results,
        acceptance criteria, test scenarios, defects, and quality assurance data.
        Answer questions based on the context provided below. Be specific, reference actual data, and be concise.
        If the context does not contain enough information to answer, say so clearly.
        You may respond in the language the user writes in.

        === CONTEXT ===
        {$context}
        === END CONTEXT ===
        SYSTEM;

        $messages = [['role' => 'system', 'content' => $system]];

        foreach ($history as $entry) {
            $messages[] = ['role' => $entry['role'], 'content' => $entry['content']];
        }

        $messages[] = ['role' => 'user', 'content' => $prompt];

        return $messages;
    }
}
