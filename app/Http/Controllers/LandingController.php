<?php

namespace App\Http\Controllers;

use App\Demo\DemoConfig;
use App\Models\DemoModule;
use App\Models\DemoPlan;
use App\Models\DemoSource;
use Inertia\Inertia;
use Inertia\Response;
use Laravel\Fortify\Features;

class LandingController extends Controller
{
    public function __invoke(): Response
    {
        return Inertia::render('Welcome', [
            'canRegister' => Features::enabled(Features::registration()),
            'searchTypes' => array_values(DemoConfig::SEARCH_TYPE_LABELS),
            'mergedFields' => array_map(
                fn (string $field): string => DemoConfig::FIELD_LABELS[$field],
                DemoConfig::MERGE_FIELDS,
            ),
            'trustWeights' => DemoConfig::TRUST_WEIGHTS,
            'sources' => DemoSource::query()
                ->where('is_active', true)
                ->orderByDesc('reliability')
                ->get()
                ->map(fn (DemoSource $source): array => [
                    'slug' => $source->slug,
                    'name' => $source->name,
                    'category' => $source->category,
                    'description' => $source->description,
                    'reliability' => $source->reliability,
                    'search_types' => array_map(
                        fn (string $type): string => DemoConfig::SEARCH_TYPE_LABELS[$type] ?? $type,
                        $source->search_types ?? [],
                    ),
                ])
                ->values(),
            'modules' => DemoModule::query()
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->get()
                ->map(fn (DemoModule $module): array => [
                    'slug' => $module->slug,
                    'name' => $module->name,
                    'description' => $module->description,
                    'category' => $module->category,
                    'icon' => $module->icon,
                ])
                ->values(),
            'plans' => DemoPlan::query()
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->get()
                ->map(fn (DemoPlan $plan): array => [
                    'slug' => $plan->slug,
                    'name' => $plan->name,
                    'description' => $plan->description,
                    'price_label' => $plan->price_label,
                    'price_cents' => $plan->price_cents,
                    'duration_minutes' => $plan->duration_minutes,
                ])
                ->values(),
        ]);
    }
}
