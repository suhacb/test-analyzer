<?php

namespace App\Services;

use App\Exceptions\QdrantException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;

class QdrantService
{
    public function __construct(
        private readonly string $baseUrl,
        private readonly ?string $apiKey,
        private readonly int $vectorSize,
        private readonly int $timeout,
    ) {}

    // -------------------------------------------------------------------------
    // Collections
    // -------------------------------------------------------------------------

    /** @return string[] */
    public function getCollections(): array
    {
        $response = $this->call(fn () => $this->client()->get('/collections'), 'list collections');

        return array_column($response->json('result.collections', []), 'name');
    }

    public function hasCollection(string $name): bool
    {
        return in_array($name, $this->getCollections(), true);
    }

    /** Returns the number of indexed points in a collection. */
    public function pointCount(string $name): int
    {
        $response = $this->call(fn () => $this->client()->get("/collections/{$name}"), "get collection '{$name}'");

        return (int) ($response->json('result.points_count') ?? 0);
    }

    /**
     * Create a collection if it does not already exist.
     * Distance is cosine, which suits normalised text embeddings.
     */
    public function ensureCollection(string $name, ?int $vectorSize = null): void
    {
        if ($this->hasCollection($name)) {
            return;
        }

        $this->call(
            fn () => $this->client()->put("/collections/{$name}", [
                'vectors' => [
                    'size'     => $vectorSize ?? $this->vectorSize,
                    'distance' => 'Cosine',
                ],
            ]),
            "create collection '{$name}'"
        );
    }

    // -------------------------------------------------------------------------
    // Points
    // -------------------------------------------------------------------------

    /**
     * Upsert one or more points.
     *
     * Each point: ['id' => int|string, 'vector' => float[], 'payload' => array]
     */
    public function upsert(string $collection, array $points): void
    {
        $this->call(
            fn () => $this->client()->put("/collections/{$collection}/points", ['points' => $points]),
            "upsert into '{$collection}'"
        );
    }

    /**
     * Search for the nearest vectors.
     *
     * @param  array<string, mixed>  $filter  Optional Qdrant filter DSL
     * @return array<int, array{id: mixed, score: float, payload: array}>
     */
    public function search(string $collection, array $vector, int $limit = 10, array $filter = []): array
    {
        $body = ['vector' => $vector, 'limit' => $limit, 'with_payload' => true];

        if (! empty($filter)) {
            $body['filter'] = $filter;
        }

        $response = $this->call(
            fn () => $this->client()->post("/collections/{$collection}/points/search", $body),
            "search '{$collection}'"
        );

        return $response->json('result', []);
    }

    /**
     * Delete points by their IDs.
     *
     * @param  array<int|string>  $ids
     */
    public function deletePoints(string $collection, array $ids): void
    {
        $this->call(
            fn () => $this->client()->post("/collections/{$collection}/points/delete", ['points' => $ids]),
            "delete from '{$collection}'"
        );
    }

    public function getVectorSize(): int { return $this->vectorSize; }

    // -------------------------------------------------------------------------
    // Internals
    // -------------------------------------------------------------------------

    private function client(): PendingRequest
    {
        $client = Http::timeout($this->timeout)->baseUrl($this->baseUrl);

        if ($this->apiKey !== null) {
            $client = $client->withHeader('api-key', $this->apiKey);
        }

        return $client;
    }

    private function call(callable $fn, string $operation): \Illuminate\Http\Client\Response
    {
        try {
            $response = $fn();
        } catch (ConnectionException $e) {
            throw new QdrantException("Cannot reach Qdrant at {$this->baseUrl}: {$e->getMessage()}", previous: $e);
        }

        if (! $response->successful()) {
            $error = $response->json('status.error') ?? $response->body();
            throw new QdrantException("Qdrant failed to {$operation} [{$response->status()}]: {$error}");
        }

        return $response;
    }
}
