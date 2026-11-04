<?php

namespace App\Demo\Providers;

use Carbon\CarbonInterface;

interface DemoProvider
{
    public function slug(): string;

    /**
     * Busca os payloads fictícios do provedor terceiro simulado.
     *
     * @return list<array{public_id: string, external_ref: string|null, fetched_at: CarbonInterface, payload: array<string, mixed>}>
     */
    public function fetch(): array;
}
