<?php

namespace App\Http\Controllers\Demo;

use App\Demo\DemoConfig;
use App\Demo\Ingestion\DemoIngestionService;
use App\Http\Controllers\Controller;
use App\Models\DemoSource;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Throwable;

class DemoSourceController extends Controller
{
    public function index(): InertiaResponse
    {
        return Inertia::render('demo/sources/Index', [
            'sources' => DemoSource::query()
                ->withCount('recordSources')
                ->orderByDesc('reliability')
                ->get()
                ->map(fn (DemoSource $source): array => [
                    'slug' => $source->slug,
                    'name' => $source->name,
                    'category' => $source->category,
                    'description' => $source->description,
                    'reliability' => $source->reliability,
                    'search_types' => $source->search_types ?? [],
                    'is_active' => $source->is_active,
                    'has_api' => ! empty($source->api_config),
                    'payloads' => $source->record_sources_count,
                ]),
        ]);
    }

    public function create(): InertiaResponse
    {
        return Inertia::render('demo/sources/Form', [
            'source' => null,
            'searchTypeOptions' => DemoConfig::SEARCH_TYPE_LABELS,
            'fieldOptions' => DemoConfig::FIELD_LABELS,
        ]);
    }

    public function store(Request $request, DemoIngestionService $ingestion): RedirectResponse
    {
        $validated = $this->validated($request);

        $source = DemoSource::create($validated);

        if (! empty($source->api_config)) {
            try {
                $ingestion->run([$source->slug]);
            } catch (Throwable $e) {
                report($e);

                return to_route('demo.sources.index')
                    ->with('toast', ['type' => 'warning', 'message' => "Fonte \"{$source->name}\" criada, mas a ingestão inicial falhou: {$e->getMessage()}"]);
            }
        }

        return to_route('demo.sources.index')
            ->with('toast', ['type' => 'success', 'message' => "Fonte \"{$source->name}\" criada."]);
    }

    public function edit(string $slug): InertiaResponse
    {
        $source = DemoSource::query()->where('slug', $slug)->firstOrFail();

        return Inertia::render('demo/sources/Form', [
            'source' => [
                'slug' => $source->slug,
                'name' => $source->name,
                'category' => $source->category,
                'description' => $source->description,
                'reliability' => $source->reliability,
                'search_types' => $source->search_types ?? [],
                'is_active' => $source->is_active,
                'notes' => $source->notes,
                'api_config' => $source->api_config,
            ],
            'searchTypeOptions' => DemoConfig::SEARCH_TYPE_LABELS,
            'fieldOptions' => DemoConfig::FIELD_LABELS,
        ]);
    }

    public function update(Request $request, string $slug, DemoIngestionService $ingestion): RedirectResponse
    {
        $source = DemoSource::query()->where('slug', $slug)->firstOrFail();
        $validated = $this->validated($request, $source);

        $hadApi = ! empty($source->api_config);
        $source->update($validated);
        $hasApi = ! empty($source->api_config);

        if ($hasApi && ! $hadApi) {
            try {
                $ingestion->run([$source->slug]);
            } catch (Throwable $e) {
                report($e);
            }
        }

        return to_route('demo.sources.index')
            ->with('toast', ['type' => 'success', 'message' => "Fonte \"{$source->name}\" atualizada."]);
    }

    public function destroy(string $slug): RedirectResponse
    {
        $source = DemoSource::query()->where('slug', $slug)->firstOrFail();
        $name = $source->name;
        $source->delete();

        return to_route('demo.sources.index')
            ->with('toast', ['type' => 'success', 'message' => "Fonte \"{$name}\" removida."]);
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, ?DemoSource $existing = null): array
    {
        if (empty($request->input('api_config.base_url'))) {
            $request->merge(['api_config' => null]);
        }

        $rules = [
            'slug' => [
                'required', 'string', 'max:80', 'regex:/^[a-z0-9]+(-[a-z0-9]+)*$/',
                Rule::unique('demo_sources', 'slug')->ignore($existing?->id),
            ],
            'name' => ['required', 'string', 'max:120'],
            'category' => ['required', 'string', 'max:60'],
            'description' => ['nullable', 'string', 'max:500'],
            'reliability' => ['required', 'integer', 'between:0,100'],
            'search_types' => ['required', 'array', 'min:1'],
            'search_types.*' => ['string', Rule::in(array_keys(DemoConfig::SEARCH_TYPE_LABELS))],
            'is_active' => ['boolean'],
            'notes' => ['nullable', 'string', 'max:1000'],

            'api_config' => ['nullable', 'array'],
            'api_config.base_url' => ['required_with:api_config', 'nullable', 'url:https,http', 'max:500'],
            'api_config.timeout' => ['nullable', 'integer', 'between:1,120'],
            'api_config.id_prefix' => ['nullable', 'string', 'max:10', 'regex:/^[A-Z0-9]+$/'],

            'api_config.auth' => ['nullable', 'array'],
            'api_config.auth.type' => ['required_with:api_config.auth', 'string', Rule::in(['none', 'bearer', 'api_key', 'basic'])],
            'api_config.auth.token' => ['nullable', 'string', 'max:500'],
            'api_config.auth.header' => ['nullable', 'string', 'max:100'],
            'api_config.auth.key' => ['nullable', 'string', 'max:500'],
            'api_config.auth.username' => ['nullable', 'string', 'max:200'],
            'api_config.auth.password' => ['nullable', 'string', 'max:500'],

            'api_config.endpoints' => ['nullable', 'array'],
            'api_config.endpoints.fetch' => ['nullable', 'array'],
            'api_config.endpoints.fetch.method' => ['nullable', 'string', Rule::in(['GET', 'POST'])],
            'api_config.endpoints.fetch.path' => ['nullable', 'string', 'max:500'],
            'api_config.endpoints.fetch.payload_root' => ['nullable', 'string', 'max:100'],
            'api_config.endpoints.fetch.id_field' => ['nullable', 'string', 'max:100'],
            'api_config.endpoints.search' => ['nullable', 'array'],
            'api_config.endpoints.search.method' => ['nullable', 'string', Rule::in(['GET', 'POST'])],
            'api_config.endpoints.search.path' => ['nullable', 'string', 'max:500'],
            'api_config.endpoints.search.query_param' => ['nullable', 'string', 'max:100'],
            'api_config.endpoints.search.type_param' => ['nullable', 'string', 'max:100'],
            'api_config.endpoints.search.payload_root' => ['nullable', 'string', 'max:100'],
            'api_config.endpoints.search.id_field' => ['nullable', 'string', 'max:100'],

            'api_config.field_map' => ['nullable', 'array'],
            'api_config.field_map.*' => ['string', 'max:100'],
        ];

        $validated = $request->validate($rules);

        if ($validated['api_config'] === null || empty($validated['api_config']['base_url'] ?? null)) {
            $validated['api_config'] = null;
        }

        return $validated;
    }
}
