<?php

use App\Enums\Role;
use App\Models\DemoSource;
use Database\Seeders\DemoSeeder;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    $this->seed(DemoSeeder::class);
});

test('admin pode listar fontes', function () {
    $this->actingAs(userWithRole(Role::Admin))
        ->get('/fontes')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('demo/sources/Index')
            ->has('sources', DemoSource::count())
        );
});

test('cliente não pode acessar fontes', function () {
    $this->actingAs(userWithRole(Role::Customer))
        ->get('/fontes')
        ->assertForbidden();
});

test('operador não pode acessar fontes', function () {
    $this->actingAs(userWithRole(Role::Operator))
        ->get('/fontes')
        ->assertForbidden();
});

test('admin pode criar fonte sem api_config', function () {
    $this->actingAs(userWithRole(Role::Admin))
        ->post('/fontes', [
            'slug' => 'nova-fonte',
            'name' => 'Nova Fonte',
            'category' => 'Teste',
            'reliability' => 80,
            'search_types' => ['name', 'cpf'],
            'is_active' => true,
        ])
        ->assertRedirect('/fontes');

    $source = DemoSource::where('slug', 'nova-fonte')->first();
    expect($source)->not->toBeNull()
        ->and($source->name)->toBe('Nova Fonte')
        ->and($source->reliability)->toBe(80)
        ->and($source->search_types)->toBe(['name', 'cpf'])
        ->and($source->api_config)->toBeNull();
});

test('admin pode criar fonte com api_config', function () {
    Http::fake(['api.example.com/*' => Http::response(['data' => []])]);

    $this->actingAs(userWithRole(Role::Admin))
        ->post('/fontes', [
            'slug' => 'api-externa',
            'name' => 'API Externa',
            'category' => 'API',
            'reliability' => 75,
            'search_types' => ['name'],
            'is_active' => true,
            'api_config' => [
                'base_url' => 'https://api.example.com',
                'timeout' => 30,
                'id_prefix' => 'EXT',
                'auth' => ['type' => 'bearer', 'token' => 'env:MY_TOKEN'],
                'endpoints' => [
                    'fetch' => [
                        'method' => 'GET',
                        'path' => '/records',
                        'payload_root' => 'data',
                        'id_field' => 'id',
                    ],
                ],
                'field_map' => ['nome' => 'full_name'],
            ],
        ])
        ->assertRedirect('/fontes');

    $source = DemoSource::where('slug', 'api-externa')->first();
    expect($source)->not->toBeNull()
        ->and($source->api_config)->toBeArray()
        ->and($source->api_config['base_url'])->toBe('https://api.example.com')
        ->and($source->api_config['auth']['type'])->toBe('bearer');
});

test('admin pode editar fonte existente', function () {
    $source = DemoSource::first();

    $this->actingAs(userWithRole(Role::Admin))
        ->get("/fontes/{$source->slug}/editar")
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('demo/sources/Form')
            ->where('source.slug', $source->slug)
        );
});

test('admin pode atualizar fonte', function () {
    $source = DemoSource::first();

    $this->actingAs(userWithRole(Role::Admin))
        ->put("/fontes/{$source->slug}", [
            'slug' => $source->slug,
            'name' => 'Nome Atualizado',
            'category' => $source->category,
            'reliability' => 99,
            'search_types' => $source->search_types,
            'is_active' => true,
        ])
        ->assertRedirect('/fontes');

    $source->refresh();
    expect($source->name)->toBe('Nome Atualizado')
        ->and($source->reliability)->toBe(99);
});

test('admin pode remover fonte', function () {
    $source = DemoSource::first();
    $slug = $source->slug;

    $this->actingAs(userWithRole(Role::Admin))
        ->delete("/fontes/{$slug}")
        ->assertRedirect('/fontes');

    expect(DemoSource::where('slug', $slug)->exists())->toBeFalse();
});

test('validação exige campos obrigatórios', function () {
    $this->actingAs(userWithRole(Role::Admin))
        ->post('/fontes', [])
        ->assertSessionHasErrors(['slug', 'name', 'category', 'reliability', 'search_types']);
});

test('slug deve ser único', function () {
    $source = DemoSource::first();

    $this->actingAs(userWithRole(Role::Admin))
        ->post('/fontes', [
            'slug' => $source->slug,
            'name' => 'Duplicada',
            'category' => 'Teste',
            'reliability' => 50,
            'search_types' => ['name'],
        ])
        ->assertSessionHasErrors('slug');
});

test('slug permite mesmo valor ao editar a própria fonte', function () {
    $source = DemoSource::first();

    $this->actingAs(userWithRole(Role::Admin))
        ->put("/fontes/{$source->slug}", [
            'slug' => $source->slug,
            'name' => $source->name,
            'category' => $source->category,
            'reliability' => $source->reliability,
            'search_types' => $source->search_types,
        ])
        ->assertRedirect('/fontes');
});

test('api_config sem base_url é tratado como null', function () {
    $this->actingAs(userWithRole(Role::Admin))
        ->post('/fontes', [
            'slug' => 'sem-api',
            'name' => 'Sem API',
            'category' => 'Teste',
            'reliability' => 60,
            'search_types' => ['name'],
            'api_config' => ['base_url' => ''],
        ])
        ->assertRedirect('/fontes');

    expect(DemoSource::where('slug', 'sem-api')->first()->api_config)->toBeNull();
});
