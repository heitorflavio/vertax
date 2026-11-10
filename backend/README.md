# Backend do ConsultaDEMO

> Todos os dados deste backend são fictícios, sintéticos e inválidos. Não há integração com bases reais.

Este diretório é um **índice de documentação** do backend demonstrativo. O código vive nos caminhos canônicos do Laravel, para preservar autoload, Artisan, migrations, Inertia e convenções do framework. O mapa abaixo liga a organização pedida (`backend/`) aos arquivos reais.

## Mapa de caminhos

| Área                              | Caminho real                                                                              |
| --------------------------------- | ----------------------------------------------------------------------------------------- |
| Configuração e labels do domínio  | `app/Demo/DemoConfig.php`                                                                 |
| Serialização para UI/API          | `app/Demo/DemoPresenter.php`                                                              |
| Leitura do seed comentado         | `app/Demo/Data/DemoSeedRepository.php`                                                    |
| Coleta e persistência de payloads | `app/Demo/Ingestion/DemoIngestionService.php`                                             |
| Contrato e registry de provedores | `app/Demo/Providers/DemoProvider.php`, `DemoProviderRegistry.php`, `SeedJsonProvider.php` |
| Merge multi-fonte                 | `app/Demo/Merge/DemoMergeService.php`                                                     |
| Score de confiança                | `app/Demo/Merge/DemoTrustScoreService.php`                                                |
| Consultas e feedback              | `app/Demo/Consultation/DemoConsultationService.php`                                       |
| Busca nas fontes compatíveis      | `app/Demo/Search/DemoSearchService.php`, `DemoSearchNormalizer.php`                       |
| Plano em sessão e gating          | `app/Demo/Plans/DemoPlanService.php`                                                      |
| Controllers web/API               | `app/Http/Controllers/Demo/`                                                              |
| Modelos                           | `app/Models/Demo*.php`                                                                    |
| Comando de ingestão               | `app/Console/Commands/DemoIngestCommand.php`                                              |
| Papéis e permissões               | `app/Enums/Role.php`, `app/Enums/Permission.php`                                          |
| Sincronização de papéis           | `database/seeders/RolesAndPermissionsSeeder.php`                                          |
| Comando de papel do usuário       | `app/Console/Commands/UserRoleCommand.php`                                                |
| Migrations                        | `database/migrations/*demo*`                                                              |
| Seed sintético                    | `database/data/demo_seed.json`                                                            |
| Seeder                            | `database/seeders/DemoSeeder.php`                                                         |
| Rotas                             | `routes/web.php`                                                                          |
| Testes                            | `tests/Feature/Demo/`                                                                     |

## Modelo de dados

Oito tabelas, todas com prefixo `demo_`:

- `demo_sources`: fontes fictícias, confiabilidade e `search_types` (por quais dados cada fonte aceita busca).
- `demo_modules`: catálogo de módulos marcados como DEMO.
- `demo_plans`: planos fictícios, duração e liberação de dados ocultos.
- `demo_records`: registro canônico mesclado.
- `demo_record_sources`: payload bruto por registro/fonte + campos contribuídos.
- `demo_consultations`: consultas registradas, score no momento e feedback.
- `demo_plan_activations`: auditoria de ativações de plano (sem pagamento).
- `demo_ingestion_runs`: execuções de ingestão, contadores, duração e status.

## Pipeline

```text
demo_seed.json
    │  (SeedJsonProvider — simula API de terceiro)
    │    fetch():  ingestão completa (demo:ingest, seeder, botão do painel)
    │    search(): busca, só nas fontes cujo search_types aceita o tipo do termo
    ▼
demo_record_sources  ── payload bruto, confiabilidade, fetched_at
    │  (DemoMergeService: maior confiabilidade por campo + _hidden)
    ▼
demo_records         ── registro canônico, hidden_data, trust_breakdown
    │  (DemoTrustScoreService)
    ▼
UI / API             ── hidden_data só é serializado com plano ativo
```

## Comandos

```bash
php artisan migrate
php artisan db:seed --class=DemoSeeder   # metadados + ingestão completa
php artisan demo:ingest                  # reingestão de todas as fontes
php artisan demo:ingest --source=cadastro-civil-demo
php artisan db:seed --class=RolesAndPermissionsSeeder  # ressincroniza papéis com os enums
php artisan user:role pessoa@mail.test admin           # admin, operador ou cliente
```

## API

Todas as respostas incluem `demo: true` e o aviso legal. O plano é mantido na sessão, por isso as rotas de API estão no grupo `web` (cookie + CSRF).

| Método | Rota                                    | Acesso               |
| ------ | --------------------------------------- | -------------------- |
| GET    | `/api/demo/registros`                   | `painel.visualizar`  |
| GET    | `/api/demo/busca?q=&tipo=&nascimento=`  | `consultas.realizar` |
| GET    | `/api/demo/consulta/{publicId}`         | `consultas.realizar` |
| GET    | `/api/demo/status`                      | público              |
| POST   | `/api/demo/plano/ativar`                | público              |
| POST   | `/api/demo/consulta/{publicId}/avaliar` | `consultas.realizar` |
| POST   | `/api/demo/sincronizar`                 | `fontes.sincronizar` |
| GET    | `/api/demo/modulos`                     | público              |
| GET    | `/api/demo/planos`                      | público              |

Rotas protegidas exigem login com e-mail verificado: sem sessão respondem 401; sem a permissão, 403. O que cada papel recebe está na seção "Papéis e permissões" do [README principal](../README.md).

IDs desconhecidos retornam `404` com `error: "demo_record_not_found"` e a lista de IDs válidos.

## Qualidade

```bash
php artisan test tests/Feature/Demo
vendor/bin/phpstan analyse --memory-limit=512M
vendor/bin/pint --test
```
