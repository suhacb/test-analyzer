<?php

namespace App\Providers;

use App\Services\AiOutcomeParser;
use App\Services\OllamaService;
use App\Services\QdrantService;
use App\Services\VectorIndexer;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(OllamaService::class, fn () => new OllamaService(
            baseUrl:      config('ollama.base_url'),
            quickModel:   config('ollama.quick_model'),
            smartModel:   config('ollama.smart_model'),
            embedModel:   config('ollama.embed_model'),
            timeout:      config('ollama.timeout'),
            smartTimeout: config('ollama.smart_timeout'),
        ));

        $this->app->singleton(AiOutcomeParser::class, fn (mixed $app) =>
            new AiOutcomeParser($app->make(OllamaService::class))
        );

        $this->app->singleton(QdrantService::class, fn () => new QdrantService(
            baseUrl:    config('qdrant.base_url'),
            apiKey:     config('qdrant.api_key'),
            vectorSize: config('qdrant.vector_size'),
            timeout:    config('qdrant.timeout'),
        ));

        $this->app->singleton(VectorIndexer::class, fn (mixed $app) => new VectorIndexer(
            ollama: $app->make(OllamaService::class),
            qdrant: $app->make(QdrantService::class),
        ));
    }

    public function boot(): void {}
}
