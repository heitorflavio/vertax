<?php

use App\Models\DemoSearchTerm;
use Database\Seeders\DemoSeeder;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed(DemoSeeder::class);
});

test('busca por nome ignora acento e maiúsculas e lista os resultados', function () {
    $this->get('/buscar?q=aurora LACERDA')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('demo/Busca')
            ->where('query', 'aurora LACERDA')
            ->has('results', 1)
            ->where('results.0.public_id', 'REG-0001')
            ->where('results.0.matched_by', 'name')
            ->where('results.0.cpf_masked', '***.482.***-10')
        );
});

test('busca parcial por nome pode trazer vários registros', function () {
    // "ra" aparece em Aurora, Cora, Moreira e Teixeira.
    $this->get('/buscar?q=ra')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('results', 4)
            ->where('results.0.public_id', 'REG-0001')
            ->where('results.1.public_id', 'REG-0003')
            ->where('results.2.public_id', 'REG-0004')
            ->where('results.3.public_id', 'REG-0006')
        );
});

test('documento, e-mail ou telefone com um único resultado abre a consulta', function (string $query, string $publicId) {
    $this->get('/buscar?q='.urlencode($query))
        ->assertRedirect("/consultas/{$publicId}");
})->with([
    'CPF formatado' => ['301.482.115-10', 'REG-0001'],
    'CPF só dígitos' => ['30148211510', 'REG-0001'],
    'CNH' => ['40246600440', 'REG-0004'],
    'CNPJ' => ['12.905.330/0001-30', 'REG-0003'],
    'telefone com DDD' => ['(11) 90000-0001', 'REG-0001'],
    'telefone com +55' => ['+55 21 90000-0002', 'REG-0002'],
    'e-mail' => ['BENTO.ROCHA@mail.test', 'REG-0002'],
    'ID' => ['reg-0006', 'REG-0006'],
]);

test('ID inexistente segue para o 404 demonstrativo', function () {
    $this->get('/buscar?q=REG-9999')->assertRedirect('/consultas/REG-9999');
    $this->get('/consultas/REG-9999')->assertNotFound();
});

test('busca sem resultado mostra a página de resultados vazia', function () {
    $this->get('/buscar?q=Fulano Inexistente')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('demo/Busca')
            ->has('results', 0)
        );
});

test('termo com menos de 2 caracteres volta para a home', function () {
    $this->get('/buscar?q=a')->assertRedirect('/');
});

test('dados ocultos não entram no índice de busca', function () {
    expect(DemoSearchTerm::query()->where('value', 'like', '%prt%')->exists())->toBeFalse();

    $this->get('/buscar?q=PRT-1001')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->has('results', 0));
});

test('API de busca retorna os registros e por qual campo foram encontrados', function () {
    $this->getJson('/api/demo/busca?q=12905330000130')
        ->assertOk()
        ->assertJsonPath('demo', true)
        ->assertJsonPath('kind', 'document')
        ->assertJsonPath('meta.total', 1)
        ->assertJsonPath('data.0.public_id', 'REG-0003')
        ->assertJsonPath('data.0.matched_by', 'cnpj')
        ->assertJsonPath('data.0.matched_by_label', 'CNPJ');

    $this->getJson('/api/demo/busca')
        ->assertUnprocessable()
        ->assertJsonValidationErrors('q');
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

    // Com tipo "nome", um ID é tratado como texto e não abre a consulta.
    $this->get('/buscar?q=REG-0002&tipo=name')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->has('results', 0));

    $this->get('/buscar?q=aurora&tipo=name')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('results', 1)
            ->where('results.0.public_id', 'REG-0001')
        );
});

test('tipo desconhecido na URL vira busca em todos os campos', function () {
    $this->get('/buscar?q=301.482.115-10&tipo=qualquer')->assertRedirect('/consultas/REG-0001');

    $this->get('/?q=aurora&tipo=qualquer')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->where('initialType', null));

    $this->get('/?q=aurora&tipo=name')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('initialQuery', 'aurora')
            ->where('initialType', 'name')
        );
});

test('API de busca aceita tipo e rejeita tipo inválido', function () {
    $this->getJson('/api/demo/busca?q=12905330000130&tipo=cnpj')
        ->assertOk()
        ->assertJsonPath('type', 'cnpj')
        ->assertJsonPath('data.0.public_id', 'REG-0003');

    $this->getJson('/api/demo/busca?q=12905330000130&tipo=cpf')
        ->assertOk()
        ->assertJsonPath('meta.total', 0);

    $this->getJson('/api/demo/busca?q=aurora&tipo=qualquer')
        ->assertUnprocessable()
        ->assertJsonValidationErrors('tipo');
});
