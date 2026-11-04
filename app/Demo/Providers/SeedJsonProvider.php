<?php

namespace App\Demo\Providers;

use App\Demo\Data\DemoSeedRepository;

class SeedJsonProvider implements DemoProvider
{
    public function __construct(
        private readonly string $slug,
        private readonly DemoSeedRepository $repository,
    ) {}

    public function slug(): string
    {
        return $this->slug;
    }

    public function fetch(): array
    {
        return $this->repository->payloadsForSource($this->slug);
    }
}
