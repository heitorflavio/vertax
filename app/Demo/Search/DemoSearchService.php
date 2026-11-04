<?php

namespace App\Demo\Search;

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
     * Detecta o tipo do termo (ID, documento, e-mail ou nome) e procura no índice.
     *
     * @return array{kind: string, matches: list<array{record: DemoRecord, matched_by: string}>}
     */
    public function search(string $query, int $limit = 20): array
    {
        $query = trim($query);

        if (preg_match('/^DEMO-\d+$/i', $query) === 1) {
            $record = DemoRecord::query()->publicId($query)->first();

            return [
                'kind' => 'id',
                'matches' => $record === null ? [] : [['record' => $record, 'matched_by' => 'id']],
            ];
        }

        if (str_contains($query, '@')) {
            $email = mb_strtolower($query);

            return [
                'kind' => 'email',
                'matches' => $this->findTerms(function (Builder $terms) use ($email): void {
                    $terms->where('type', 'email')->where('value', $email);
                }, $limit),
            ];
        }

        // Só dígitos e pontuação: CPF, CNH, CNPJ ou telefone.
        if (preg_match('/^[\d\s.\-\/()+]+$/', $query) === 1) {
            return ['kind' => 'document', 'matches' => $this->documentMatches($query, $limit)];
        }

        return ['kind' => 'name', 'matches' => $this->nameMatches($query, $limit)];
    }

    /**
     * @return list<array{record: DemoRecord, matched_by: string}>
     */
    private function documentMatches(string $query, int $limit): array
    {
        $digits = $this->digits($query);
        $phone = $this->phoneDigits($query);

        if ($digits === '') {
            return [];
        }

        return $this->findTerms(function (Builder $terms) use ($digits, $phone): void {
            $terms
                ->where(function (Builder $terms) use ($digits): void {
                    $terms->whereIn('type', ['cpf', 'cnh', 'cnpj'])->where('value', $digits);
                })
                ->orWhere(function (Builder $terms) use ($phone): void {
                    $terms->where('type', 'phone')->where('value', $phone);
                });
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
