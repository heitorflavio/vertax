<?php

namespace App\Demo\Ingestion;

use App\Demo\Merge\DemoMergeService;
use App\Demo\Providers\DemoProviderRegistry;
use App\Models\DemoIngestionRun;
use App\Models\DemoRecord;
use App\Models\DemoRecordSource;
use App\Models\DemoSource;
use Carbon\CarbonInterface;
use Throwable;

class DemoIngestionService
{
    public function __construct(
        private readonly DemoProviderRegistry $providers,
        private readonly DemoMergeService $merger,
    ) {}

    /**
     * Simula a coleta em APIs de terceiros, grava o payload bruto e
     * reconstrói a base canônica via merge multi-fonte.
     *
     * @param  list<string>|null  $sourceSlugs
     */
    public function run(?array $sourceSlugs = null): DemoIngestionRun
    {
        $startedAt = now();
        $run = DemoIngestionRun::create([
            'status' => 'running',
            'started_at' => $startedAt,
        ]);

        try {
            $sources = DemoSource::query()
                ->where('is_active', true)
                ->when($sourceSlugs !== null, fn ($query) => $query->whereIn('slug', $sourceSlugs))
                ->orderBy('reliability', 'desc')
                ->get();

            $payloadsIngested = 0;

            foreach ($sources as $source) {
                $provider = $this->providers->for($source->slug);
                $payloads = $provider->fetch();

                foreach ($payloads as $payload) {
                    $this->store($source, $payload);
                    $payloadsIngested++;
                }

                // Remove o que a fonte deixou de devolver (ex.: ID renomeado).
                // Uma resposta vazia não apaga nada, para uma falha do provedor
                // não zerar a base.
                if ($payloads !== []) {
                    DemoRecordSource::query()
                        ->where('demo_source_id', $source->id)
                        ->whereNotIn('public_id', array_column($payloads, 'public_id'))
                        ->delete();
                }
            }

            $publicIds = DemoRecordSource::query()
                ->distinct()
                ->orderBy('public_id')
                ->pluck('public_id')
                ->map(fn (mixed $publicId): string => (string) $publicId)
                ->all();

            // Registros que ficaram sem nenhuma fonte deixam de existir
            // (consultas saem junto, por cascade).
            DemoRecord::query()->whereNotIn('public_id', $publicIds)->delete();

            $recordsMerged = count($this->mergeRecords($publicIds));

            $finishedAt = now();

            $run->update([
                'status' => 'completed',
                'sources_synced' => $sources->count(),
                'payloads_ingested' => $payloadsIngested,
                'records_merged' => $recordsMerged,
                'duration_ms' => (int) $startedAt->diffInMilliseconds($finishedAt),
                'message' => sprintf(
                    '%d fonte(s), %d payload(s) e %d registro(s) mesclado(s).',
                    $sources->count(),
                    $payloadsIngested,
                    $recordsMerged,
                ),
                'finished_at' => $finishedAt,
            ]);

            return $run->fresh();
        } catch (Throwable $exception) {
            $run->update([
                'status' => 'failed',
                'message' => $exception->getMessage(),
                'duration_ms' => (int) $startedAt->diffInMilliseconds(now()),
                'finished_at' => now(),
            ]);

            throw $exception;
        }
    }

    /**
     * Grava (ou atualiza) o payload bruto que uma fonte devolveu para um
     * registro. Usado pela ingestão completa e pela busca nas fontes.
     *
     * @param  array{public_id: string, external_ref: string|null, fetched_at: CarbonInterface, payload: array<string, mixed>}  $payload
     */
    public function store(DemoSource $source, array $payload): void
    {
        DemoRecordSource::updateOrCreate(
            [
                'public_id' => $payload['public_id'],
                'demo_source_id' => $source->id,
            ],
            [
                'external_ref' => $payload['external_ref'],
                'payload' => $payload['payload'],
                'reliability_score' => $source->reliability,
                'fetched_at' => $payload['fetched_at'],
            ],
        );
    }

    /**
     * Mescla os payloads gravados de cada ID num registro canônico, com score
     * de confiança e os campos que cada fonte forneceu.
     *
     * @param  array<string>  $publicIds
     * @return array<string, DemoRecord> registros por public_id
     */
    public function mergeRecords(array $publicIds): array
    {
        $records = [];

        foreach ($publicIds as $publicId) {
            $merged = $this->merger->merge($publicId);

            $record = DemoRecord::updateOrCreate(
                ['public_id' => $publicId],
                [
                    ...$merged['fields'],
                    'hidden_data' => $merged['hidden_data'],
                    'trust_score' => $merged['trust_score'],
                    'trust_label' => $merged['trust_label'],
                    'trust_breakdown' => $merged['trust_breakdown'],
                    'sources_count' => $merged['sources_count'],
                ],
            );

            DemoRecordSource::query()
                ->where('public_id', $publicId)
                ->get()
                ->each(function (DemoRecordSource $row) use ($record, $merged): void {
                    $row->update([
                        'demo_record_id' => $record->id,
                        'contributed_fields' => $merged['contributions'][$row->id] ?? [],
                    ]);
                });

            $records[$publicId] = $record;
        }

        return $records;
    }
}
