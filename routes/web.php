<?php

use App\Enums\Permission;
use App\Http\Controllers\Demo\DemoApiController;
use App\Http\Controllers\Demo\DemoPageController;
use App\Http\Controllers\Demo\DemoSourceController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Protótipo ConsultaDEMO (dados 100% fictícios)
|--------------------------------------------------------------------------
| As rotas de API ficam no grupo "web" de propósito: o plano demo é mantido
| na sessão, então precisamos de cookies + CSRF (fetch same-origin).
|
| Acesso: catálogo de módulos e planos é público; buscar e consultar exigem
| login com a permissão "consultas.realizar"; painel e sincronização exigem
| as permissões próprias (ver App\Enums\Role para o que cada papel recebe).
*/

$consultations = ['auth', 'verified', 'can:'.Permission::RunConsultations->value];
$panel = ['auth', 'verified', 'can:'.Permission::ViewPanel->value];
$syncSources = ['auth', 'verified', 'can:'.Permission::SyncSources->value];
$manageSources = ['auth', 'verified', 'can:'.Permission::ManageSources->value];

Route::get('modulos', [DemoPageController::class, 'modulesPage'])->name('demo.modules');
Route::get('planos', [DemoPageController::class, 'plans'])->name('demo.plans');

Route::middleware($consultations)->group(function (): void {
    Route::get('/', [DemoPageController::class, 'home'])->name('home');
    Route::get('buscar', [DemoPageController::class, 'search'])->name('demo.search');
    Route::get('consultas/{publicId}', [DemoPageController::class, 'show'])->name('demo.show');
});

Route::get('painel', [DemoPageController::class, 'panel'])
    ->middleware($panel)
    ->name('demo.panel');

Route::prefix('api/demo')->name('api.demo.')->group(function () use ($consultations, $panel, $syncSources): void {
    Route::get('status', [DemoApiController::class, 'status'])->name('status');
    Route::post('plano/ativar', [DemoApiController::class, 'activatePlan'])->name('plan.activate');
    Route::get('modulos', [DemoApiController::class, 'modules'])->name('modules');
    Route::get('planos', [DemoApiController::class, 'plans'])->name('plans');

    Route::middleware($consultations)->group(function (): void {
        Route::get('busca', [DemoApiController::class, 'search'])->name('search');
        Route::get('consulta/{publicId}', [DemoApiController::class, 'show'])->name('show');
        Route::post('consulta/{publicId}/avaliar', [DemoApiController::class, 'rate'])->name('show.rate');
    });

    Route::get('registros', [DemoApiController::class, 'records'])
        ->middleware($panel)
        ->name('records');

    Route::post('sincronizar', [DemoApiController::class, 'sync'])
        ->middleware([...$syncSources, 'throttle:6,1'])
        ->name('sync');
});

Route::prefix('fontes')->middleware($manageSources)->name('demo.sources.')->group(function (): void {
    Route::get('/', [DemoSourceController::class, 'index'])->name('index');
    Route::get('criar', [DemoSourceController::class, 'create'])->name('create');
    Route::post('/', [DemoSourceController::class, 'store'])->name('store');
    Route::get('{slug}/editar', [DemoSourceController::class, 'edit'])->name('edit');
    Route::put('{slug}', [DemoSourceController::class, 'update'])->name('update');
    Route::delete('{slug}', [DemoSourceController::class, 'destroy'])->name('destroy');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'Dashboard')->name('dashboard');
});

require __DIR__.'/settings.php';
