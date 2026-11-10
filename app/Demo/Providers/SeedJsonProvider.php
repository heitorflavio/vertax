<?php

namespace App\Demo\Providers;

use App\Demo\Data\DemoSeedRepository;
use App\Demo\DemoConfig;
use App\Demo\Search\DemoSearchNormalizer;

class SeedJsonProvider implements DemoProvider
{
    public function __construct(
        private readonly string $slug,
        private readonly DemoSeedRepository $repository,
        private readonly DemoSearchNormalizer $normalizer,
    ) {}

    public function slug(): string
    {
        return $this->slug;
    }

    public function fetch(): array
    {
        return $this->repository->payloadsForSource($this->slug);
    }

    /**
     * Filtra os payloads desta fonte no seed. Só olha os campos de primeiro
     * nível do payload, então o bloco _hidden nunca é pesquisado.
     */
    public function search(string $type, string $term, array $filters = []): array
    {
        $field = DemoConfig::SEARCH_TYPE_FIELDS[$type] ?? null;

        if ($field === null) {
            return [];
        }

        return array_values(array_filter(
            $this->repository->payloadsForSource($this->slug),
            fn (array $item): bool => $this->normalizer->matches($type, $item['payload'][$field] ?? null, $term)
                && $this->passesFilters($item['payload'], $filters),
        ));
    }

    /**
     * A fonte só filtra pelo que tem: payload sem data de nascimento passa, e
     * a busca confere a data depois.
     *
     * @param  array<string, mixed>  $payload
     * @param  array{birth_date?: string}  $filters
     */
    private function passesFilters(array $payload, array $filters): bool
    {
        $birthDate = $filters['birth_date'] ?? null;
        $payloadBirthDate = $this->normalizer->date($payload['birth_date'] ?? null);

        return $birthDate === null || $payloadBirthDate === null || $payloadBirthDate === $birthDate;
    }
}
