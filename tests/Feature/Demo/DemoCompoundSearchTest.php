<?php

use App\Demo\Providers\DemoProviderRegistry;
use App\Enums\Role;
use App\Models\DemoRecordSource;
use App\Models\DemoSource;
use Database\Seeders\DemoSeeder;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed(DemoSeeder::class);
    $this->actingAs(userWithRole(Role::Customer));
});

test('nome + data de nascimento reduz a lista de resultados', function () {
    // "ra" sozinho traz Aurora, Cora, Moreira e Teixeira.
    $this->get('/buscar?q=ra&tipo=name')
        ->assertInertia(fn (Assert $page) => $page->has('results', 4));

    $this->get('/buscar?q=ra&tipo=name&nascimento=1990-04-12')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('demo/Busca')
            ->where('birthDate', '1990-04-12')
            ->where('birthDateFormatted', '12/04/1990')
            ->has('results', 1)
            ->where('results.0.public_id', 'REG-0001')
            ->where('results.0.birth_date', '1990-04-12')
        );
});

test('data de nascimento que não bate com ninguém não traz resultados', function () {
    $this->get('/buscar?q=ra&tipo=name&nascimento=2000-01-01')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('results', 0)
            ->where('searchedSources.0.hits', 0)
            ->where('searchedSources.1.hits', 0)
            ->where('searchedSources.2.hits', 0)
        );
});

test('fonte sem data de nascimento conta só os registros que passaram no filtro', function () {
    // Contatos não tem data de nascimento: devolve todos os "ra" que conhece,
    // e a busca descarta os que não nasceram em 12/04/1990.
    $response = $this->getJson('/api/demo/busca?q=ra&tipo=name&nascimento=1990-04-12')
        ->assertOk()
        ->assertJsonPath('birth_date', '1990-04-12')
        ->assertJsonPath('meta.total', 1);

    $contacts = collect($response->json('meta.sources'))->firstWhere('slug', 'contato-sintetico');

    expect($contacts['hits'])->toBe(1);
});

test('a base aplica o filtro quando conhece a data; sem a data, devolve e a busca confere', function () {
    $registry = app(DemoProviderRegistry::class);

    expect($registry->for('cadastro-civil-demo')->search('name', 'ra', ['birth_date' => '1990-04-12']))
        ->toHaveCount(1)
        ->and(count($registry->for('contato-sintetico')->search('name', 'ra', ['birth_date' => '1990-04-12'])))
        ->toBeGreaterThan(1);
});

test('sem data no que a fonte devolveu, vale a data do registro já mesclado', function () {
    // E-mail só consulta Contatos, que não tem data de nascimento.
    $this->getJson('/api/demo/busca?q=aurora.lacerda@mail.test&tipo=email&nascimento=1990-04-12')
        ->assertOk()
        ->assertJsonPath('meta.total', 1)
        ->assertJsonPath('data.0.public_id', 'REG-0001');

    $this->getJson('/api/demo/busca?q=aurora.lacerda@mail.test&tipo=email&nascimento=1991-04-12')
        ->assertOk()
        ->assertJsonPath('meta.total', 0);
});

test('quem não passa no filtro não é gravado', function () {
    $contacts = DemoSource::query()->where('slug', 'contato-sintetico')->firstOrFail();
    $coraFromContacts = fn () => DemoRecordSource::query()
        ->where('public_id', 'REG-0003')
        ->where('demo_source_id', $contacts->id);

    $coraFromContacts()->delete();

    // Cora nasceu em 21/07/1998: com a data da Aurora, Contatos a devolve, mas
    // ela é descartada antes de gravar.
    $this->get('/buscar?q=cora&tipo=name&nascimento=1990-04-12')
        ->assertInertia(fn (Assert $page) => $page->has('results', 0));

    expect($coraFromContacts()->exists())->toBeFalse();

    $this->get('/buscar?q=cora&tipo=name&nascimento=1998-07-21')
        ->assertInertia(fn (Assert $page) => $page->has('results', 1));

    expect($coraFromContacts()->exists())->toBeTrue();
});

test('data de nascimento inválida ou no futuro é recusada', function (string $birthDate) {
    $this->get('/buscar?q=aurora&tipo=name&nascimento='.$birthDate)->assertRedirect('/');

    $this->getJson('/api/demo/busca?q=aurora&tipo=name&nascimento='.$birthDate)
        ->assertUnprocessable()
        ->assertJsonValidationErrors('nascimento');
})->with([
    'formato errado' => ['abc'],
    'dia que não existe' => ['1990-02-31'],
    'no futuro' => [now()->addDay()->format('Y-m-d')],
]);

test('home preenche a data de nascimento vinda da URL', function () {
    $this->get('/?q=ra&nascimento=1990-04-12')
        ->assertInertia(fn (Assert $page) => $page->where('initialBirthDate', '1990-04-12'));

    $this->get('/?q=ra&nascimento=abc')
        ->assertInertia(fn (Assert $page) => $page->where('initialBirthDate', null));
});
