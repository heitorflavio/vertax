<?php

use App\Http\Controllers\Demo\DemoApiController;
use App\Http\Controllers\Demo\DemoPageController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Protótipo ConsultaDEMO (dados 100% fictícios)
|--------------------------------------------------------------------------
| As rotas de API ficam no grupo "web" de propósito: o plano demo é mantido
| na sessão, então precisamos de cookies + CSRF (fetch same-origin).
*/

Route::get('/', [DemoPageController::class, 'home'])->name('home');
Route::get('buscar', [DemoPageController::class, 'search'])->name('demo.search');
Route::get('modulos', [DemoPageController::class, 'modulesPage'])->name('demo.modules');
Route::get('planos', [DemoPageController::class, 'plans'])->name('demo.plans');
Route::get('painel', [DemoPageController::class, 'panel'])->name('demo.panel');
Route::get('consultas/{publicId}', [DemoPageController::class, 'show'])->name('demo.show');

Route::prefix('api/demo')->name('api.demo.')->group(function (): void {
    Route::get('registros', [DemoApiController::class, 'records'])->name('records');
    Route::get('busca', [DemoApiController::class, 'search'])->name('search');
    Route::get('consulta/{publicId}', [DemoApiController::class, 'show'])->name('show');
    Route::get('status', [DemoApiController::class, 'status'])->name('status');
    Route::post('plano/ativar', [DemoApiController::class, 'activatePlan'])->name('plan.activate');
    Route::post('consulta/{publicId}/avaliar', [DemoApiController::class, 'rate'])->name('show.rate');
    Route::post('sincronizar', [DemoApiController::class, 'sync'])
        ->middleware('throttle:6,1')
        ->name('sync');
    Route::get('modulos', [DemoApiController::class, 'modules'])->name('modules');
    Route::get('planos', [DemoApiController::class, 'plans'])->name('plans');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'Dashboard')->name('dashboard');
});

require __DIR__.'/settings.php';
