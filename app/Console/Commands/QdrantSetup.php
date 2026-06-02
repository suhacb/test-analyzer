<?php

namespace App\Console\Commands;

use App\Exceptions\QdrantException;
use App\Services\VectorIndexer;
use Illuminate\Console\Command;

class QdrantSetup extends Command
{
    protected $signature = 'qdrant:setup';
    protected $description = 'Create Qdrant collections required by the application';

    public function handle(VectorIndexer $indexer): int
    {
        $this->info('Setting up Qdrant collections…');

        try {
            $indexer->ensureCollections();
        } catch (QdrantException $e) {
            $this->error($e->getMessage());
            return self::FAILURE;
        }

        foreach ([VectorIndexer::EXECUTIONS, VectorIndexer::SCENARIOS] as $name) {
            $this->line("  ✓ {$name}");
        }

        $this->info('Done.');
        return self::SUCCESS;
    }
}
