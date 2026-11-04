<?php

namespace App\Demo\Merge;

use App\Demo\DemoConfig;
use App\Models\DemoRecordSource;
use Illuminate\Support\Collection;

class DemoTrustScoreService
{
    /**
     * Avalia a qualidade do resultado depois da consulta/mesclagem.
     *
     * @param  array<string, list<string>>  $valuesByField
     * @param  Collection<int, DemoRecordSource>  $sources
     * @return array{score: int, label: string, breakdown: array<string, mixed>}
     */
    public function evaluate(array $valuesByField, Collection $sources): array
    {
        $agreements = [];
        $missingFields = [];
        $requiredFields = array_values(array_diff(DemoConfig::MERGE_FIELDS, DemoConfig::OPTIONAL_FIELDS));

        foreach (DemoConfig::MERGE_FIELDS as $field) {
            $contributors = count($valuesByField[$field] ?? []);

            $values = array_values(array_unique(array_filter(
                $valuesByField[$field] ?? [],
                fn (string $value): bool => $value !== '',
            )));

            if ($values === []) {
                // Campo opcional ausente (ex.: CNPJ) não penaliza a completude.
                if (in_array($field, $requiredFields, true)) {
                    $missingFields[] = $field;
                }

                continue;
            }

            // Só mede consistência quando duas ou mais fontes cobrem o campo.
            if ($contributors >= 2 || $sources->count() === 1) {
                $agreements[$field] = 1 / count($values);
            }
        }

        $filled = count($requiredFields) - count($missingFields);
        $completeness = ($filled / count($requiredFields)) * 100;

        $consistency = $agreements === []
            ? 70.0
            : (array_sum($agreements) / count($agreements)) * 100;

        $sourceQuality = $sources->isEmpty()
            ? 0.0
            : (float) $sources->avg('reliability_score');

        $newest = $sources
            ->pluck('fetched_at')
            ->filter()
            ->sortDesc()
            ->first();

        $ageDays = $newest === null ? 365 : max(0, (int) $newest->diffInDays(now()));
        $freshness = max(40.0, 100.0 - ($ageDays * 2.5));

        $score = (int) round(
            ($sourceQuality * DemoConfig::TRUST_WEIGHTS['source_quality'])
            + ($completeness * DemoConfig::TRUST_WEIGHTS['completeness'])
            + ($consistency * DemoConfig::TRUST_WEIGHTS['consistency'])
            + ($freshness * DemoConfig::TRUST_WEIGHTS['freshness'])
        );

        $label = DemoConfig::trustLabel($score);

        return [
            'score' => $score,
            'label' => $label,
            'breakdown' => [
                'source_quality' => round($sourceQuality, 1),
                'completeness' => round($completeness, 1),
                'consistency' => round($consistency, 1),
                'freshness' => round($freshness, 1),
                'weights' => DemoConfig::TRUST_WEIGHTS,
                'sources_evaluated' => $sources->count(),
                'fields_filled' => $filled,
                'fields_total' => count($requiredFields),
                'agreement_by_field' => array_map(
                    fn (float $agreement): float => round($agreement * 100, 1),
                    $agreements,
                ),
                'missing_fields' => array_map(
                    fn (string $field): string => DemoConfig::FIELD_LABELS[$field],
                    $missingFields,
                ),
                'newest_payload_age_days' => $ageDays,
                'explanation' => DemoConfig::trustExplanation($label),
                'evaluated_at' => now()->toIso8601String(),
            ],
        ];
    }
}
