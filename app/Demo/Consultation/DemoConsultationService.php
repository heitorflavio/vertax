<?php

namespace App\Demo\Consultation;

use App\Models\DemoConsultation;
use App\Models\DemoRecord;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class DemoConsultationService
{
    /**
     * Registra a consulta e avalia o resultado depois da busca.
     */
    public function register(DemoRecord $record, Request $request, bool $planActive): DemoConsultation
    {
        $consultation = DemoConsultation::query()->create([
            'demo_record_id' => $record->id,
            'session_id' => $request->hasSession() ? $request->session()->getId() : null,
            'plan_active' => $planActive,
            'trust_score' => $record->trust_score,
            'sources_count' => $record->sources_count,
            'ip_hash' => $this->ipHash($request),
        ]);

        $record->increment('consultations_count');
        $record->forceFill(['last_consulted_at' => now()])->save();

        return $consultation;
    }

    public function rate(DemoRecord $record, bool $helpful, Request $request): ?DemoConsultation
    {
        $sessionId = $request->hasSession() ? $request->session()->getId() : null;

        $consultation = DemoConsultation::query()
            ->where('demo_record_id', $record->id)
            ->when($sessionId !== null, fn ($query) => $query->where('session_id', $sessionId))
            ->latest()
            ->first();

        $consultation ??= DemoConsultation::query()
            ->where('demo_record_id', $record->id)
            ->latest()
            ->first();

        if ($consultation === null) {
            return null;
        }

        $consultation->update([
            'feedback' => $helpful ? 'helpful' : 'not_helpful',
            'feedback_at' => now(),
        ]);

        return $consultation;
    }

    public function ipHash(Request $request): ?string
    {
        $ip = $request->ip();

        return $ip === null ? null : hash('sha256', Str::lower($ip).'|'.config('app.key'));
    }
}
