<?php

namespace App\Demo\Search;

use App\Demo\DemoConfig;
use App\Models\DemoRecord;
use App\Models\DemoSearchTerm;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DemoSearchService
{
    /**
     * Reconstrói o índice de busca de um registro a partir dos campos visíveis.
     * Dados ocultos (hidden_data) nunca entram no índice.
     */
    public function index(DemoRecord $record): void
    {
        $terms = array_filter([
            'name' => $this->normalizeName($record->full_name),
            'cpf' => $this->digits($record->cpf),
            'cnh' => $this->digits($record->cnh),
            'cnpj' => $this->digits($record->cnpj),
            'email' => mb_strtolower(trim((string) $record->email)),
            'phone' => $this->phoneDigits($record->phone),
        ], fn (string $value): bool => $value !== '');

        DB::transaction(function () use ($record, $terms): void {
            $record->searchTerms()->delete();

            foreach ($terms as $type => $value) {
                $record->searchTerms()->create(['type' => $type, 'value' => $value]);
            }
        });
    }

    /**
     * Procura no índice. Com $type (chave de DemoConfig::SEARCH_TYPE_LABELS)
     * a busca fica restrita àquele campo; sem ele, o tipo é detectado pelo termo.
     *
     * @return array{kind: string, matches: list<array{record: DemoRecord, matched_by: string}>}
     */
    public function search(string $query, ?string $type = null, int $limit = 20): array
    {
        $query = trim($query);

        return match ($type ?? $this->detectType($query)) {
            'id' => ['kind' => 'id', 'matches' => $this->idMatches($query)],
            'email' => ['kind' => 'email', 'matches' => $this->emailMatches($query, $limit)],
            'document' => ['kind' => 'document', 'matches' => $this->documentMatches($query, ['cpf', 'cnh', 'cnpj', 'phone'], $limit)],
            'cpf', 'cnh', 'cnpj', 'phone' => ['kind' => 'document', 'matches' => $this->documentMatches($query, [$type], $limit)],
            default => ['kind' => 'name', 'matches' => $this->nameMatches($query, $limit)],
        };
    }

    /**
     * ID (REG-0001), e-mail, documento/telefone (só dígitos e pontuação) ou nome.
     */
    private function detectType(string $query): string
    {
        return match (true) {
            preg_match('/^'.DemoConfig::PUBLIC_ID_PREFIX.'-\d+$/i', $query) === 1 => 'id',
            str_contains($query, '@') => 'email',
            preg_match('/^[\d\s.\-\/()+]+$/', $query) === 1 => 'document',
            default => 'name',
        };
    }

    /**
     * @return list<array{record: DemoRecord, matched_by: string}>
     */
    private function idMatches(string $query): array
    {
        $record = DemoRecord::query()->publicId($query)->first();

        return $record === null ? [] : [['record' => $record, 'matched_by' => 'id']];
    }

    /**
     * @return list<array{record: DemoRecord, matched_by: string}>
     */
    private function emailMatches(string $query, int $limit): array
    {
        $email = mb_strtolower($query);

        return $this->findTerms(function (Builder $terms) use ($email): void {
            $terms->where('type', 'email')->where('value', $email);
        }, $limit);
    }

    /**
     * Compara só os dígitos. Telefone ignora o +55.
     *
     * @param  list<string>  $types  subconjunto de cpf, cnh, cnpj e phone
     * @return list<array{record: DemoRecord, matched_by: string}>
     */
    private function documentMatches(string $query, array $types, int $limit): array
    {
        $digits = $this->digits($query);
        $phone = $this->phoneDigits($query);
        $documentTypes = array_values(array_intersect($types, ['cpf', 'cnh', 'cnpj']));
        $searchPhone = in_array('phone', $types, true);

        if ($digits === '') {
            return [];
        }

        return $this->findTerms(function (Builder $terms) use ($digits, $phone, $documentTypes, $searchPhone): void {
            $terms
                ->where(function (Builder $terms) use ($digits, $documentTypes): void {
                    $terms->whereIn('type', $documentTypes)->where('value', $digits);
                })
                ->when($searchPhone, fn (Builder $terms) => $terms->orWhere(function (Builder $terms) use ($phone): void {
                    $terms->where('type', 'phone')->where('value', $phone);
                }));
        }, $limit);
    }

    /**
     * Busca parcial por nome: todas as palavras precisam aparecer, sem
     * diferenciar acento ou maiúsculas.
     *
     * @return list<array{record: DemoRecord, matched_by: string}>
     */
    private function nameMatches(string $query, int $limit): array
    {
        $tokens = array_values(array_filter(explode(' ', $this->normalizeName($query))));

        if ($tokens === []) {
            return [];
        }

        return $this->findTerms(function (Builder $terms) use ($tokens): void {
            $terms->where('type', 'name');

            foreach ($tokens as $token) {
                $terms->where('value', 'like', "%{$token}%");
            }
        }, $limit);
    }

    /**
     * @param  Closure(Builder<DemoSearchTerm>): void  $constraints
     * @return list<array{record: DemoRecord, matched_by: string}>
     */
    private function findTerms(Closure $constraints, int $limit): array
    {
        $query = DemoSearchTerm::query()->with('record');
        $constraints($query);

        $matches = [];

        foreach ($query->orderBy('demo_record_id')->limit($limit)->get() as $term) {
            $record = $term->record;

            if ($record === null || isset($matches[$record->id])) {
                continue;
            }

            $matches[$record->id] = ['record' => $record, 'matched_by' => $term->type];
        }

        return array_values($matches);
    }

    private function normalizeName(?string $value): string
    {
        return Str::of((string) $value)
            ->ascii()
            ->lower()
            ->replaceMatches('/[^a-z0-9]+/', ' ')
            ->squish()
            ->toString();
    }

    private function digits(?string $value): string
    {
        return (string) preg_replace('/\D+/', '', (string) $value);
    }

    /**
     * Telefone sem o código do país (+55), para "11 90000-0001" e
     * "+55 11 90000-0001" caírem no mesmo termo.
     */
    private function phoneDigits(?string $value): string
    {
        $digits = $this->digits($value);

        if (strlen($digits) >= 12 && str_starts_with($digits, '55')) {
            return substr($digits, 2);
        }

        return $digits;
    }
}
