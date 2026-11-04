<?php

namespace App\Demo\Plans;

use App\Demo\DemoConfig;
use App\Models\DemoPlan;
use App\Models\DemoPlanActivation;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

class DemoPlanService
{
    private const SESSION_KEY = 'demo.plan';

    /**
     * @return array{
     *     active: bool,
     *     slug: string|null,
     *     name: string|null,
     *     price_label: string|null,
     *     features: list<string>,
     *     grants_hidden: bool,
     *     activated_at: string|null,
     *     expires_at: string|null,
     *     remaining_seconds: int
     * }
     */
    public function status(Request $request): array
    {
        $inactive = [
            'active' => false,
            'slug' => null,
            'name' => null,
            'price_label' => null,
            'features' => [],
            'grants_hidden' => false,
            'activated_at' => null,
            'expires_at' => null,
            'remaining_seconds' => 0,
        ];

        if (! $request->hasSession()) {
            return $inactive;
        }

        $state = $request->session()->get(self::SESSION_KEY);

        if (! is_array($state) || ! isset($state['slug'], $state['expires_at'])) {
            return $inactive;
        }

        $expiresAt = Carbon::parse($state['expires_at']);
        $activatedAt = $state['activated_at'] ?? null;
        $remaining = $expiresAt->getTimestamp() - now()->getTimestamp();

        if ($remaining <= 0) {
            $request->session()->forget(self::SESSION_KEY);

            return $inactive;
        }

        $plan = DemoPlan::query()
            ->where('slug', $state['slug'])
            ->where('is_active', true)
            ->first();

        if ($plan === null) {
            $request->session()->forget(self::SESSION_KEY);

            return $inactive;
        }

        return [
            'active' => true,
            'slug' => $plan->slug,
            'name' => $plan->name,
            'price_label' => $plan->price_label,
            'features' => array_values($plan->features ?? []),
            'grants_hidden' => $plan->grants_hidden,
            'activated_at' => is_string($activatedAt) ? $activatedAt : null,
            'expires_at' => $expiresAt->toIso8601String(),
            'remaining_seconds' => max(0, $remaining),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function activate(Request $request, ?string $slug = null): array
    {
        $slug ??= DemoConfig::DEFAULT_PLAN_SLUG;

        $plan = DemoPlan::query()
            ->where('slug', $slug)
            ->where('is_active', true)
            ->first();

        if ($plan === null) {
            throw ValidationException::withMessages([
                'plan_slug' => 'Plano demo inexistente ou inativo.',
            ]);
        }

        $activatedAt = now();
        $expiresAt = $activatedAt->addMinutes($plan->duration_minutes);

        $request->session()->put(self::SESSION_KEY, [
            'slug' => $plan->slug,
            'activated_at' => $activatedAt->toIso8601String(),
            'expires_at' => $expiresAt->toIso8601String(),
        ]);

        DemoPlanActivation::create([
            'demo_plan_id' => $plan->id,
            'plan_slug' => $plan->slug,
            'session_id' => $request->session()->getId(),
            'activated_at' => $activatedAt,
            'expires_at' => $expiresAt,
            'ip_hash' => $request->ip() === null
                ? null
                : hash('sha256', $request->ip().'|'.config('app.key')),
        ]);

        return $this->status($request);
    }

    public function deactivate(Request $request): void
    {
        if ($request->hasSession()) {
            $request->session()->forget(self::SESSION_KEY);
        }
    }

    public function hasHiddenAccess(Request $request): bool
    {
        $status = $this->status($request);

        return $status['active'] && $status['grants_hidden'];
    }
}
