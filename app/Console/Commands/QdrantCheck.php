<?php

namespace App\Console\Commands;

use App\Exceptions\QdrantException;
use App\Services\QdrantService;
use Illuminate\Console\Command;

class QdrantCheck extends Command
{
    protected $signature = 'qdrant:check';
    protected $description = 'Verify Qdrant connectivity and list existing collections';

    public function handle(QdrantService $qdrant): int
    {
        $this->info('Connecting to Qdrant…');

        try {
            $collections = $qdrant->getCollections();
        } catch (QdrantException $e) {
            $this->error($e->getMessage());
            return self::FAILURE;
        }

        if (empty($collections)) {
            $this->line('No collections yet.');
        } else {
            foreach ($collections as $name) {
                $count = $qdrant->pointCount($name);
                $this->line("  • {$name}  ({$count} points)");
            }
        }

        $this->info('Qdrant reachable. Configured vector size: ' . $qdrant->getVectorSize());

        return self::SUCCESS;
    }
}
