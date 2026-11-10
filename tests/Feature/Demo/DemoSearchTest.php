<?php

use App\Enums\Role;
use Database\Seeders\DemoSeeder;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed(DemoSeeder::class);
    $this->actingAs(userWithRole(Role::Customer));
});

test('busca por nome ignora acento e maiúsculas e lista os resultados', function () {
    $this->get('/buscar?q=aurora LACERDA&tipo=name')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('demo/Busca')
            ->where('query', 'aurora LACERDA')
            ->where('type', 'name')
            ->where('typeLabel', 'Nome')
            ->has('results', 1)
            ->where('results.0.public_id', 'REG-0001')
            ->where('results.0.cpf_masked', '***.482.***-10')
            ->missing('results.0.matched_by')
        );
});

test('busca parcial por nome pode trazer vários registros', function () {
    // "ra" aparece em Aurora, Cora, Moreira e Teixeira.
    $this->get('/buscar?q=ra&tipo=name')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('results', 4)
            ->where('results.0.public_id', 'REG-0001')
            ->where('results.1.public_id', 'REG-0003')
            ->where('results.2.public_id', 'REG-0004')
            ->where('results.3.public_id', 'REG-0006')
        );
});

test('documento, e-mail ou telefone com um único resultado abre a consulta', function (string $type, string $query, string $publicId) {
    $this->get('/buscar?'.http_build_query(['q' => $query, 'tipo' => $type]))
        ->assertRedirect("/consultas/{$publicId}");
})->with([
    'CPF formatado' => ['cpf', '301.482.115-10', 'REG-0001'],
    'CPF só dígitos' => ['cpf', '30148211510', 'REG-0001'],
    'CNH' => ['cnh', '40246600440', 'REG-0004'],
    'CNPJ' => ['cnpj', '12.905.330/0001-30', 'REG-0003'],
    'telefone com DDD' => ['phone', '(11) 90000-0001', 'REG-0001'],
    'telefone com +55' => ['phone', '+55 21 90000-0002', 'REG-0002'],
    'e-mail' => ['email', 'BENTO.ROCHA@mail.test', 'REG-0002'],
]);

test('busca sem resultado mostra a página de resultados vazia', function () {
    $this->get('/buscar?q=Fulano Inexistente&tipo=name')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('demo/Busca')
            ->has('results', 0)
        );
});

test('termo com menos de 2 caracteres volta para a home', function () {
    $this->get('/buscar?q=a&tipo=name')->assertRedirect('/');
});

test('busca sem tipo, com tipo desconhecido ou por ID volta para a home', function (string $url) {
    $this->get($url)->assertRedirect('/');
})->with([
    'sem tipo' => '/buscar?q=aurora',
    'tipo desconhecido' => '/buscar?q=aurora&tipo=qualquer',
    'ID não é tipo de busca' => '/buscar?q=REG-0001&tipo=id',
]);

test('dados ocultos não são pesquisáveis em nenhuma fonte', function () {
    // "PRT-1001" e o e-mail alternativo só existem no bloco _hidden.
    $this->get('/buscar?q=PRT-1001&tipo=name')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->has('results', 0));

    $this->get('/buscar?q=aurora.l@mail.test&tipo=email')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->has('results', 0));
});

test('API de busca retorna os registros e as fontes que os encontraram', function () {
    $this->getJson('/api/demo/busca?q=12905330000130&tipo=cnpj')
        ->assertOk()
        ->assertJsonPath('demo', true)
        ->assertJsonPath('type', 'cnpj')
        ->assertJsonPath('meta.total', 1)
        ->assertJsonPath('data.0.public_id', 'REG-0003')
        ->assertJsonPath('data.0.found_in.0.slug', 'credito-ficticio')
        ->assertJsonMissingPath('kind')
        ->assertJsonMissingPath('data.0.matched_by');
});

test('com tipo escolhido, a busca procura só naquele campo', function () {
    // O CPF da Aurora não é CNH de ninguém.
    $this->get('/buscar?q=301.482.115-10&tipo=cnh')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('demo/Busca')
            ->where('type', 'cnh')
            ->where('typeLabel', 'CNH')
            ->has('results', 0)
        );

    // Mesmos 11 dígitos: achados como CNH, não como CPF.
    $this->get('/buscar?q=10482300110&tipo=cnh')->assertRedirect('/consultas/REG-0001');
    $this->get('/buscar?q=10482300110&tipo=cpf')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->has('results', 0));

    // Um ID buscado como nome é só texto: não acha ninguém.
    $this->get('/buscar?q=REG-0002&tipo=name')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->has('results', 0));
});

test('home preenche o tipo vindo da URL e ignora tipo desconhecido', function () {
    $this->get('/?q=aurora&tipo=cpf')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('initialQuery', 'aurora')
            ->where('initialType', 'cpf')
        );

    $this->get('/?q=aurora&tipo=qualquer')
        ->assertInertia(fn (Assert $page) => $page->where('initialType', null));

    $this->get('/?q=REG-0001&tipo=id')
        ->assertInertia(fn (Assert $page) => $page->where('initialType', null));
});

test('API de busca exige um tipo válido', function () {
    $this->getJson('/api/demo/busca?q=12905330000130&tipo=cpf')
        ->assertOk()
        ->assertJsonPath('meta.total', 0);

    $this->getJson('/api/demo/busca')
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['q', 'tipo']);

    foreach (['', 'qualquer', 'id'] as $type) {
        $this->getJson('/api/demo/busca?q=aurora&tipo='.$type)
            ->assertUnprocessable()
            ->assertJsonValidationErrors('tipo');
    }
});
