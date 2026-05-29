<?php

namespace App\Console\Commands;

use App\Exceptions\OllamaException;
use App\Services\OllamaService;
use Illuminate\Console\Command;

class OllamaCheck extends Command
{
    protected $signature = 'ollama:check';
    protected $description = 'Verify Ollama connectivity and that the configured models are available';

    public function handle(OllamaService $ollama): int
    {
        $this->info('Connecting to Ollama…');

        try {
            $available = $ollama->listModels();
        } catch (OllamaException $e) {
            $this->error($e->getMessage());
            return self::FAILURE;
        }

        $this->line('Available models: ' . (empty($available) ? '(none)' : implode(', ', $available)));

        $allPresent = true;

        foreach (['quick' => $ollama->getQuickModel(), 'smart' => $ollama->getSmartModel()] as $role => $model) {
            $present = collect($available)->contains(fn ($name) => str_starts_with($name, $model));

            if ($present) {
                $this->info("  [{$role}] {$model} — found");
            } else {
                $this->warn("  [{$role}] {$model} — NOT found (run: ollama pull {$model})");
                $allPresent = false;
            }
        }

        return $allPresent ? self::SUCCESS : self::FAILURE;
    }
}
