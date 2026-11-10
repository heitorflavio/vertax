<?php

use App\Enums\Role;
use App\Models\DemoSource;
use App\Models\User;
use Database\Seeders\DemoSeeder;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed(DemoSeeder::class);
});

test('visitante vê a landing na raiz com fontes, módulos e planos ativos', function () {
    $this->get('/')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Welcome')
            ->has('sources', 4)
            ->where('sources.0.name', 'Cadastro Civil')
            ->where('sources.0.search_types', ['Nome', 'CPF', 'CNH'])
            ->has('modules', 6)
            ->has('plans', 3)
            ->where('plans.0.price_cents', 0)
            ->has('searchTypes', 6)
            ->has('mergedFields', 11)
            ->where('trustWeights.source_quality', 0.35)
        );
});

test('landing não lista fontes inativas', function () {
    DemoSource::query()->where('slug', 'credito-ficticio')->update(['is_active' => false]);

    $this->get('/')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('sources', 3)
            ->where('sources.2.name', 'Localização')
        );
});

test('usuário logado continua vendo a busca na raiz', function () {
    $this->actingAs(userWithRole(Role::Customer))
        ->get('/')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('demo/Home'));
});

test('usuário logado sem e-mail verificado vai para a verificação', function () {
    $user = User::factory()->unverified()->create()->assignRole(Role::Customer);

    $this->actingAs($user)
        ->get('/')
        ->assertRedirect(route('verification.notice'));
});
