<?php

declare(strict_types=1);

namespace App\AI\Console;

use App\AI\Jobs\EmbedDocumentJob;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class AiEmbedDocsCommand extends Command
{
    protected $signature = 'ai:embed-docs
        {--path=docs/KB : Source path relative to the project root}
        {--namespace=kb : Memory namespace}
        {--chunk-size=800 : Approximate word count per chunk}
        {--overlap=100 : Word overlap between chunks}';

    protected $description = 'Walk a docs folder, chunk markdown files, embed them, and persist into ai_memories.';

    public function handle(): int
    {
        $path = (string) $this->option('path');
        $namespace = (string) $this->option('namespace');
        $chunkSize = max(100, (int) $this->option('chunk-size'));
        $overlap = max(0, min($chunkSize - 50, (int) $this->option('overlap')));

        $abs = base_path($path);

        if (! File::exists($abs)) {
            $this->warn("Path does not exist: {$abs}. Create it and add markdown articles.");

            return self::SUCCESS;
        }

        $files = File::allFiles($abs);

        if (empty($files)) {
            $this->warn("No files found under {$abs}.");

            return self::SUCCESS;
        }

        $count = 0;

        foreach ($files as $file) {
            if ($file->getExtension() !== 'md') {
                continue;
            }

            $contents = File::get($file->getPathname());
            $chunks = $this->chunk($contents, $chunkSize, $overlap);
            $sourceId = (string) Str::uuid();

            EmbedDocumentJob::dispatch(
                namespace: $namespace,
                source: 'doc',
                sourceId: $sourceId,
                chunks: $chunks,
                metadata: [
                    'path' => (string) $file->getPathname(),
                    'url' => 'docs/'.Str::after($file->getPathname(), 'docs/'),
                    'title' => $file->getFilenameWithoutExtension(),
                ],
            );

            $count++;
            $this->info("Queued {$file->getFilename()} ({$file->getPathname()}) — ".count($chunks).' chunks');
        }

        $this->info("Queued {$count} files for embedding.");

        return self::SUCCESS;
    }

    /**
     * Naive word-based chunker. Good enough for markdown KB articles.
     *
     * @return array<int, string>
     */
    protected function chunk(string $text, int $size, int $overlap): array
    {
        $words = preg_split('/\s+/', trim($text));
        $chunks = [];

        if (empty($words)) {
            return $chunks;
        }

        $start = 0;

        while ($start < count($words)) {
            $slice = array_slice($words, $start, $size);
            $chunks[] = implode(' ', $slice);

            if (count($words) <= $size + $start) {
                break;
            }

            $start += ($size - $overlap);
        }

        return $chunks;
    }
}
