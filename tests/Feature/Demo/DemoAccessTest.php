<?php

use App\Enums\Permission;
use App\Enums\Role;
use App\Models\DemoIngestionRun;
use App\Models\User;
use Database\Seeders\DemoSeeder;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed(DemoSeeder::class);
});

test('visitante é mandado para o login ao buscar, consultar ou abrir o painel', function (string $url) {
    $this->get($url)->assertRedirect(route('login'));
})->with([
    'home' => '/',
    'busca' => '/buscar?q=aurora&tipo=name',
    'consulta' => '/consultas/REG-0001',
    'painel' => '/painel',
]);

test('visitante recebe 401 nas APIs protegidas', function (string $method, string $url) {
    $this->json($method, $url)->assertUnauthorized();
})->with([
    'busca' => ['GET', '/api/demo/busca?q=aurora&tipo=name'],
    'consulta' => ['GET', '/api/demo/consulta/REG-0001'],
    'avaliar' => ['POST', '/api/demo/consulta/REG-0001/avaliar'],
    'registros' => ['GET', '/api/demo/registros'],
    'sincronizar' => ['POST', '/api/demo/sincronizar'],
]);

test('catálogo de módulos e planos continua público', function () {
    $this->get('/modulos')->assertOk();
    $this->get('/planos')->assertOk();
    $this->getJson('/api/demo/modulos')->assertOk();
    $this->getJson('/api/demo/planos')->assertOk();
});

test('cliente consulta, mas não abre o painel nem sincroniza as fontes', function () {
    $this->actingAs(userWithRole(Role::Customer));

    $this->get('/consultas/REG-0001')->assertOk();
    $this->get('/painel')->assertForbidden();
    $this->getJson('/api/demo/registros')->assertForbidden();
    $this->postJson('/api/demo/sincronizar')->assertForbidden();

    expect(DemoIngestionRun::query()->count())->toBe(1);
});

test('operador abre o painel, mas não sincroniza as fontes', function () {
    $this->actingAs(userWithRole(Role::Operator));

    $this->get('/painel')->assertOk();
    $this->getJson('/api/demo/registros')->assertOk();
    $this->postJson('/api/demo/sincronizar')->assertForbidden();
});

test('admin sincroniza as fontes', function () {
    $this->actingAs(userWithRole(Role::Admin));

    $this->postJson('/api/demo/sincronizar')->assertOk();

    expect(DemoIngestionRun::query()->count())->toBe(2);
});

test('usuário sem papel não consulta', function () {
    $this->actingAs(User::factory()->create());

    $this->get('/consultas/REG-0001')->assertForbidden();
    $this->getJson('/api/demo/busca?q=aurora&tipo=name')->assertForbidden();
});

test('usuário com e-mail não verificado é mandado para a verificação', function () {
    $user = User::factory()->unverified()->create()->assignRole(Role::Customer);

    $this->actingAs($user)
        ->get('/consultas/REG-0001')
        ->assertRedirect(route('verification.notice'));
});

test('página recebe papéis e permissões do usuário logado', function () {
    $this->actingAs(userWithRole(Role::Operator))
        ->get('/modulos')
        ->assertInertia(fn (Assert $page) => $page
            ->where('auth.roles', [Role::Operator->value])
            ->where('auth.permissions', fn ($permissions) => collect($permissions)->sort()->values()->all() === [
                Permission::RunConsultations->value,
                Permission::ViewPanel->value,
            ])
        );
});

test('visitante recebe listas vazias de papéis e permissões', function () {
    $this->get('/modulos')
        ->assertInertia(fn (Assert $page) => $page
            ->where('auth.roles', [])
            ->where('auth.permissions', [])
        );
});
