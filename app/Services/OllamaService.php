<?php

namespace App\Services;

use App\Exceptions\OllamaException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

class OllamaService
{
    public function __construct(
        private readonly string $baseUrl,
        private readonly string $quickModel,
        private readonly string $smartModel,
        private readonly string $embedModel,
        private readonly int $timeout,
    ) {}

    /**
     * Send a chat request and return the assistant's reply.
     *
     * @param  array<int, array{role: string, content: string}>  $messages
     */
    public function chat(string $model, array $messages): string
    {
        try {
            $response = Http::timeout($this->timeout)
                ->post("{$this->baseUrl}/api/chat", [
                    'model'    => $model,
                    'messages' => $messages,
                    'stream'   => false,
                ]);
        } catch (ConnectionException $e) {
            throw new OllamaException("Cannot reach Ollama at {$this->baseUrl}: {$e->getMessage()}", previous: $e);
        }

        if ($response->failed()) {
            throw new OllamaException("Ollama chat request failed [{$response->status()}]: {$response->body()}");
        }

        $content = $response->json('message.content');

        if (! is_string($content)) {
            throw new OllamaException('Unexpected Ollama response shape: ' . $response->body());
        }

        return $content;
    }

    /**
     * Embed a piece of text and return the embedding vector.
     *
     * @return float[]
     */
    public function embed(string $model, string $text): array
    {
        try {
            $response = Http::timeout($this->timeout)
                ->post("{$this->baseUrl}/api/embed", [
                    'model' => $model,
                    'input' => $text,
                ]);
        } catch (ConnectionException $e) {
            throw new OllamaException("Cannot reach Ollama at {$this->baseUrl}: {$e->getMessage()}", previous: $e);
        }

        if ($response->failed()) {
            throw new OllamaException("Ollama embed request failed [{$response->status()}]: {$response->body()}");
        }

        $embeddings = $response->json('embeddings.0');

        if (! is_array($embeddings)) {
            throw new OllamaException('Unexpected Ollama embed response shape: ' . $response->body());
        }

        return $embeddings;
    }

    /**
     * Convenience: chat using the configured quick model.
     *
     * @param  array<int, array{role: string, content: string}>  $messages
     */
    public function chatQuick(array $messages): string
    {
        return $this->chat($this->quickModel, $messages);
    }

    /**
     * Convenience: chat using the configured smart model.
     *
     * @param  array<int, array{role: string, content: string}>  $messages
     */
    public function chatSmart(array $messages): string
    {
        return $this->chat($this->smartModel, $messages);
    }

    /** List models currently available in this Ollama instance. */
    public function listModels(): array
    {
        try {
            $response = Http::timeout(10)->get("{$this->baseUrl}/api/tags");
        } catch (ConnectionException $e) {
            throw new OllamaException("Cannot reach Ollama at {$this->baseUrl}: {$e->getMessage()}", previous: $e);
        }

        if ($response->failed()) {
            throw new OllamaException("Ollama tags request failed [{$response->status()}]: {$response->body()}");
        }

        return array_column($response->json('models', []), 'name');
    }

    /** Convenience: embed using the configured embed model. */
    public function embedText(string $text): array
    {
        return $this->embed($this->embedModel, $text);
    }

    public function getQuickModel(): string { return $this->quickModel; }

    public function getSmartModel(): string { return $this->smartModel; }

    public function getEmbedModel(): string { return $this->embedModel; }
}
