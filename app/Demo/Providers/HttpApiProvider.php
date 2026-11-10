<?php

namespace App\Demo\Providers;

use App\Demo\DemoConfig;
use App\Demo\Search\DemoSearchNormalizer;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;

class HttpApiProvider implements DemoProvider
{
    /**
     * @param  array<string, mixed>  $apiConfig
     */
    public function __construct(
        private readonly string $sourceSlug,
        private readonly array $apiConfig,
        private readonly DemoSearchNormalizer $normalizer,
    ) {}

    public function slug(): string
    {
        return $this->sourceSlug;
    }

    public function fetch(): array
    {
        $endpoint = $this->apiConfig['endpoints']['fetch'] ?? null;

        if ($endpoint === null) {
            return [];
        }

        $response = $this->client()->send(
            $endpoint['method'] ?? 'GET',
            $endpoint['path'] ?? '/',
        )->throw();

        $items = $this->extractItems($response->json(), $endpoint['payload_root'] ?? null);

        return $this->mapPayloads($items, $endpoint['id_field'] ?? 'id');
    }

    public function search(string $type, string $term, array $filters = []): array
    {
        $endpoint = $this->apiConfig['endpoints']['search'] ?? null;

        if ($endpoint === null) {
            return $this->searchLocally($type, $term, $filters);
        }

        $queryParam = $endpoint['query_param'] ?? 'q';
        $typeParam = $endpoint['type_param'] ?? null;

        $query = [$queryParam => $term];

        if ($typeParam !== null) {
            $query[$typeParam] = $type;
        }

        if (isset($filters['birth_date']) && isset($endpoint['birth_date_param'])) {
            $query[$endpoint['birth_date_param']] = $filters['birth_date'];
        }

        $response = $this->client()->send(
            $endpoint['method'] ?? 'GET',
            $endpoint['path'] ?? '/search',
            ['query' => $query],
        )->throw();

        $items = $this->extractItems($response->json(), $endpoint['payload_root'] ?? null);
        $payloads = $this->mapPayloads($items, $endpoint['id_field'] ?? 'id');

        if (! isset($endpoint['filters_server_side']) || ! $endpoint['filters_server_side']) {
            $payloads = $this->applyLocalFilters($payloads, $type, $term, $filters);
        }

        return $payloads;
    }

    private function client(): PendingRequest
    {
        $baseUrl = $this->apiConfig['base_url'] ?? '';
        $timeout = $this->apiConfig['timeout'] ?? 30;
        $headers = $this->apiConfig['headers'] ?? ['Accept' => 'application/json'];

        $client = Http::baseUrl($baseUrl)
            ->timeout($timeout)
            ->withHeaders($headers);

        return $this->applyAuth($client);
    }

    private function applyAuth(PendingRequest $client): PendingRequest
    {
        $auth = $this->apiConfig['auth'] ?? null;

        if ($auth === null || ($auth['type'] ?? 'none') === 'none') {
            return $client;
        }

        return match ($auth['type']) {
            'bearer' => $client->withToken($this->resolveSecret($auth['token'] ?? '')),
            'api_key' => $client->withHeaders([
                ($auth['header'] ?? 'X-Api-Key') => $this->resolveSecret($auth['key'] ?? ''),
            ]),
            'basic' => $client->withBasicAuth(
                $this->resolveSecret($auth['username'] ?? ''),
                $this->resolveSecret($auth['password'] ?? ''),
            ),
            default => $client,
        };
    }

    /**
     * Resolve valores com prefixo "env:" para variáveis de ambiente,
     * evitando guardar segredos no banco.
     */
    private function resolveSecret(string $value): string
    {
        if (str_starts_with($value, 'env:')) {
            return (string) env(substr($value, 4), '');
        }

        return $value;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function extractItems(mixed $json, ?string $payloadRoot): array
    {
        if (! is_array($json)) {
            return [];
        }

        if ($payloadRoot !== null && $payloadRoot !== '') {
            $items = Arr::get($json, $payloadRoot, []);
        } else {
            $items = $json;
        }

        return is_array($items) ? array_values($items) : [];
    }

    /**
     * @param  list<array<string, mixed>>  $items
     * @return list<array{public_id: string, external_ref: string|null, fetched_at: \Carbon\CarbonInterface, payload: array<string, mixed>}>
     */
    private function mapPayloads(array $items, string $idField): array
    {
        $fieldMap = $this->apiConfig['field_map'] ?? [];
        $refField = $this->apiConfig['external_ref_field'] ?? null;
        $idPrefix = $this->apiConfig['id_prefix'] ?? 'EXT';
        $payloads = [];
        $counter = 1;

        foreach ($items as $item) {
            if (! is_array($item)) {
                continue;
            }

            $externalId = (string) ($item[$idField] ?? '');
            $publicId = $externalId !== ''
                ? strtoupper($idPrefix).'-'.str_pad((string) $counter, 4, '0', STR_PAD_LEFT)
                : strtoupper($idPrefix).'-'.str_pad((string) $counter, 4, '0', STR_PAD_LEFT);

            $payload = $this->mapFields($item, $fieldMap);

            $payloads[] = [
                'public_id' => $publicId,
                'external_ref' => $refField !== null ? ($item[$refField] ?? $externalId) : $externalId,
                'fetched_at' => now(),
                'payload' => $payload,
            ];

            $counter++;
        }

        return $payloads;
    }

    /**
     * @param  array<string, mixed>  $item
     * @param  array<string, string>  $fieldMap  externo => interno
     * @return array<string, mixed>
     */
    private function mapFields(array $item, array $fieldMap): array
    {
        if ($fieldMap === []) {
            return $item;
        }

        $mapped = [];

        foreach ($fieldMap as $externalKey => $internalKey) {
            $value = Arr::get($item, $externalKey);

            if ($value !== null) {
                $mapped[$internalKey] = $value;
            }
        }

        return $mapped;
    }

    /**
     * Quando a API não tem endpoint de busca, faz fetch completo e filtra
     * localmente (igual ao SeedJsonProvider).
     *
     * @param  array{birth_date?: string}  $filters
     * @return list<array{public_id: string, external_ref: string|null, fetched_at: \Carbon\CarbonInterface, payload: array<string, mixed>}>
     */
    private function searchLocally(string $type, string $term, array $filters): array
    {
        $field = DemoConfig::SEARCH_TYPE_FIELDS[$type] ?? null;

        if ($field === null) {
            return [];
        }

        return array_values(array_filter(
            $this->fetch(),
            fn (array $item): bool => $this->normalizer->matches($type, $item['payload'][$field] ?? null, $term)
                && $this->passesLocalFilters($item['payload'], $filters),
        ));
    }

    /**
     * Filtra resultados de uma API que não filtra por conta própria.
     *
     * @param  list<array{public_id: string, external_ref: string|null, fetched_at: \Carbon\CarbonInterface, payload: array<string, mixed>}>  $payloads
     * @param  array{birth_date?: string}  $filters
     * @return list<array{public_id: string, external_ref: string|null, fetched_at: \Carbon\CarbonInterface, payload: array<string, mixed>}>
     */
    private function applyLocalFilters(array $payloads, string $type, string $term, array $filters): array
    {
        $field = DemoConfig::SEARCH_TYPE_FIELDS[$type] ?? null;

        return array_values(array_filter($payloads, function (array $item) use ($type, $term, $field, $filters): bool {
            if ($field !== null && ! $this->normalizer->matches($type, $item['payload'][$field] ?? null, $term)) {
                return false;
            }

            return $this->passesLocalFilters($item['payload'], $filters);
        }));
    }

    /**
     * @param  array<string, mixed>  $payload
     * @param  array{birth_date?: string}  $filters
     */
    private function passesLocalFilters(array $payload, array $filters): bool
    {
        $birthDate = $filters['birth_date'] ?? null;
        $payloadBirthDate = $this->normalizer->date($payload['birth_date'] ?? null);

        return $birthDate === null || $payloadBirthDate === null || $payloadBirthDate === $birthDate;
    }
}
