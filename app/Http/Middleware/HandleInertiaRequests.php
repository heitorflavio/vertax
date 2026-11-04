<?php

namespace App\Http\Middleware;

use App\Demo\DemoConfig;
use App\Demo\Plans\DemoPlanService;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'auth' => [
                'user' => $request->user(),
            ],
            'demo' => [
                'plan' => app(DemoPlanService::class)->status($request),
                'disclaimer' => DemoConfig::DISCLAIMER,
                'shortDisclaimer' => DemoConfig::SHORT_DISCLAIMER,
                'copyNotice' => DemoConfig::COPY_NOTICE,
                'defaultPlanSlug' => DemoConfig::DEFAULT_PLAN_SLUG,
            ],
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
        ];
    }
}
