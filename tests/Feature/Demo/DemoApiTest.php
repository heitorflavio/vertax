<?php

use App\Models\DemoRecord;
use Database\Seeders\DemoSeeder;

beforeEach(function () {
    $this->seed(DemoSeeder::class);
});

test('lista os registros fictícios disponíveis', function () {
    $response = $this->getJson('/api/demo/registros');

    $response
        ->assertOk()
        ->assertJsonPath('demo', true)
        ->assertJsonPath('meta.total', 6)
        ->assertJsonCount(6, 'data')
        ->assertJsonStructure([
            'disclaimer',
            'data' => [
                '*' => [
                    'public_id',
                    'full_name',
                    'cpf_masked',
                    'trust_score',
                    'trust_label',
                    'sources_count',
                    'url',
                ],
            ],
        ]);
});

test('busca válida retorna dados fictícios, fontes e score de confiança', function () {
    $response = $this->getJson('/api/demo/consulta/REG-0001');

    $response
        ->assertOk()
        ->assertJsonPath('demo', true)
        ->assertJsonPath('data.public_id', 'REG-0001')
        ->assertJsonPath('data.full_name', 'Aurora Mendes Lacerda')
        ->assertJsonPath('data.hidden_locked', true)
        ->assertJsonPath('data.hidden', null)
        ->assertJsonPath('data.sources_count', 4)
        ->assertJsonStructure([
            'data' => [
                'basic_fields',
                'fields',
                'trust' => [
                    'score',
                    'label',
                    'breakdown' => [
                        'source_quality',
                        'completeness',
                        'consistency',
                        'freshness',
                    ],
                ],
                'sources' => [
                    '*' => ['name', 'reliability', 'contributed_fields'],
                ],
            ],
        ]);

    $score = $response->json('data.trust.score');
    expect($score)->toBeGreaterThan(0)->toBeLessThanOrEqual(100);

    // O valor oculto não deve vazar sem plano ativo.
    $response->assertDontSee('PRT-1001');
});

test('busca inválida retorna 404 com mensagem de demonstração', function () {
    $response = $this->getJson('/api/demo/consulta/ID-QUE-NAO-EXISTE');

    $response
        ->assertNotFound()
        ->assertJsonPath('demo', true)
        ->assertJsonPath('error', 'demo_record_not_found')
        ->assertJsonPath('message', 'Registro não encontrado. Use um ID existente.')
        ->assertJsonStructure(['available_ids']);
});

test('ativação de plano demo ativa a sessão sem pagamento', function () {
    $this->getJson('/api/demo/status')
        ->assertOk()
        ->assertJsonPath('data.active', false);

    $this->postJson('/api/demo/plano/ativar', ['plan_slug' => 'demo-pro'])
        ->assertOk()
        ->assertJsonPath('data.active', true)
        ->assertJsonPath('data.slug', 'demo-pro')
        ->assertJsonPath('message', 'Plano ativado. Nenhum pagamento foi realizado.');

    $this->getJson('/api/demo/status')
        ->assertOk()
        ->assertJsonPath('data.active', true)
        ->assertJsonPath('data.grants_hidden', true);
});

test('informações ocultas são bloqueadas sem plano e reveladas com plano ativo', function () {
    $blocked = $this->getJson('/api/demo/consulta/REG-0001')
        ->assertOk()
        ->assertJsonPath('data.hidden_locked', true)
        ->assertJsonPath('data.hidden', null)
        ->assertJsonPath('data.hidden_fields_count', 6);

    $blocked->assertDontSee('PRT-1001');

    $this->postJson('/api/demo/plano/ativar', ['plan_slug' => 'demo-pro'])
        ->assertOk();

    $revealed = $this->getJson('/api/demo/consulta/REG-0001')
        ->assertOk()
        ->assertJsonPath('data.hidden_locked', false)
        ->assertJsonStructure([
            'data' => [
                'hidden' => [
                    '*' => ['key', 'label', 'value'],
                ],
            ],
        ]);

    $revealed->assertSee('PRT-1001');
});

test('consulta válida pode ser avaliada depois da busca', function () {
    $this->getJson('/api/demo/consulta/REG-0003')->assertOk();

    $this->postJson('/api/demo/consulta/REG-0003/avaliar', ['helpful' => true])
        ->assertOk()
        ->assertJsonPath('data.feedback', 'helpful')
        ->assertJsonPath('message', 'Obrigado! Avaliação registrada.');

    expect(DemoRecord::query()->where('public_id', 'REG-0003')->first()->consultations_count)->toBe(1);
});

test('registro com menor completude recebe score menor que um registro completo', function () {
    $complete = DemoRecord::query()->where('public_id', 'REG-0001')->first();
    $incomplete = DemoRecord::query()->where('public_id', 'REG-0005')->first();

    expect($complete->trust_score)->toBeGreaterThan($incomplete->trust_score);
    expect($incomplete->trust_breakdown['completeness'])->toBeLessThan(100);
});

test('consulta exibe o CPF fictício completo e a listagem mantém o CPF mascarado', function () {
    $this->getJson('/api/demo/consulta/REG-0001')
        ->assertOk()
        ->assertJsonPath('data.cpf', '301.482.115-10')
        ->assertJsonPath('data.cpf_masked', '***.482.***-10')
        ->assertJsonPath('data.basic_fields.1.key', 'cpf')
        ->assertJsonPath('data.basic_fields.1.value', '301.482.115-10');

    $this->getJson('/api/demo/registros')
        ->assertOk()
        ->assertJsonPath('data.0.cpf_masked', '***.482.***-10')
        ->assertDontSee('301.482.115-10');
});

test('todos os CPFs do seed são inválidos (dígito verificador não confere)', function () {
    $checkDigits = function (string $base): string {
        $digits = array_map('intval', str_split($base));
        $first = (array_sum(array_map(fn ($d, $w) => $d * $w, $digits, range(10, 2))) * 10) % 11 % 10;
        $digits[] = $first;
        $second = (array_sum(array_map(fn ($d, $w) => $d * $w, $digits, range(11, 2))) * 10) % 11 % 10;

        return $first.$second;
    };

    $records = DemoRecord::query()->get();

    expect($records)->not->toBeEmpty();

    foreach ($records as $record) {
        $digits = preg_replace('/\D+/', '', (string) $record->cpf);

        expect($digits)->toHaveLength(11)
            ->and(substr($digits, 9, 2))->not->toBe($checkDigits(substr($digits, 0, 9)));
    }
});

test('CNHs e CNPJs do seed também são inválidos', function () {
    $cnhDigits = function (string $base): string {
        $d = array_map('intval', str_split($base));
        $first = array_sum(array_map(fn ($x, $w) => $x * $w, $d, range(9, 1))) % 11;
        $discount = 0;

        if ($first >= 10) {
            [$first, $discount] = [0, 2];
        }

        $rest = array_sum(array_map(fn ($x, $w) => $x * $w, $d, range(1, 9))) % 11;
        $second = $rest >= 10 ? 0 : $rest - $discount;
        $second = $second < 0 ? $second + 11 : $second;

        return $first.($second >= 10 ? 0 : $second);
    };

    $cnpjDigits = function (string $base): string {
        $weights = [5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];
        $d = array_map('intval', str_split($base));
        $rest = array_sum(array_map(fn ($x, $w) => $x * $w, $d, $weights)) % 11;
        $first = $rest < 2 ? 0 : 11 - $rest;
        $d[] = $first;
        $rest = array_sum(array_map(fn ($x, $w) => $x * $w, $d, [6, ...$weights])) % 11;

        return $first.($rest < 2 ? 0 : 11 - $rest);
    };

    // Sanidade: o cálculo de CNPJ reconhece um CNPJ válido de exemplo.
    expect($cnpjDigits('112223330001'))->toBe('81');

    $records = DemoRecord::query()->get();

    expect($records->whereNotNull('cnh'))->toHaveCount(5)
        ->and($records->whereNotNull('cnpj'))->toHaveCount(2);

    foreach ($records->whereNotNull('cnh') as $record) {
        expect(substr($record->cnh, 9, 2))->not->toBe($cnhDigits(substr($record->cnh, 0, 9)));
    }

    foreach ($records->whereNotNull('cnpj') as $record) {
        $digits = preg_replace('/\D+/', '', $record->cnpj);

        expect($digits)->toHaveLength(14)
            ->and(substr($digits, 12, 2))->not->toBe($cnpjDigits(substr($digits, 0, 12)));
    }
});
