<?php

use App\Demo\Providers\HttpApiProvider;
use App\Demo\Search\DemoSearchNormalizer;
use App\Models\DemoSource;
use Illuminate\Support\Facades\Http;

function makeProvider(array $apiConfig, string $slug = 'api-externa'): HttpApiProvider
{
    return new HttpApiProvider($slug, $apiConfig, new DemoSearchNormalizer);
}

function baseConfig(array $overrides = []): array
{
    return array_replace_recursive([
        'base_url' => 'https://api.example.com',
        'auth' => ['type' => 'none'],
        'id_prefix' => 'EXT',
        'endpoints' => [
            'fetch' => [
                'method' => 'GET',
                'path' => '/records',
                'payload_root' => 'data',
                'id_field' => 'id',
            ],
            'search' => [
                'method' => 'GET',
                'path' => '/search',
                'query_param' => 'q',
                'type_param' => 'type',
                'payload_root' => 'data',
                'id_field' => 'id',
            ],
        ],
        'field_map' => [
            'nome' => 'full_name',
            'documento' => 'cpf',
            'nascimento' => 'birth_date',
        ],
    ], $overrides);
}

test('fetch mapeia campos da API externa para o formato interno', function () {
    Http::fake([
        'api.example.com/records' => Http::response([
            'data' => [
                ['id' => 'abc-1', 'nome' => 'Maria Silva', 'documento' => '12345678900', 'nascimento' => '1990-05-10'],
                ['id' => 'abc-2', 'nome' => 'João Santos', 'documento' => '98765432100', 'nascimento' => '1985-03-22'],
            ],
        ]),
    ]);

    $provider = makeProvider(baseConfig());
    $payloads = $provider->fetch();

    expect($payloads)->toHaveCount(2)
        ->and($payloads[0]['public_id'])->toBe('EXT-0001')
        ->and($payloads[0]['external_ref'])->toBe('abc-1')
        ->and($payloads[0]['payload'])->toBe([
            'full_name' => 'Maria Silva',
            'cpf' => '12345678900',
            'birth_date' => '1990-05-10',
        ])
        ->and($payloads[1]['public_id'])->toBe('EXT-0002')
        ->and($payloads[1]['payload']['full_name'])->toBe('João Santos');
});

test('fetch retorna lista vazia quando endpoint não está configurado', function () {
    $config = baseConfig();
    unset($config['endpoints']['fetch']);

    $provider = makeProvider($config);

    expect($provider->fetch())->toBe([]);
});

test('search envia tipo e termo para a API', function () {
    Http::fake([
        'api.example.com/search*' => Http::response([
            'data' => [
                ['id' => 'x-1', 'nome' => 'Aurora Lacerda', 'documento' => '30148211510'],
            ],
        ]),
    ]);

    $provider = makeProvider(baseConfig());
    $results = $provider->search('name', 'Aurora');

    expect($results)->toHaveCount(1)
        ->and($results[0]['payload']['full_name'])->toBe('Aurora Lacerda');

    Http::assertSent(function ($request) {
        return str_contains($request->url(), 'q=Aurora')
            && str_contains($request->url(), 'type=name');
    });
});

test('search sem endpoint de busca faz fetch e filtra localmente', function () {
    Http::fake([
        'api.example.com/records' => Http::response([
            'data' => [
                ['id' => '1', 'nome' => 'Aurora Lacerda', 'documento' => '30148211510'],
                ['id' => '2', 'nome' => 'Bento Rocha', 'documento' => '99988877766'],
            ],
        ]),
    ]);

    $config = baseConfig();
    unset($config['endpoints']['search']);

    $provider = makeProvider($config);
    $results = $provider->search('name', 'Aurora');

    expect($results)->toHaveCount(1)
        ->and($results[0]['payload']['full_name'])->toBe('Aurora Lacerda');
});

test('autenticação bearer envia token no header', function () {
    Http::fake([
        'api.example.com/records' => Http::response(['data' => []]),
    ]);

    $provider = makeProvider(baseConfig([
        'auth' => ['type' => 'bearer', 'token' => 'meu-token-secreto'],
    ]));

    $provider->fetch();

    Http::assertSent(function ($request) {
        return $request->hasHeader('Authorization', 'Bearer meu-token-secreto');
    });
});

test('autenticação api_key envia chave no header configurado', function () {
    Http::fake([
        'api.example.com/records' => Http::response(['data' => []]),
    ]);

    $provider = makeProvider(baseConfig([
        'auth' => ['type' => 'api_key', 'header' => 'X-Token', 'key' => 'chave-123'],
    ]));

    $provider->fetch();

    Http::assertSent(function ($request) {
        return $request->hasHeader('X-Token', 'chave-123');
    });
});

test('segredos com prefixo env: são resolvidos via variável de ambiente', function () {
    Http::fake([
        'api.example.com/records' => Http::response(['data' => []]),
    ]);

    putenv('TEST_API_TOKEN=token-do-env');

    $provider = makeProvider(baseConfig([
        'auth' => ['type' => 'bearer', 'token' => 'env:TEST_API_TOKEN'],
    ]));

    $provider->fetch();

    Http::assertSent(function ($request) {
        return $request->hasHeader('Authorization', 'Bearer token-do-env');
    });

    putenv('TEST_API_TOKEN');
});

test('field_map vazio repassa o payload original', function () {
    Http::fake([
        'api.example.com/records' => Http::response([
            'data' => [
                ['id' => '1', 'full_name' => 'Direto', 'cpf' => '11122233344'],
            ],
        ]),
    ]);

    $config = baseConfig();
    $config['field_map'] = [];

    $provider = makeProvider($config);
    $payloads = $provider->fetch();

    expect($payloads[0]['payload'])->toHaveKeys(['full_name', 'cpf'])
        ->and($payloads[0]['payload']['full_name'])->toBe('Direto');
});

test('payload_root com dot notation extrai dados aninhados', function () {
    Http::fake([
        'api.example.com/records' => Http::response([
            'response' => [
                'items' => [
                    ['id' => '1', 'nome' => 'Aninhado'],
                ],
            ],
        ]),
    ]);

    $provider = makeProvider(baseConfig([
        'endpoints' => [
            'fetch' => [
                'method' => 'GET',
                'path' => '/records',
                'payload_root' => 'response.items',
                'id_field' => 'id',
            ],
        ],
    ]));

    $payloads = $provider->fetch();

    expect($payloads)->toHaveCount(1)
        ->and($payloads[0]['payload']['full_name'])->toBe('Aninhado');
});

test('filtro de data de nascimento é aplicado localmente', function () {
    Http::fake([
        'api.example.com/search*' => Http::response([
            'data' => [
                ['id' => '1', 'nome' => 'Maria Silva', 'nascimento' => '1990-05-10'],
                ['id' => '2', 'nome' => 'Maria Oliveira', 'nascimento' => '1985-03-22'],
            ],
        ]),
    ]);

    $provider = makeProvider(baseConfig());
    $results = $provider->search('name', 'Maria', ['birth_date' => '1990-05-10']);

    expect($results)->toHaveCount(1)
        ->and($results[0]['payload']['full_name'])->toBe('Maria Silva');
});

test('registry resolve HttpApiProvider quando fonte tem api_config', function () {
    DemoSource::create([
        'slug' => 'api-teste',
        'name' => 'API Teste',
        'category' => 'API',
        'reliability' => 80,
        'search_types' => ['name', 'cpf'],
        'is_active' => true,
        'api_config' => baseConfig(),
    ]);

    $registry = app(\App\Demo\Providers\DemoProviderRegistry::class);
    $provider = $registry->for('api-teste');

    expect($provider)->toBeInstanceOf(HttpApiProvider::class)
        ->and($provider->slug())->toBe('api-teste');
});

test('registry continua usando SeedJsonProvider quando fonte não tem api_config', function () {
    $registry = app(\App\Demo\Providers\DemoProviderRegistry::class);

    DemoSource::create([
        'slug' => 'cadastro-civil-demo',
        'name' => 'Cadastro Civil',
        'category' => 'Registro',
        'reliability' => 92,
        'search_types' => ['name', 'cpf', 'cnh'],
        'is_active' => true,
    ]);

    $provider = $registry->for('cadastro-civil-demo');

    expect($provider)->toBeInstanceOf(\App\Demo\Providers\SeedJsonProvider::class);
});
