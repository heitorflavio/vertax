<?php

namespace App\Demo\Merge;

use App\Demo\DemoConfig;
use App\Models\DemoRecordSource;
use Illuminate\Support\Collection;
use RuntimeException;

class DemoMergeService
{
    public function __construct(private readonly DemoTrustScoreService $trustScore) {}

    /**
     * Mescla os payloads das várias fontes em um registro canônico.
     *
     * Estratégia: vence o valor da fonte de maior confiabilidade; a consistência
     * é medida pela concordância normalizada entre todas as fontes.
     *
     * @return array{
     *     fields: array<string, mixed>,
     *     hidden_data: array<string, mixed>|null,
     *     trust_score: int,
     *     trust_label: string,
     *     trust_breakdown: array<string, mixed>,
     *     sources_count: int,
     *     contributions: array<int, list<string>>
     * }
     */
    public function merge(string $publicId): array
    {
        /** @var Collection<int, DemoRecordSource> $sources */
        $sources = DemoRecordSource::query()
            ->with('source')
            ->where('public_id', $publicId)
            ->get()
            ->sortByDesc(fn (DemoRecordSource $row): array => [
                (int) $row->source->reliability,
                (int) $row->fetched_at->getTimestamp(),
            ])
            ->values();

        if ($sources->isEmpty()) {
            throw new RuntimeException("Nenhuma fonte encontrada para {$publicId}.");
        }

        $valuesByField = [];
        $chosenFields = [];
        $contributions = [];
        $hiddenData = [];

        foreach (DemoConfig::MERGE_FIELDS as $field) {
            foreach ($sources as $row) {
                $value = data_get($row->payload, $field);

                if ($value === null || $value === '') {
                    continue;
                }

                $valuesByField[$field][] = $this->normalize($field, $value);

                if (! array_key_exists($field, $chosenFields)) {
                    $chosenFields[$field] = $value;
                    $contributions[$row->id][] = $field;
                }
            }
        }

        foreach ($sources as $row) {
            foreach ((array) data_get($row->payload, '_hidden', []) as $key => $value) {
                if (! array_key_exists($key, $hiddenData)) {
                    $hiddenData[$key] = $value;
                }
            }
        }

        $trust = $this->trustScore->evaluate($valuesByField, $sources);

        $fields = array_replace(
            array_fill_keys(DemoConfig::MERGE_FIELDS, null),
            $chosenFields,
        );

        return [
            'fields' => $fields,
            'hidden_data' => $hiddenData === [] ? null : $hiddenData,
            'trust_score' => $trust['score'],
            'trust_label' => $trust['label'],
            'trust_breakdown' => $trust['breakdown'],
            'sources_count' => $sources->count(),
            'contributions' => $contributions,
        ];
    }

    private function normalize(string $field, mixed $value): string
    {
        $string = trim((string) $value);

        return match ($field) {
            'email' => mb_strtolower($string),
            'state' => mb_strtoupper($string),
            'cpf', 'cnh', 'cnpj', 'phone' => preg_replace('/\D+/', '', $string) ?? $string,
            'birth_date' => str_contains($string, '-')
                ? $string
                : (string) strtotime($string),
            default => mb_strtolower((string) preg_replace('/\s+/', ' ', $string)),
        };
    }
}
