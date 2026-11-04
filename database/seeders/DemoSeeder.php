<?php

namespace Database\Seeders;

use App\Demo\Data\DemoSeedRepository;
use App\Demo\Ingestion\DemoIngestionService;
use App\Models\DemoModule;
use App\Models\DemoPlan;
use App\Models\DemoSource;
use Illuminate\Database\Seeder;

class DemoSeeder extends Seeder
{
    public function run(): void
    {
        /** @var DemoSeedRepository $seed */
        $seed = app(DemoSeedRepository::class);

        foreach ($seed->sources() as $source) {
            DemoSource::updateOrCreate(
                ['slug' => $source['slug']],
                [
                    'name' => $source['name'],
                    'category' => $source['category'],
                    'description' => $source['description'] ?? null,
                    'reliability' => $source['reliability'],
                    'is_active' => true,
                    'notes' => $source['notes'] ?? null,
                ],
            );
        }

        foreach ($seed->modules() as $module) {
            DemoModule::updateOrCreate(
                ['slug' => $module['slug']],
                [
                    'name' => $module['name'],
                    'description' => $module['description'],
                    'category' => $module['category'],
                    'icon' => $module['icon'] ?? null,
                    'badge' => $module['badge'] ?? 'DEMO',
                    'is_demo' => true,
                    'is_active' => true,
                    'sort_order' => $module['sort_order'] ?? 0,
                ],
            );
        }

        foreach ($seed->plans() as $plan) {
            DemoPlan::updateOrCreate(
                ['slug' => $plan['slug']],
                [
                    'name' => $plan['name'],
                    'description' => $plan['description'],
                    'price_label' => $plan['price_label'],
                    'price_cents' => $plan['price_cents'] ?? 0,
                    'duration_minutes' => $plan['duration_minutes'] ?? 60,
                    'grants_hidden' => $plan['grants_hidden'] ?? true,
                    'features' => $plan['features'] ?? [],
                    'is_active' => true,
                    'sort_order' => $plan['sort_order'] ?? 0,
                ],
            );
        }

        // Simula a chamada às APIs de terceiros e reconstrói a base canônica.
        app(DemoIngestionService::class)->run();
    }
}
