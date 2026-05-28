<?php

namespace App\Console\Commands;

use App\Jobs\ProcessDocument;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class DispatchDocuments extends Command
{
    protected $signature = 'documents:dispatch';
    protected $description = 'Dispatch a ProcessDocument job for every .docx in storage/app/private/docs/';

    // ASCII-only patterns — avoids NFD/NFC encoding mismatches on macOS/Linux
    private const SIDE_MAP = [
        'izvajalca'  => 'provider',
        'narocnika'  => 'client',
    ];

    public function handle(): int
    {
        $basePath = Storage::disk('local')->path('docs');

        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($basePath, \FilesystemIterator::SKIP_DOTS)
        );

        $dispatched = 0;

        foreach ($files as $file) {
            if (strtolower($file->getExtension()) !== 'docx') {
                continue;
            }

            $absolutePath = $file->getPathname();
            $side = $this->inferSide($absolutePath);

            if ($side === null) {
                $this->warn("Skipped (cannot infer side): {$absolutePath}");
                continue;
            }

            ProcessDocument::dispatch($absolutePath, $side);
            $dispatched++;
        }

        $this->info("Dispatched {$dispatched} jobs onto the [documents] queue.");

        return self::SUCCESS;
    }

    private function inferSide(string $path): ?string
    {
        $lower = mb_strtolower($path);

        foreach (self::SIDE_MAP as $needle => $side) {
            if (str_contains($lower, $needle)) {
                return $side;
            }
        }

        return null;
    }
}
