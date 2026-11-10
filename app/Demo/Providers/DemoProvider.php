<?php

namespace App\Demo\Providers;

use Carbon\CarbonInterface;

interface DemoProvider
{
    public function slug(): string;

    /**
     * Busca todos os payloads fictícios do provedor terceiro simulado
     * (ingestão completa).
     *
     * @return list<array{public_id: string, external_ref: string|null, fetched_at: CarbonInterface, payload: array<string, mixed>}>
     */
    public function fetch(): array;

    /**
     * Consulta o provedor por um campo, no momento da busca. Só é chamado com
     * tipos que a fonte declara em search_types. Dados ocultos (_hidden) não
     * podem ser pesquisáveis.
     *
     * $filters refina a busca (busca composta, ex.: nome + nascimento). A
     * fonte aplica os filtros que conhece, para devolver menos registros; o
     * que ela não souber filtrar, a busca confere depois.
     *
     * @param  string  $type  chave de DemoConfig::SEARCH_TYPE_FIELDS
     * @param  array{birth_date?: string}  $filters  birth_date em Y-m-d
     * @return list<array{public_id: string, external_ref: string|null, fetched_at: CarbonInterface, payload: array<string, mixed>}>
     */
    public function search(string $type, string $term, array $filters = []): array;
}
