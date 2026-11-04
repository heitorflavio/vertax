<?php

namespace App\Http\Controllers\Demo;

use App\Demo\Consultation\DemoConsultationService;
use App\Demo\DemoConfig;
use App\Demo\DemoPresenter;
use App\Demo\Ingestion\DemoIngestionService;
use App\Demo\Plans\DemoPlanService;
use App\Demo\Search\DemoSearchService;
use App\Http\Controllers\Controller;
use App\Models\DemoModule;
use App\Models\DemoPlan;
use App\Models\DemoRecord;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

class DemoApiController extends Controller
{
    public function __construct(
        private readonly DemoPlanService $plans,
        private readonly DemoPresenter $presenter,
        private readonly DemoConsultationService $consultations,
    ) {}

    public function records(): JsonResponse
    {
        $records = DemoRecord::query()->orderBy('public_id')->get();

        return response()->json([
            'demo' => true,
            'disclaimer' => DemoConfig::DISCLAIMER,
            'data' => $records
                ->map(fn (DemoRecord $record): array => $this->presenter->summary($record))
                ->values(),
            'meta' => [
                'total' => $records->count(),
                'available_ids' => $records->pluck('public_id')->values(),
            ],
        ]);
    }

    public function search(Request $request, DemoSearchService $search): JsonResponse
    {
        $validated = $request->validate([
            'q' => ['required', 'string', 'min:2', 'max:100'],
        ]);

        $result = $search->search($validated['q']);

        return response()->json([
            'demo' => true,
            'disclaimer' => DemoConfig::DISCLAIMER,
            'query' => $validated['q'],
            'kind' => $result['kind'],
            'data' => array_map(
                fn (array $match): array => $this->presenter->searchResult($match['record'], $match['matched_by']),
                $result['matches'],
            ),
            'meta' => [
                'total' => count($result['matches']),
            ],
        ]);
    }

    public function show(Request $request, string $publicId): JsonResponse
    {
        $record = DemoRecord::query()->publicId($publicId)->first();

        if ($record === null) {
            return $this->notFound();
        }

        $hasHiddenAccess = $this->plans->hasHiddenAccess($request);
        $consultation = $this->consultations->register($record, $request, $hasHiddenAccess);
        $record = $record->fresh();

        return response()->json([
            'demo' => true,
            'disclaimer' => DemoConfig::DISCLAIMER,
            'data' => $this->presenter->detail($record, $hasHiddenAccess, $consultation),
        ]);
    }

    public function status(Request $request): JsonResponse
    {
        return response()->json([
            'demo' => true,
            'disclaimer' => DemoConfig::DISCLAIMER,
            'data' => $this->plans->status($request),
        ]);
    }

    public function activatePlan(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'plan_slug' => ['nullable', 'string', 'exists:demo_plans,slug'],
        ]);

        $status = $this->plans->activate($request, $validated['plan_slug'] ?? null);

        return response()->json([
            'demo' => true,
            'message' => 'Plano demo ativado. Nenhum pagamento foi realizado: este é um protótipo.',
            'data' => $status,
        ]);
    }

    public function rate(Request $request, string $publicId): JsonResponse
    {
        $validated = $request->validate([
            'helpful' => ['required', 'boolean'],
        ]);

        $record = DemoRecord::query()->publicId($publicId)->first();

        if ($record === null) {
            return $this->notFound();
        }

        $consultation = $this->consultations->rate($record, (bool) $validated['helpful'], $request);

        if ($consultation === null) {
            return response()->json([
                'demo' => true,
                'message' => 'Nenhuma consulta registrada para avaliar. Faça uma busca primeiro.',
            ], 409);
        }

        return response()->json([
            'demo' => true,
            'message' => 'Obrigado! Sua avaliação ajuda a demonstrar o fluxo de qualidade dos dados.',
            'data' => [
                'consultation_id' => $consultation->id,
                'feedback' => $consultation->feedback,
                'feedback_at' => $consultation->feedback_at?->toIso8601String(),
            ],
        ]);
    }

    public function sync(DemoIngestionService $ingestion): JsonResponse
    {
        try {
            $run = $ingestion->run();
        } catch (Throwable $exception) {
            report($exception);

            return response()->json([
                'demo' => true,
                'message' => 'Falha ao sincronizar as fontes demo.',
                'error' => $exception->getMessage(),
            ], 500);
        }

        return response()->json([
            'demo' => true,
            'message' => $run->message,
            'data' => [
                'status' => $run->status,
                'sources_synced' => $run->sources_synced,
                'payloads_ingested' => $run->payloads_ingested,
                'records_merged' => $run->records_merged,
                'duration_ms' => $run->duration_ms,
            ],
        ]);
    }

    public function modules(): JsonResponse
    {
        return response()->json([
            'demo' => true,
            'disclaimer' => DemoConfig::DISCLAIMER,
            'data' => DemoModule::query()
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->get(),
        ]);
    }

    public function plans(): JsonResponse
    {
        return response()->json([
            'demo' => true,
            'disclaimer' => DemoConfig::DISCLAIMER,
            'data' => DemoPlan::query()
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->get(),
        ]);
    }

    private function notFound(): JsonResponse
    {
        return response()->json([
            'demo' => true,
            'error' => 'demo_record_not_found',
            'message' => 'Registro fictício não encontrado. Este ambiente é uma demonstração; use um ID existente no seed.',
            'available_ids' => DemoRecord::query()->orderBy('public_id')->pluck('public_id'),
        ], 404);
    }
}
