<?php

namespace App\Demo\Providers;

use App\Demo\Data\DemoSeedRepository;
use App\Demo\Search\DemoSearchNormalizer;
use App\Models\DemoSource;
use InvalidArgumentException;

class DemoProviderRegistry
{
    /**
     * Provedores registrados explicitamente, por slug da fonte. Têm prioridade
     * sobre a resolução automática (API ou seed JSON).
     *
     * @var array<string, DemoProvider>
     */
    private array $providers = [];

    public function __construct(
        private readonly DemoSeedRepository $repository,
        private readonly DemoSearchNormalizer $normalizer,
    ) {}

    /**
     * Usa outro provedor para uma fonte (ex.: um cliente HTTP, ou um dublê
     * nos testes).
     */
    public function register(string $slug, DemoProvider $provider): void
    {
        $this->providers[$slug] = $provider;
    }

    public function for(string $slug): DemoProvider
    {
        if (isset($this->providers[$slug])) {
            return $this->providers[$slug];
        }

        $source = DemoSource::query()->where('slug', $slug)->first();

        if ($source !== null && ! empty($source->api_config)) {
            return new HttpApiProvider($slug, $source->api_config, $this->normalizer);
        }

        $known = array_column($this->repository->sources(), 'slug');

        if ($source === null && ! in_array($slug, $known, true)) {
            throw new InvalidArgumentException("Provedor demo desconhecido: {$slug}");
        }

        return new SeedJsonProvider($slug, $this->repository, $this->normalizer);
    }

    /**
     * @return list<string>
     */
    public function available(): array
    {
        return array_values(array_filter(array_column($this->repository->sources(), 'slug')));
    }
}
