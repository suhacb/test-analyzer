<?php

namespace App\Providers;

use App\Services\AiOutcomeParser;
use App\Services\OllamaService;
use App\Services\QdrantService;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(OllamaService::class, fn () => new OllamaService(
            baseUrl:    config('ollama.base_url'),
            quickModel: config('ollama.quick_model'),
            smartModel: config('ollama.smart_model'),
            embedModel: config('ollama.embed_model'),
            timeout:    config('ollama.timeout'),
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
    }

    public function boot(): void {}
}
