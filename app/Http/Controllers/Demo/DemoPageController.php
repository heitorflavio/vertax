<?php

namespace App\Http\Controllers\Demo;

use App\Demo\Consultation\DemoConsultationService;
use App\Demo\DemoConfig;
use App\Demo\DemoPresenter;
use App\Demo\Plans\DemoPlanService;
use App\Demo\Search\DemoSearchService;
use App\Http\Controllers\Controller;
use App\Models\DemoConsultation;
use App\Models\DemoIngestionRun;
use App\Models\DemoModule;
use App\Models\DemoPlan;
use App\Models\DemoPlanActivation;
use App\Models\DemoRecord;
use App\Models\DemoSource;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class DemoPageController extends Controller
{
    public function __construct(
        private readonly DemoPlanService $plans,
        private readonly DemoPresenter $presenter,
        private readonly DemoConsultationService $consultations,
    ) {}

    public function home(Request $request): InertiaResponse
    {
        return Inertia::render('demo/Home', [
            'modules' => $this->modules(),
            'recentSearches' => $this->recentSearches($request),
            'searchExamples' => $this->searchExamples(),
            'initialQuery' => (string) $request->query('q', ''),
        ]);
    }

    /**
     * Busca geral: ID, nome, CPF, CNPJ, CNH, e-mail ou telefone.
     */
    public function search(Request $request, DemoSearchService $search): InertiaResponse|RedirectResponse
    {
        $query = Str::limit(trim((string) $request->query('q', '')), 100, '');

        if (mb_strlen($query) < 2) {
            Inertia::flash('toast', ['type' => 'error', 'message' => 'Digite pelo menos 2 caracteres para buscar.']);

            return to_route('home');
        }

        $result = $search->search($query);

        // ID vai direto para a consulta; um ID inexistente cai no 404 demonstrativo.
        if ($result['kind'] === 'id') {
            return to_route('demo.show', ['publicId' => mb_strtoupper($query)]);
        }

        // Documento, e-mail ou telefone com um único registro: abre a consulta.
        if ($result['kind'] !== 'name' && count($result['matches']) === 1) {
            return to_route('demo.show', ['publicId' => $result['matches'][0]['record']->public_id]);
        }

        return Inertia::render('demo/Busca', [
            'query' => $query,
            'results' => array_map(
                fn (array $match): array => $this->presenter->searchResult($match['record'], $match['matched_by']),
                $result['matches'],
            ),
        ]);
    }

    /**
     * Um exemplo de cada tipo de busca, tirado do próprio seed.
     *
     * @return list<array{type: string, value: string}>
     */
    private function searchExamples(): array
    {
        $records = DemoRecord::query()->orderBy('public_id')->get();

        $candidates = [
            ['type' => 'Nome', 'value' => Str::before((string) $records->get(0)?->full_name, ' ')],
            ['type' => 'CPF', 'value' => (string) $records->get(1)?->cpf],
            ['type' => 'CNPJ', 'value' => (string) $records->whereNotNull('cnpj')->first()?->cnpj],
            ['type' => 'CNH', 'value' => (string) $records->get(3)?->cnh],
            ['type' => 'ID', 'value' => (string) $records->last()?->public_id],
        ];

        return array_values(array_filter(
            $candidates,
            fn (array $example): bool => $example['value'] !== '',
        ));
    }

    /**
     * Últimos registros consultados nesta sessão, do mais recente para o mais
     * antigo, sem repetir o mesmo ID.
     *
     * @return list<array<string, mixed>>
     */
    private function recentSearches(Request $request, int $limit = 5): array
    {
        $latestIds = DemoConsultation::query()
            ->where('session_id', $request->session()->getId())
            ->selectRaw('MAX(id) as last_id')
            ->groupBy('demo_record_id')
            ->orderByDesc('last_id')
            ->limit($limit)
            ->pluck('last_id');

        $consultations = DemoConsultation::query()
            ->with('record')
            ->whereIn('id', $latestIds)
            ->orderByDesc('id')
            ->get();

        $searches = [];

        foreach ($consultations as $consultation) {
            $record = $consultation->record;

            if ($record === null) {
                continue;
            }

            $searches[] = [
                'public_id' => $record->public_id,
                'full_name' => $record->full_name,
                'trust_score' => $record->trust_score,
                'trust_label' => $record->trust_label,
                'searched_at' => $consultation->created_at?->toIso8601String(),
            ];
        }

        return $searches;
    }

    public function show(Request $request, string $publicId): InertiaResponse|SymfonyResponse
    {
        $record = DemoRecord::query()->publicId($publicId)->first();

        if ($record === null) {
            return Inertia::render('demo/NotFound', [
                'publicId' => $publicId,
                'availableIds' => DemoRecord::query()->orderBy('public_id')->pluck('public_id'),
            ])->toResponse($request)->setStatusCode(Response::HTTP_NOT_FOUND);
        }

        $hasHiddenAccess = $this->plans->hasHiddenAccess($request);
        $consultation = $this->consultations->register($record, $request, $hasHiddenAccess);
        $record = $record->fresh();

        return Inertia::render('demo/Consulta', [
            'record' => $this->presenter->detail($record, $hasHiddenAccess, $consultation),
            'plans' => $this->planOptions(),
        ]);
    }

    public function plans(): InertiaResponse
    {
        return Inertia::render('demo/Planos', [
            'plans' => $this->planOptions(),
        ]);
    }

    public function panel(): InertiaResponse
    {
        $helpful = DemoConsultation::query()->where('feedback', 'helpful')->count();
        $notHelpful = DemoConsultation::query()->where('feedback', 'not_helpful')->count();

        return Inertia::render('demo/Painel', [
            'records' => DemoRecord::query()
                ->orderBy('public_id')
                ->get()
                ->map(fn (DemoRecord $record): array => $this->presenter->summary($record))
                ->values(),
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
                    'is_active' => $source->is_active,
                    'payloads' => $source->record_sources_count,
                ]),
            'stats' => [
                'records' => DemoRecord::query()->count(),
                'sources' => DemoSource::query()->where('is_active', true)->count(),
                'consultations' => DemoConsultation::query()->count(),
                'plan_activations' => DemoPlanActivation::query()->count(),
                'helpful' => $helpful,
                'not_helpful' => $notHelpful,
                'avg_trust' => (int) round((float) DemoRecord::query()->avg('trust_score')),
            ],
            'lastRun' => $this->lastRun(),
        ]);
    }

    public function modulesPage(): InertiaResponse
    {
        return Inertia::render('demo/Modulos', [
            'modules' => $this->modules(),
        ]);
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function planOptions(): array
    {
        $plans = [];

        foreach (DemoPlan::query()->where('is_active', true)->orderBy('sort_order')->get() as $plan) {
            $plans[] = [
                'slug' => $plan->slug,
                'name' => $plan->name,
                'description' => $plan->description,
                'price_label' => $plan->price_label,
                'duration_minutes' => $plan->duration_minutes,
                'grants_hidden' => $plan->grants_hidden,
                'features' => array_values($plan->features ?? []),
                'recommended' => $plan->slug === DemoConfig::DEFAULT_PLAN_SLUG,
            ];
        }

        return $plans;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function modules(): array
    {
        $modules = [];

        foreach (DemoModule::query()->where('is_active', true)->orderBy('sort_order')->get() as $module) {
            $modules[] = [
                'slug' => $module->slug,
                'name' => $module->name,
                'description' => $module->description,
                'category' => $module->category,
                'icon' => $module->icon,
                'badge' => $module->badge,
                'is_demo' => $module->is_demo,
            ];
        }

        return $modules;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function lastRun(): ?array
    {
        $run = DemoIngestionRun::query()->latest('id')->first();

        if ($run === null) {
            return null;
        }

        return [
            'status' => $run->status,
            'sources_synced' => $run->sources_synced,
            'payloads_ingested' => $run->payloads_ingested,
            'records_merged' => $run->records_merged,
            'duration_ms' => $run->duration_ms,
            'message' => $run->message,
            'started_at' => $run->started_at?->toIso8601String(),
            'finished_at' => $run->finished_at?->toIso8601String(),
        ];
    }
}
