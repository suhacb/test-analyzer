<?php

namespace App\Console\Commands;

use App\Exceptions\QdrantException;
use App\Models\TestExecution;
use App\Models\TestScenario;
use App\Services\VectorIndexer;
use Illuminate\Console\Command;

class QdrantSeed extends Command
{
    protected $signature = 'qdrant:seed
                            {--collection= : Seed only this collection (test_executions or test_scenarios)}';

    protected $description = 'Embed and upsert all test executions and scenarios into Qdrant';

    public function handle(VectorIndexer $indexer): int
    {
        $only = $this->option('collection');

        // Ensure collections exist first
        try {
            $indexer->ensureCollections();
        } catch (QdrantException $e) {
            $this->error("Cannot reach Qdrant: {$e->getMessage()}");
            return self::FAILURE;
        }

        if (! $only || $only === VectorIndexer::EXECUTIONS) {
            $this->seedExecutions($indexer);
        }

        if (! $only || $only === VectorIndexer::SCENARIOS) {
            $this->seedScenarios($indexer);
        }

        $this->info('Seeding complete.');
        return self::SUCCESS;
    }

    private function seedExecutions(VectorIndexer $indexer): void
    {
        $total = TestExecution::count();
        $this->info("Indexing {$total} execution(s) into «" . VectorIndexer::EXECUTIONS . "»…");

        $ok = 0;
        $failed = 0;
        $bar = $this->output->createProgressBar($total);
        $bar->start();

        TestExecution::with('testScenario.acceptanceCriteria.userStory')
            ->chunkById(50, function ($chunk) use ($indexer, $bar, &$ok, &$failed) {
                foreach ($chunk as $execution) {
                    try {
                        $indexer->indexExecution($execution);
                        $ok++;
                    } catch (\Throwable) {
                        $failed++;
                    }
                    $bar->advance();
                }
            });

        $bar->finish();
        $this->newLine();
        $this->line("  indexed: {$ok}  failed: {$failed}");
        $this->newLine();
    }

    private function seedScenarios(VectorIndexer $indexer): void
    {
        $total = TestScenario::count();
        $this->info("Indexing {$total} scenario(s) into «" . VectorIndexer::SCENARIOS . "»…");

        $ok = 0;
        $failed = 0;
        $bar = $this->output->createProgressBar($total);
        $bar->start();

        TestScenario::with('acceptanceCriteria.userStory')
            ->chunkById(50, function ($chunk) use ($indexer, $bar, &$ok, &$failed) {
                foreach ($chunk as $scenario) {
                    try {
                        $indexer->indexScenario($scenario);
                        $ok++;
                    } catch (\Throwable) {
                        $failed++;
                    }
                    $bar->advance();
                }
            });

        $bar->finish();
        $this->newLine();
        $this->line("  indexed: {$ok}  failed: {$failed}");
        $this->newLine();
    }
}
