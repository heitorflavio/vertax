<?php

use App\Demo\Ingestion\DemoIngestionService;
use App\Models\DemoConsultation;
use App\Models\DemoRecord;
use App\Models\DemoRecordSource;
use App\Models\DemoSource;
use Database\Seeders\DemoSeeder;

beforeEach(function () {
    $this->seed(DemoSeeder::class);
});

test('ingestão remove payloads e registros que as fontes deixaram de devolver', function () {
    $source = DemoSource::query()->where('slug', 'cadastro-civil-demo')->firstOrFail();

    // Simula um registro antigo (ex.: ID renomeado no provedor).
    $stale = DemoRecord::query()->create([
        'public_id' => 'OLD-0001',
        'full_name' => 'Registro Antigo',
        'birth_date' => '1990-01-01',
    ]);
    DemoRecordSource::query()->create([
        'public_id' => 'OLD-0001',
        'demo_record_id' => $stale->id,
        'demo_source_id' => $source->id,
        'payload' => ['full_name' => 'Registro Antigo', 'birth_date' => '1990-01-01'],
        'fetched_at' => now(),
    ]);
    DemoConsultation::query()->create(['demo_record_id' => $stale->id]);

    app(DemoIngestionService::class)->run();

    expect(DemoRecord::query()->where('public_id', 'OLD-0001')->exists())->toBeFalse()
        ->and(DemoRecordSource::query()->where('public_id', 'OLD-0001')->exists())->toBeFalse()
        ->and(DemoConsultation::query()->where('demo_record_id', $stale->id)->exists())->toBeFalse()
        ->and(DemoRecord::query()->count())->toBe(6);
});
