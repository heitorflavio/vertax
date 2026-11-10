<?php

namespace App\Demo\Search;

use App\Demo\Ingestion\DemoIngestionService;
use App\Demo\Providers\DemoProviderRegistry;
use App\Models\DemoRecord;
use App\Models\DemoSource;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Throwable;

/**
 * Busca em todas as fontes compatíveis: o termo vai, no momento da busca, para
 * cada fonte ativa que aceita o tipo escolhido (search_types). O que as fontes
 * devolvem é gravado e mesclado como na ingestão, e cada resultado informa em
 * quais fontes foi encontrado.
 *
 * Busca composta: filtros (ex.: data de nascimento) vão para as fontes, que
 * aplicam o que conseguem, e são conferidos de novo aqui antes de gravar.
 * Quem não passa no filtro não é gravado nem mostrado.
 *
 * @phpstan-type SourcePayload array{public_id: string, external_ref: string|null, fetched_at: CarbonInterface, payload: array<string, mixed>}
 * @phpstan-type SearchFilters array{birth_date?: string}
 * @phpstan-type FoundIn list<array{slug: string, name: string}>
 * @phpstan-type SearchMatch array{record: DemoRecord, found_in: FoundIn}
 * @phpstan-type SourceReport array{slug: string, name: string, status: string, hits: int, duration_ms: int}
 * @phpstan-type Hit array{found_in: array<string, string>, payloads: list<array{source: DemoSource, payload: SourcePayload}>}
 */
class DemoSearchService
{
    public function __construct(
        private readonly DemoProviderRegistry $providers,
        private readonly DemoIngestionService $ingestion,
        private readonly DemoSearchNormalizer $normalizer,
    ) {}

    /**
     * @param  string  $type  chave de DemoConfig::SEARCH_TYPE_LABELS
     * @param  SearchFilters  $filters  birth_date em Y-m-d
     * @return array{matches: list<SearchMatch>, sources: list<SourceReport>}
     */
    public function search(string $query, string $type, array $filters = [], int $limit = 20): array
    {
        $query = trim($query);

        /** @var array<string, Hit> $hits */
        $hits = [];
        $sources = [];

        foreach ($this->compatibleSources($type) as $source) {
            ['report' => $report, 'found' => $found] = $this->searchSource($source, $type, $query, $filters);
            $sources[] = $report;

            foreach ($found as $payload) {
                $publicId = $payload['public_id'];
                $hits[$publicId]['found_in'][$source->slug] = $source->name;
                $hits[$publicId]['payloads'][] = ['source' => $source, 'payload' => $payload];
            }
        }

        $hits = $this->applyFilters($hits, $filters);

        foreach ($hits as $hit) {
            foreach ($hit['payloads'] as ['source' => $source, 'payload' => $payload]) {
                $this->ingestion->store($source, $payload);
            }
        }

        // Quantos registros de cada fonte sobraram depois dos filtros.
        foreach ($sources as $index => $report) {
            $sources[$index]['hits'] = count(array_filter(
                $hits,
                fn (array $hit): bool => isset($hit['found_in'][$report['slug']]),
            ));
        }

        return [
            'matches' => $this->matches($hits, $filters, $limit),
            'sources' => $sources,
        ];
    }

    /**
     * Fontes ativas que aceitam o tipo, da mais confiável para a menos
     * confiável.
     *
     * @return Collection<int, DemoSource>
     */
    private function compatibleSources(string $type): Collection
    {
        return DemoSource::query()
            ->where('is_active', true)
            ->orderByDesc('reliability')
            ->get()
            ->filter(fn (DemoSource $source): bool => $source->supportsSearch($type))
            ->values();
    }

    /**
     * Consulta uma fonte. Se ela falhar, o erro fica no relatório e a busca
     * segue nas outras.
     *
     * @param  SearchFilters  $filters
     * @return array{report: SourceReport, found: list<SourcePayload>}
     */
    private function searchSource(DemoSource $source, string $type, string $query, array $filters): array
    {
        $startedAt = hrtime(true);
        $status = 'ok';

        try {
            $found = $this->providers->for($source->slug)->search($type, $query, $filters);
        } catch (Throwable $exception) {
            report($exception);
            $found = [];
            $status = 'error';
        }

        return [
            'report' => [
                'slug' => $source->slug,
                'name' => $source->name,
                'status' => $status,
                'hits' => 0,
                'duration_ms' => intdiv(hrtime(true) - $startedAt, 1_000_000),
            ],
            'found' => $found,
        ];
    }

    /**
     * Confere os filtros antes de gravar qualquer coisa. A data de nascimento
     * vem do payload mais confiável que a tiver; se nenhuma fonte desta busca
     * a devolveu, vale a do registro já mesclado. Sem data conhecida, o
     * registro não passa: a busca composta só mostra quem confere.
     *
     * @param  array<string, Hit>  $hits
     * @param  SearchFilters  $filters
     * @return array<string, Hit>
     */
    private function applyFilters(array $hits, array $filters): array
    {
        $birthDate = $filters['birth_date'] ?? null;

        if ($birthDate === null || $hits === []) {
            return $hits;
        }

        $storedBirthDates = DemoRecord::query()
            ->whereIn('public_id', array_map('strval', array_keys($hits)))
            ->get(['public_id', 'birth_date'])
            ->mapWithKeys(fn (DemoRecord $record): array => [
                $record->public_id => $record->birth_date->format('Y-m-d'),
            ]);

        return array_filter(
            $hits,
            function (array $hit, int|string $publicId) use ($birthDate, $storedBirthDates): bool {
                foreach ($hit['payloads'] as ['payload' => $payload]) {
                    $payloadBirthDate = $this->normalizer->date($payload['payload']['birth_date'] ?? null);

                    if ($payloadBirthDate !== null) {
                        return $payloadBirthDate === $birthDate;
                    }
                }

                return $storedBirthDates->get((string) $publicId) === $birthDate;
            },
            ARRAY_FILTER_USE_BOTH,
        );
    }

    /**
     * Mescla todos os registros encontrados (as fontes acabaram de devolver
     * dados novos) e devolve até $limit, por ID.
     *
     * @param  array<string, Hit>  $hits
     * @param  SearchFilters  $filters
     * @return list<SearchMatch>
     */
    private function matches(array $hits, array $filters, int $limit): array
    {
        if ($hits === []) {
            return [];
        }

        ksort($hits, SORT_STRING);
        $records = $this->ingestion->mergeRecords(array_map('strval', array_keys($hits)));
        $birthDate = $filters['birth_date'] ?? null;
        $matches = [];

        foreach ($hits as $publicId => $hit) {
            $record = $records[(string) $publicId];

            // O merge pode ter escolhido a data de uma fonte mais confiável que
            // não participou desta busca: o registro final também precisa bater.
            if ($birthDate !== null && $record->birth_date->format('Y-m-d') !== $birthDate) {
                continue;
            }

            $foundIn = [];

            foreach ($hit['found_in'] as $slug => $name) {
                $foundIn[] = ['slug' => $slug, 'name' => $name];
            }

            $matches[] = ['record' => $record, 'found_in' => $foundIn];

            if (count($matches) === $limit) {
                break;
            }
        }

        return $matches;
    }
}
