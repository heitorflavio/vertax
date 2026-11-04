<?php

use Database\Seeders\DemoSeeder;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed(DemoSeeder::class);
});

test('página inicial exibe busca, módulos e aviso de demonstração', function () {
    $this->get('/')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('demo/Home')
            ->has('modules', 6)
            ->has('recentSearches', 0)
            ->has('searchExamples', 5)
            ->where('searchExamples.0', ['type' => 'Nome', 'value' => 'Aurora'])
            ->where('demo.disclaimer', fn (string $value) => str_contains($value, 'fictícios'))
        );
});

test('página inicial lista as últimas buscas da sessão, sem repetir registros', function () {
    // ID de sessão fixo: sem o cookie, cada request de teste ganha uma sessão nova.
    $this->withCookie(config('session.cookie'), str_repeat('a', 40));

    $this->get('/consultas/DEMO-0002')->assertOk();
    $this->get('/consultas/DEMO-0001')->assertOk();
    $this->get('/consultas/DEMO-0002')->assertOk();

    $this->get('/')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('recentSearches', 2)
            ->where('recentSearches.0.public_id', 'DEMO-0002')
            ->where('recentSearches.1.public_id', 'DEMO-0001')
        );
});

test('últimas buscas não mostram consultas de outra sessão', function () {
    $this->withCookie(config('session.cookie'), str_repeat('a', 40))
        ->get('/consultas/DEMO-0003')
        ->assertOk();

    $this->withCookie(config('session.cookie'), str_repeat('b', 40))
        ->get('/')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->has('recentSearches', 0));
});

test('página de consulta válida mostra dados fictícios e bloco oculto bloqueado', function () {
    $response = $this->get('/consultas/DEMO-0001');

    $response
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('demo/Consulta')
            ->where('record.public_id', 'DEMO-0001')
            ->where('record.hidden_locked', true)
            ->where('record.hidden', null)
            ->has('record.trust.breakdown')
            ->has('plans', 3)
        );

    $response->assertDontSee('CX-DEMO-1001');
});

test('bloco oculto é revelado na página após ativar plano demo', function () {
    $this->postJson('/api/demo/plano/ativar', ['plan_slug' => 'demo-essencial'])
        ->assertOk();

    $this->get('/consultas/DEMO-0001')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('demo/Consulta')
            ->where('record.hidden_locked', false)
            ->has('record.hidden', 6)
        );
});

test('página de consulta inválida retorna 404 demonstrativo', function () {
    $this->get('/consultas/NAO-EXISTE')
        ->assertNotFound()
        ->assertInertia(fn (Assert $page) => $page
            ->component('demo/NotFound')
            ->where('publicId', 'NAO-EXISTE')
            ->has('availableIds', 6)
        );
});

test('páginas de planos, painel e módulos respondem', function () {
    $this->get('/planos')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('demo/Planos')
            ->has('plans', 3)
        );

    $this->get('/painel')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('demo/Painel')
            ->has('records', 6)
            ->has('sources', 4)
        );

    $this->get('/modulos')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('demo/Modulos')
            ->has('modules', 6)
        );
});
