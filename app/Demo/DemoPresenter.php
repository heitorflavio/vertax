<?php

namespace App\Demo;

use App\Models\DemoConsultation;
use App\Models\DemoRecord;
use Illuminate\Support\Str;

class DemoPresenter
{
    /**
     * @return array<string, mixed>
     */
    public function summary(DemoRecord $record): array
    {
        return [
            'public_id' => $record->public_id,
            'full_name' => $record->full_name,
            'cpf_masked' => $record->cpf_masked,
            'birth_date' => $this->dateValue($record, 'birth_date')?->format('Y-m-d'),
            'birth_date_formatted' => $this->dateValue($record, 'birth_date')?->format('d/m/Y'),
            'trust_score' => $record->trust_score,
            'trust_label' => $record->trust_label,
            'sources_count' => $record->sources_count,
            'consultations_count' => $record->consultations_count,
            'last_consulted_at' => $this->dateValue($record, 'last_consulted_at')?->format(\DateTimeInterface::ATOM),
            'url' => route('demo.show', ['publicId' => $record->public_id], false),
        ];
    }

    /**
     * Resultado de busca: resumo (CPF mascarado) + campo que casou com o termo.
     *
     * @return array<string, mixed>
     */
    public function searchResult(DemoRecord $record, string $matchedBy): array
    {
        return [
            ...$this->summary($record),
            'matched_by' => $matchedBy,
            'matched_by_label' => DemoConfig::SEARCH_TYPE_LABELS[$matchedBy] ?? $matchedBy,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function detail(DemoRecord $record, bool $includeHidden, ?DemoConsultation $consultation = null): array
    {
        $hiddenData = $this->arrayAttribute($record, 'hidden_data');

        return [
            ...$this->summary($record),
            'cpf' => $record->cpf,
            'fields' => $this->fields($record),
            'basic_fields' => array_values(array_filter(
                $this->fields($record),
                fn (array $field): bool => in_array($field['key'], DemoConfig::BASIC_FIELDS, true),
            )),
            'hidden' => $includeHidden ? $this->hiddenFields($hiddenData) : null,
            'hidden_locked' => ! $includeHidden,
            'hidden_fields_count' => count($hiddenData),
            'trust' => $this->trust($record),
            'sources' => $this->sources($record),
            'consultation' => $consultation === null ? null : [
                'id' => $consultation->id,
                'evaluated_at' => $consultation->created_at?->toIso8601String(),
                'trust_score' => $consultation->trust_score,
                'plan_active' => $consultation->plan_active,
            ],
        ];
    }

    /**
     * @return list<array{key: string, label: string, value: string|null}>
     */
    public function fields(DemoRecord $record): array
    {
        $fields = [];

        foreach (DemoConfig::FIELD_LABELS as $key => $label) {
            $value = $record->getAttribute($key);

            if ($value instanceof \DateTimeInterface) {
                $value = $value->format('d/m/Y');
            }

            $fields[] = [
                'key' => $key,
                'label' => $label,
                'value' => $value === null || $value === '' ? null : (string) $value,
            ];
        }

        return $fields;
    }

    /**
     * @param  array<string, mixed>  $hiddenData
     * @return list<array{key: string, label: string, value: string}>
     */
    public function hiddenFields(array $hiddenData): array
    {
        $fields = [];

        foreach (DemoConfig::HIDDEN_LABELS as $key => $label) {
            if (! array_key_exists($key, $hiddenData)) {
                continue;
            }

            $fields[] = [
                'key' => $key,
                'label' => $label,
                'value' => (string) $hiddenData[$key],
            ];
        }

        foreach ($hiddenData as $key => $value) {
            if (array_key_exists($key, DemoConfig::HIDDEN_LABELS)) {
                continue;
            }

            $fields[] = [
                'key' => (string) $key,
                'label' => Str::headline((string) $key),
                'value' => (string) $value,
            ];
        }

        return $fields;
    }

    /**
     * @return array<string, mixed>
     */
    public function trust(DemoRecord $record): array
    {
        $breakdown = $this->arrayAttribute($record, 'trust_breakdown');

        return [
            'score' => $record->trust_score,
            'label' => $record->trust_label,
            'breakdown' => [
                'source_quality' => $breakdown['source_quality'] ?? 0,
                'completeness' => $breakdown['completeness'] ?? 0,
                'consistency' => $breakdown['consistency'] ?? 0,
                'freshness' => $breakdown['freshness'] ?? 0,
                'weights' => $breakdown['weights'] ?? DemoConfig::TRUST_WEIGHTS,
                'sources_evaluated' => $breakdown['sources_evaluated'] ?? $record->sources_count,
                'fields_filled' => $breakdown['fields_filled'] ?? 0,
                'fields_total' => $breakdown['fields_total'] ?? count(DemoConfig::MERGE_FIELDS) - count(DemoConfig::OPTIONAL_FIELDS),
                'agreement_by_field' => $breakdown['agreement_by_field'] ?? [],
                'missing_fields' => $breakdown['missing_fields'] ?? [],
                'newest_payload_age_days' => $breakdown['newest_payload_age_days'] ?? null,
                'explanation' => $breakdown['explanation'] ?? DemoConfig::trustExplanation($record->trust_label),
                'evaluated_at' => $breakdown['evaluated_at'] ?? null,
            ],
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function sources(DemoRecord $record): array
    {
        $sources = [];

        foreach ($record->recordSources()->with('source')->get() as $row) {
            $source = $row->source;
            $contributed = $row->contributed_fields;
            $contributed = is_array($contributed) ? array_values($contributed) : [];

            $sources[] = [
                'slug' => $source === null ? null : $source->slug,
                'name' => $source === null ? 'Fonte' : $source->name,
                'category' => $source === null ? null : $source->category,
                'reliability' => $row->reliability_score,
                'match_score' => $row->match_score,
                'fetched_at' => $row->fetched_at->toIso8601String(),
                'contributed_fields' => array_map(
                    fn (string $field): string => DemoConfig::FIELD_LABELS[$field] ?? $field,
                    $contributed,
                ),
            ];
        }

        return $sources;
    }

    private function dateValue(DemoRecord $record, string $key): ?\DateTimeInterface
    {
        $value = $record->getAttribute($key);

        return $value instanceof \DateTimeInterface ? $value : null;
    }

    /**
     * @return array<string, mixed>
     */
    private function arrayAttribute(DemoRecord $record, string $key): array
    {
        $value = $record->getAttribute($key);

        return is_array($value) ? $value : [];
    }
}
