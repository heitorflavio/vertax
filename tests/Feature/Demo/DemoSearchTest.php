<?php

use App\Models\DemoSearchTerm;
use Database\Seeders\DemoSeeder;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed(DemoSeeder::class);
});

test('busca por nome ignora acento e maiúsculas e lista os resultados', function () {
    $this->get('/buscar?q=aurora FICTICIA')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('demo/Busca')
            ->where('query', 'aurora FICTICIA')
            ->has('results', 1)
            ->where('results.0.public_id', 'DEMO-0001')
            ->where('results.0.matched_by', 'name')
            ->where('results.0.cpf_masked', '***.482.***-10')
        );
});

test('busca parcial por nome pode trazer vários registros', function () {
    $this->get('/buscar?q=ra')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('results', 2)
            ->where('results.0.public_id', 'DEMO-0001')
            ->where('results.1.public_id', 'DEMO-0003')
        );
});

test('documento, e-mail ou telefone com um único resultado abre a consulta', function (string $query, string $publicId) {
    $this->get('/buscar?q='.urlencode($query))
        ->assertRedirect("/consultas/{$publicId}");
})->with([
    'CPF formatado' => ['301.482.115-10', 'DEMO-0001'],
    'CPF só dígitos' => ['30148211510', 'DEMO-0001'],
    'CNH' => ['40246600440', 'DEMO-0004'],
    'CNPJ' => ['12.905.330/0001-30', 'DEMO-0003'],
    'telefone com DDD' => ['(11) 90000-0001', 'DEMO-0001'],
    'telefone com +55' => ['+55 21 90000-0002', 'DEMO-0002'],
    'e-mail' => ['BENTO.DEMO@example.test', 'DEMO-0002'],
    'ID' => ['demo-0006', 'DEMO-0006'],
]);

test('ID inexistente segue para o 404 demonstrativo', function () {
    $this->get('/buscar?q=DEMO-9999')->assertRedirect('/consultas/DEMO-9999');
    $this->get('/consultas/DEMO-9999')->assertNotFound();
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
    expect(DemoSearchTerm::query()->where('value', 'like', '%cx-demo%')->exists())->toBeFalse();

    $this->get('/buscar?q=CX-DEMO-1001')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->has('results', 0));
});

test('API de busca retorna os registros e por qual campo foram encontrados', function () {
    $this->getJson('/api/demo/busca?q=12905330000130')
        ->assertOk()
        ->assertJsonPath('demo', true)
        ->assertJsonPath('kind', 'document')
        ->assertJsonPath('meta.total', 1)
        ->assertJsonPath('data.0.public_id', 'DEMO-0003')
        ->assertJsonPath('data.0.matched_by', 'cnpj')
        ->assertJsonPath('data.0.matched_by_label', 'CNPJ');

    $this->getJson('/api/demo/busca')
        ->assertUnprocessable()
        ->assertJsonValidationErrors('q');
});
