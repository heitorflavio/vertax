<?php

namespace App\Console\Commands;

use App\Demo\Ingestion\DemoIngestionService;
use Illuminate\Console\Command;

class DemoIngestCommand extends Command
{
    protected $signature = 'demo:ingest {--source=* : Slugs de fontes específicas (opcional)}';

    protected $description = 'Simula a coleta em APIs de terceiros fictícias, grava os payloads e mescla os registros demo.';

    public function handle(DemoIngestionService $ingestion): int
    {
        $sources = $this->option('source');
        $sources = array_values(array_filter(
            $sources,
            fn (mixed $source): bool => is_string($source) && $source !== '',
        ));

        $run = $ingestion->run($sources === [] ? null : $sources);

        $this->info($run->message);
        $this->line(sprintf(
            'Status: %s | Duração: %s ms',
            $run->status,
            $run->duration_ms,
        ));

        return self::SUCCESS;
    }
}
