<?php

namespace App\Demo\Search;

use DateTimeImmutable;
use Illuminate\Support\Str;

/**
 * Compara o termo buscado com o valor que uma fonte guarda: nome sem acento
 * nem maiúsculas, documentos só com dígitos, telefone sem o +55.
 */
class DemoSearchNormalizer
{
    /**
     * O valor do campo casa com o termo? Nome é parcial (todas as palavras do
     * termo precisam aparecer); os demais tipos comparam o valor normalizado.
     *
     * @param  string  $type  chave de DemoConfig::SEARCH_TYPE_FIELDS
     */
    public function matches(string $type, mixed $value, string $term): bool
    {
        if (! is_scalar($value)) {
            return false;
        }

        $value = (string) $value;

        return match ($type) {
            'name' => $this->nameMatches($value, $term),
            'email' => $this->sameNonEmpty($this->email($value), $this->email($term)),
            'phone' => $this->sameNonEmpty($this->phone($value), $this->phone($term)),
            'cpf', 'cnh', 'cnpj' => $this->sameNonEmpty($this->digits($value), $this->digits($term)),
            default => false,
        };
    }

    public function name(?string $value): string
    {
        return Str::of((string) $value)
            ->ascii()
            ->lower()
            ->replaceMatches('/[^a-z0-9]+/', ' ')
            ->squish()
            ->toString();
    }

    public function digits(?string $value): string
    {
        return (string) preg_replace('/\D+/', '', (string) $value);
    }

    /**
     * Telefone sem o código do país (+55), para "11 90000-0001" e
     * "+55 11 90000-0001" serem o mesmo número.
     */
    public function phone(?string $value): string
    {
        $digits = $this->digits($value);

        if (strlen($digits) >= 12 && str_starts_with($digits, '55')) {
            return substr($digits, 2);
        }

        return $digits;
    }

    public function email(?string $value): string
    {
        return mb_strtolower(trim((string) $value));
    }

    /**
     * Data em Y-m-d, a partir de "1990-04-12" ou "12/04/1990". Valor vazio
     * ou inválido (ex.: 31/02) vira null.
     */
    public function date(mixed $value): ?string
    {
        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        $value = trim($value);

        foreach (['Y-m-d', 'd/m/Y'] as $format) {
            $date = DateTimeImmutable::createFromFormat('!'.$format, $value);

            if ($date !== false && $date->format($format) === $value) {
                return $date->format('Y-m-d');
            }
        }

        return null;
    }

    private function nameMatches(string $value, string $term): bool
    {
        $tokens = array_filter(explode(' ', $this->name($term)));

        if ($tokens === []) {
            return false;
        }

        $name = $this->name($value);

        foreach ($tokens as $token) {
            if (! str_contains($name, $token)) {
                return false;
            }
        }

        return true;
    }

    private function sameNonEmpty(string $value, string $term): bool
    {
        return $term !== '' && $value === $term;
    }
}
