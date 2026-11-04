<?php

namespace App\Demo\Providers;

use App\Demo\Data\DemoSeedRepository;
use InvalidArgumentException;

class DemoProviderRegistry
{
    public function __construct(private readonly DemoSeedRepository $repository) {}

    public function for(string $slug): DemoProvider
    {
        $known = array_column($this->repository->sources(), 'slug');

        if (! in_array($slug, $known, true)) {
            throw new InvalidArgumentException("Provedor demo desconhecido: {$slug}");
        }

        return new SeedJsonProvider($slug, $this->repository);
    }

    /**
     * @return list<string>
     */
    public function available(): array
    {
        return array_values(array_filter(array_column($this->repository->sources(), 'slug')));
    }
}
