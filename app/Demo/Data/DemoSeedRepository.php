<?php

namespace App\Demo\Data;

use Carbon\CarbonInterface;
use Illuminate\Support\Arr;

class DemoSeedRepository
{
    /**
     * @var array<string, mixed>|null
     */
    private ?array $cache = null;

    public function path(): string
    {
        return database_path('data/demo_seed.json');
    }

    /**
     * @return array<string, mixed>
     */
    public function all(): array
    {
        if ($this->cache !== null) {
            return $this->cache;
        }

        /** @var array<string, mixed> $decoded */
        $decoded = json_decode((string) file_get_contents($this->path()), true, flags: JSON_THROW_ON_ERROR);

        return $this->cache = $decoded;
    }

    /**
     * @return array<string, mixed>
     */
    public function meta(): array
    {
        return Arr::get($this->all(), '_meta', []);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function sources(): array
    {
        return Arr::get($this->meta(), 'sources', []);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function modules(): array
    {
        return Arr::get($this->meta(), 'modules', []);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function plans(): array
    {
        return Arr::get($this->meta(), 'plans', []);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function records(): array
    {
        return Arr::get($this->all(), 'records', []);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function record(string $publicId): ?array
    {
        $needle = mb_strtoupper(trim($publicId));

        foreach ($this->records() as $record) {
            if (mb_strtoupper((string) ($record['public_id'] ?? '')) === $needle) {
                return $record;
            }
        }

        return null;
    }

    /**
     * Simula a chamada a um provedor terceiro de dados fictícios.
     *
     * @return list<array{public_id: string, external_ref: string|null, fetched_at: CarbonInterface, payload: array<string, mixed>}>
     */
    public function payloadsForSource(string $slug): array
    {
        $payloads = [];

        foreach ($this->records() as $record) {
            foreach (Arr::get($record, 'sources', []) as $sourcePayload) {
                if (($sourcePayload['source'] ?? null) !== $slug) {
                    continue;
                }

                $daysAgo = max(0, (int) ($sourcePayload['days_ago'] ?? 0));
                $externalRef = $sourcePayload['external_ref'] ?? null;
                $payload = $sourcePayload['payload'] ?? [];

                $payloads[] = [
                    'public_id' => (string) $record['public_id'],
                    'external_ref' => is_string($externalRef) ? $externalRef : null,
                    'fetched_at' => now()->subDays($daysAgo),
                    'payload' => is_array($payload) ? $payload : [],
                ];
            }
        }

        return $payloads;
    }

    /**
     * @return list<string>
     */
    public function publicIds(): array
    {
        return array_map(
            fn (array $record): string => (string) $record['public_id'],
            $this->records(),
        );
    }
}
