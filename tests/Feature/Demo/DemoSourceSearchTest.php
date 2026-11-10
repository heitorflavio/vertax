<?php

use App\Demo\Ingestion\DemoIngestionService;
use App\Demo\Providers\DemoProvider;
use App\Demo\Providers\DemoProviderRegistry;
use App\Enums\Role;
use App\Models\DemoRecord;
use App\Models\DemoRecordSource;
use App\Models\DemoSource;
use Database\Seeders\DemoSeeder;
use Illuminate\Support\Facades\Exceptions;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed(DemoSeeder::class);
    $this->actingAs(userWithRole(Role::Customer));
});

/**
 * @param  list<array{slug: string}>  $sources
 * @return list<string>
 */
function sourceSlugs(array $sources): array
{
    return array_column($sources, 'slug');
}

test('busca por nome consulta todas as fontes que aceitam nome', function () {
    $this->get('/buscar?q=aurora&tipo=name')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('demo/Busca')
            ->where('searchedSources', fn ($sources) => sourceSlugs($sources->all()) === [
                'cadastro-civil-demo',
                'contato-sintetico',
                'localizacao-mock',
            ])
            ->where('searchedSources.0.status', 'ok')
            ->where('searchedSources.0.hits', 1)
            ->has('results', 1)
            ->where('results.0.public_id', 'REG-0001')
            ->where('results.0.found_in', fn ($foundIn) => sourceSlugs($foundIn->all()) === [
                'cadastro-civil-demo',
                'contato-sintetico',
                'localizacao-mock',
            ])
        );
});

test('busca por CPF consulta as quatro fontes', function () {
    $response = $this->getJson('/api/demo/busca?q=301.482.115-10&tipo=cpf')
        ->assertOk()
        ->assertJsonPath('meta.total', 1)
        ->assertJsonPath('data.0.public_id', 'REG-0001');

    expect(sourceSlugs($response->json('meta.sources')))->toBe([
        'cadastro-civil-demo',
        'contato-sintetico',
        'localizacao-mock',
        'credito-ficticio',
    ])
        ->and($response->json('data.0.found_in'))->toHaveCount(4);
});

test('cada tipo só vai para as fontes que o aceitam', function (string $type, string $query, array $slugs) {
    $response = $this->getJson('/api/demo/busca?'.http_build_query(['q' => $query, 'tipo' => $type]))
        ->assertOk()
        ->assertJsonPath('meta.total', 1);

    expect(sourceSlugs($response->json('meta.sources')))->toBe($slugs);
})->with([
    'nome' => ['name', 'aurora', ['cadastro-civil-demo', 'contato-sintetico', 'localizacao-mock']],
    'CNH' => ['cnh', '10482300110', ['cadastro-civil-demo']],
    'CNPJ' => ['cnpj', '12905330000130', ['credito-ficticio']],
    'e-mail' => ['email', 'aurora.lacerda@mail.test', ['contato-sintetico']],
    'telefone' => ['phone', '(11) 90000-0001', ['contato-sintetico']],
]);

test('fonte inativa não é consultada', function () {
    DemoSource::query()->where('slug', 'localizacao-mock')->update(['is_active' => false]);

    $this->getJson('/api/demo/busca?q=aurora&tipo=name')
        ->assertOk()
        ->assertJsonCount(2, 'meta.sources')
        ->assertJsonCount(2, 'data.0.found_in');
});

test('fonte fora do ar não derruba a busca nas outras', function () {
    Exceptions::fake();

    $registry = app(DemoProviderRegistry::class);
    $registry->register('contato-sintetico', new class implements DemoProvider
    {
        public function slug(): string
        {
            return 'contato-sintetico';
        }

        public function fetch(): array
        {
            return [];
        }

        public function search(string $type, string $term, array $filters = []): array
        {
            throw new RuntimeException('Tempo esgotado.');
        }
    });
    $this->app->instance(DemoProviderRegistry::class, $registry);

    $response = $this->getJson('/api/demo/busca?q=aurora&tipo=name')
        ->assertOk()
        ->assertJsonPath('meta.total', 1)
        ->assertJsonPath('meta.sources.1.slug', 'contato-sintetico')
        ->assertJsonPath('meta.sources.1.status', 'error')
        ->assertJsonPath('meta.sources.1.hits', 0);

    expect(sourceSlugs($response->json('data.0.found_in')))->toBe([
        'cadastro-civil-demo',
        'localizacao-mock',
    ]);

    Exceptions::assertReported(RuntimeException::class);
});

test('a busca grava o que a fonte devolveu e atualiza o registro', function () {
    $contact = DemoSource::query()->where('slug', 'contato-sintetico')->firstOrFail();

    // Simula um registro que ainda não tinha vindo da fonte de contatos.
    DemoRecordSource::query()
        ->where('public_id', 'REG-0001')
        ->where('demo_source_id', $contact->id)
        ->delete();
    app(DemoIngestionService::class)->mergeRecords(['REG-0001']);

    expect(DemoRecord::query()->where('public_id', 'REG-0001')->value('email'))->toBeNull();

    $this->getJson('/api/demo/busca?q=aurora.lacerda@mail.test&tipo=email')
        ->assertOk()
        ->assertJsonPath('data.0.public_id', 'REG-0001')
        ->assertJsonPath('data.0.found_in.0.slug', 'contato-sintetico');

    expect(DemoRecordSource::query()
        ->where('public_id', 'REG-0001')
        ->where('demo_source_id', $contact->id)
        ->exists())->toBeTrue()
        ->and(DemoRecord::query()->where('public_id', 'REG-0001')->value('email'))
        ->toBe('aurora.lacerda@mail.test');
});

test('painel mostra por quais dados cada fonte aceita busca', function () {
    $this->actingAs(userWithRole(Role::Operator))
        ->get('/painel')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('sources.0.slug', 'cadastro-civil-demo')
            ->where('sources.0.search_types', ['Nome', 'CPF', 'CNH'])
            ->where('sources.3.slug', 'credito-ficticio')
            ->where('sources.3.search_types', ['CPF', 'CNPJ'])
        );
});
