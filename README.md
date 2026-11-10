# ConsultaDEMO — protótipo de consultas com dados 100% fictícios

> **AVISO LEGAL**
>
> Este projeto é um **protótipo de demonstração**. Todos os nomes, CPFs, datas, endereços, e-mails, telefones, empresas, scores e demais informações são **fictícios, sintéticos, inválidos e sem qualquer validade legal**. Nada aqui consulta bases reais, bureaus de crédito, órgãos públicos ou identifica pessoas. Os CPFs são inválidos de propósito (dígito verificador errado): aparecem completos na tela de resultado e mascarados nas listagens.

O ConsultaDEMO simula um produto de consulta cadastral: busca por nome, CPF, CNPJ, CNH, e-mail ou telefone, resultado mockado, bloco de informações ocultas com gating de plano, catálogo de módulos marcados como **DEMO**, painel de qualidade e uma API REST demonstrativa.

## O que está implementado

- Home com busca por tipo (nome, CPF, CNPJ, CNH, e-mail ou telefone, com nome + data de nascimento opcional), últimas buscas da sessão e catálogo de módulos.
- Página de resultado com dados básicos, fontes simuladas, score de confiança e bloco **Informações ocultas**.
- Modal de planos quando não há plano ativo; revelação dos dados ocultos após ativar um plano demo (sem pagamento).
- Ação **Copiar resultado** incluindo o aviso de que os dados são fictícios.
- Página de planos com ativação de plano demo e painel com registros mockados, fontes, métricas e última ingestão.
- API REST com respostas `demo: true`, aviso legal e 404 demonstrativo para IDs desconhecidos.
- Pipeline de ingestão multi-fonte: payloads brutos de "APIs de terceiros" fictícias são persistidos, mesclados e avaliados por um score de confiança.
- Seed JSON comentado, testes de feature e verificações de tipos/estilo.

## Stack

- PHP 8.3+ / Laravel 13
- Inertia.js 3 + Vue 3 + TypeScript
- Tailwind CSS 4 / Vite+
- SQLite por padrão (funciona com qualquer banco suportado pelo Laravel)
- Pest, Larastan/PHPStan e Pint

## Como rodar

```bash
cp .env.example .env
composer install
php artisan key:generate
touch database/database.sqlite

php artisan migrate
php artisan db:seed --class=DemoSeeder

npm install
npm run build
php artisan serve
```

Acesse `http://localhost:8000`.

`php artisan migrate --seed` também funciona: o `DatabaseSeeder` chama o `DemoSeeder`. O seed é idempotente para metadados (usa `updateOrCreate`) e reconstruído via ingestão a cada execução.

Buscar e consultar exigem login. O `DatabaseSeeder` cria `test@example.com` (senha `password`) como **admin**. Quem se cadastra em `/register` entra como **cliente**. Para trocar o papel de alguém:

```bash
php artisan user:role pessoa@mail.test admin     # admin, operador ou cliente
php artisan user:role pessoa@mail.test           # mostra o papel atual
```

Para desenvolvimento com hot reload:

```bash
npm run dev
php artisan serve
```

### IDs fictícios disponíveis

| ID         | Persona                                                  |
| ---------- | -------------------------------------------------------- |
| `REG-0001` | Cadastro completo em quatro fontes simuladas             |
| `REG-0002` | Três fontes com concordância de localização              |
| `REG-0003` | Quatro fontes com dados complementares                   |
| `REG-0004` | Conflito proposital de UF para demonstrar consistência   |
| `REG-0005` | Cadastro incompleto, menor score de confiança            |
| `REG-0006` | Quatro fontes recentes, usado para demonstrar score alto |

Qualquer outro ID retorna a página/JSON de 404 demonstrativo.

## Rotas

### Páginas web (Inertia)

| Método | Rota                           | Descrição                                          | Acesso               |
| ------ | ------------------------------ | -------------------------------------------------- | -------------------- |
| GET    | `/`                            | Home com busca, últimas buscas e módulos           | `consultas.realizar` |
| GET    | `/buscar?q=&tipo=&nascimento=` | Busca por nome, CPF, CNPJ, CNH, e-mail ou telefone | `consultas.realizar` |
| GET    | `/consultas/{publicId}`        | Resultado da consulta fictícia                     | `consultas.realizar` |
| GET    | `/planos`                      | Planos demo e ativação                             | público              |
| GET    | `/painel`                      | Painel de registros, fontes e qualidade            | `painel.visualizar`  |
| GET    | `/modulos`                     | Catálogo de módulos DEMO                           | público              |

Sem login, as rotas protegidas mandam para `/login`. Logado sem a permissão, retornam 403.

### API REST

| Método | Rota                                    | Descrição                                               | Acesso               |
| ------ | --------------------------------------- | ------------------------------------------------------- | -------------------- |
| GET    | `/api/demo/registros`                   | Lista os registros fictícios                            | `painel.visualizar`  |
| GET    | `/api/demo/busca?q=&tipo=&nascimento=`  | Busca nas fontes compatíveis; resultados com `found_in` | `consultas.realizar` |
| GET    | `/api/demo/consulta/{publicId}`         | Detalhe da consulta (oculta dados sem plano)            | `consultas.realizar` |
| GET    | `/api/demo/status`                      | Status do plano demo na sessão                          | público              |
| POST   | `/api/demo/plano/ativar`                | Ativa um plano demo, sem pagamento                      | público              |
| POST   | `/api/demo/consulta/{publicId}/avaliar` | Registra feedback da consulta                           | `consultas.realizar` |
| POST   | `/api/demo/sincronizar`                 | Reexecuta a ingestão simulada (throttle 6/min)          | `fontes.sincronizar` |
| GET    | `/api/demo/modulos`                     | Lista módulos DEMO                                      | público              |
| GET    | `/api/demo/planos`                      | Lista planos DEMO                                       | público              |

As rotas `/api/demo/*` ficam no middleware `web` de propósito: o plano é mantido na sessão e a ativação usa cookie + CSRF. Em chamadas de fora do navegador, envie `Accept: application/json` e obtenha o cookie/`XSRF-TOKEN` antes do `POST`. As rotas protegidas usam a mesma sessão do login: sem ela, respondem 401; sem a permissão, 403.

Exemplos (rotas públicas):

```bash
curl -s http://localhost:8000/api/demo/modulos | jq
curl -s http://localhost:8000/api/demo/planos | jq
```

Resposta de `/api/demo/consulta/{publicId}` com ID inexistente (HTTP 404, logado):

```json
{
    "demo": true,
    "disclaimer": "Dados fictícios: nomes, documentos, endereços, contatos e scores não correspondem a pessoas reais.",
    "error": "demo_record_not_found",
    "message": "Registro não encontrado. Use um ID existente.",
    "available_ids": [
        "REG-0001",
        "REG-0002",
        "REG-0003",
        "REG-0004",
        "REG-0005",
        "REG-0006"
    ]
}
```

Ativação de plano:

```json
POST /api/demo/plano/ativar
{ "plan_slug": "demo-pro" }
```

Sem plano ativo, `data.hidden` é `null` e `data.hidden_locked` é `true`; os valores premium nunca são serializados. Com plano ativo, o bloco oculto é revelado.

## Como funciona o backend demonstrativo

O código segue as convenções do Laravel (monólito Inertia), com o domínio demo isolado em `app/Demo`. Os diretórios [`backend/`](backend/README.md) e [`frontend/`](frontend/README.md) documentam o mapa entre a organização pedida e os caminhos canônicos do framework.

### Ingestão multi-fonte

1. `DemoProvider` define o contrato de um provedor terceiro fictício.
2. `SeedJsonProvider` lê `database/data/demo_seed.json` e simula a resposta de cada fonte: `fetch()` devolve tudo (ingestão) e `search()` responde a uma consulta (busca).
3. `DemoIngestionService` grava cada payload bruto em `demo_record_sources` e registra a execução em `demo_ingestion_runs`. Payloads que uma fonte deixou de devolver são removidos, e registros sem nenhuma fonte saem da base (com as consultas). Uma resposta vazia do provedor não apaga nada.
4. `DemoMergeService` escolhe o valor de maior confiabilidade por campo, coleta dados `_hidden` e monta o registro canônico em `demo_records`.
5. `DemoTrustScoreService` avalia o resultado depois da consulta/mesclagem.

```bash
php artisan demo:ingest
php artisan demo:ingest --source=cadastro-civil-demo
```

### Busca

A busca vai a **todas as fontes compatíveis**, no momento da busca. Cada fonte declara em `search_types` por quais dados aceita ser consultada:

| Fonte          | Nome | CPF | CNH | CNPJ | E-mail | Telefone |
| -------------- | :--: | :-: | :-: | :--: | :----: | :------: |
| Cadastro Civil |  ✓   |  ✓  |  ✓  |      |        |          |
| Contatos       |  ✓   |  ✓  |     |      |   ✓    |    ✓     |
| Localização    |  ✓   |  ✓  |     |      |        |          |
| Crédito        |      |  ✓  |     |  ✓   |        |          |

Buscar um nome consulta Cadastro Civil, Contatos e Localização. Buscar um CPF consulta as quatro. Para cada fonte ativa compatível, `DemoSearchService`:

1. chama `search(tipo, termo)` do provedor da fonte;
2. grava o que a fonte devolveu em `demo_record_sources`, como na ingestão;
3. mescla de novo os registros encontrados (`DemoIngestionService::mergeRecords`);
4. devolve cada registro com `found_in` (as fontes que o encontraram) e um relatório por fonte consultada: quantidade de registros, status e tempo (`searchedSources` na página e `meta.sources` na API).

Uma fonte que falha (exceção do provedor) aparece como `error` no relatório e a busca segue nas outras. Fonte inativa não é consultada.

O tipo é **obrigatório**: o select ao lado do campo tem Nome (padrão), CPF, CNPJ, CNH, E-mail e Telefone (`tipo=name|cpf|cnpj|cnh|email|phone` na URL e na API). Não há detecção automática nem busca por ID; um registro é aberto pelo ID direto em `/consultas/{publicId}`. Sem tipo, ou com tipo desconhecido, a página volta para a home com um aviso; a API responde 422.

| Tipo                       | Exemplo                                               | Comportamento                                                           |
| -------------------------- | ----------------------------------------------------- | ----------------------------------------------------------------------- |
| Nome                       | `aurora lacerda`                                      | Parcial, sem acento nem maiúsculas; todas as palavras precisam aparecer |
| CPF, CNPJ, CNH ou telefone | `301.482.115-10`, `12905330000130`, `(11) 90000-0001` | Compara só os dígitos; com um único resultado, abre a consulta          |
| E-mail                     | `bento.rocha@mail.test`                               | Igualdade sem diferenciar maiúsculas; com um único resultado, abre      |

A comparação (`DemoSearchNormalizer`) ignora acento, maiúsculas e pontuação, e o telefone ignora o +55. Os provedores só pesquisam campos visíveis: os dados ocultos do plano (`_hidden`) nunca são pesquisáveis. Os resultados mostram o CPF mascarado; o CPF completo aparece só na tela da consulta.

Para ligar uma fonte a outro provedor (por exemplo, um cliente HTTP), implemente `DemoProvider` e registre com `DemoProviderRegistry::register(slug, provedor)`.

#### Busca composta (nome + data de nascimento)

Um nome curto ("heitor") pode trazer muitos registros. O campo opcional **Nascimento**, ao lado da busca (tipo Nome), deixa só quem nasceu naquela data: `nascimento=AAAA-MM-DD` na URL e na API.

- O filtro vai para cada fonte (`search(tipo, termo, filtros)`), que aplica o que conhece para devolver menos registros. Hoje só o Cadastro Civil tem data de nascimento; Contatos e Localização devolvem todos os nomes que casam.
- Antes de gravar, a busca confere a data de cada registro: vale a do payload mais confiável que a tiver ou, se nenhuma fonte desta busca a devolveu, a do registro já mesclado. Sem data conhecida, o registro não entra.
- Quem não passa no filtro não é gravado nem mostrado, e a contagem por fonte já desconta os descartados.
- Data inválida (`1990-02-31`) ou no futuro: a página volta para a home com um aviso; a API responde 422.

### Score de confiança

O score (0–100) combina quatro dimensões:

| Dimensão             | Peso | Como é calculada                                                                            |
| -------------------- | ---- | ------------------------------------------------------------------------------------------- |
| Qualidade das fontes | 35%  | média da confiabilidade das fontes que cobrem o registro                                    |
| Completude           | 25%  | campos preenchidos / campos canônicos                                                       |
| Consistência         | 25%  | concordância entre fontes nos campos cobertos por 2+ fontes (70 quando não há sobreposição) |
| Atualidade           | 15%  | idade do payload mais recente, com piso de 40                                               |

Rótulos: **Alto** (≥ 85), **Moderado** (65–84) e **Baixo** (< 65). O detalhamento fica em `trust_breakdown` e é exibido na interface.

### Plano e dados ocultos

- O plano ativo fica em `session('demo.plan')` com slug, ativação e expiração.
- A ativação é registrada em `demo_plan_activations` (sem cobrança).
- `DemoPlanService::hasHiddenAccess()` decide se `hidden_data` pode ser serializado.
- As rotas de página e API usam o mesmo serviço, então o gating é idêntico nos dois canais.

### Papéis e permissões

Usa o [spatie/laravel-permission](https://spatie.be/docs/laravel-permission). Os papéis e o que cada um pode fazer estão em `app/Enums/Role.php` e `app/Enums/Permission.php`:

| Papel      | `consultas.realizar` | `painel.visualizar` | `fontes.sincronizar` |
| ---------- | :------------------: | :-----------------: | :------------------: |
| `admin`    |          ✓           |          ✓          |          ✓           |
| `operador` |          ✓           |          ✓          |                      |
| `cliente`  |          ✓           |                     |                      |

- As rotas checam a permissão com o middleware `can:` e também exigem e-mail verificado (`verified`).
- Os papéis são criados por uma migration, então existem em qualquer ambiente depois do `migrate`. Depois de mudar os enums, rode `php artisan db:seed --class=RolesAndPermissionsSeeder` para sincronizar.
- Cada usuário tem um papel. Todo cadastro novo recebe `cliente`.
- O front recebe `auth.roles` e `auth.permissions` em toda página. `usePermissions().can()` esconde o que o usuário não pode usar, como o item Painel e o botão de sincronizar. Isso só afeta a interface: quem bloqueia o acesso são as rotas.

### Nomes realistas e aviso discreto

Os valores exibidos (nomes, endereços, fontes, módulos e planos) não usam marcadores como "Demo" ou "Fictício". O que garante que nenhum registro é uma pessoa real:

- CPF, CNH e CNPJ com dígito verificador inválido (há testes cobrindo os três);
- e-mails no domínio reservado `.test`, que não recebe mensagens.

O aviso de dados fictícios continua, de forma discreta: uma linha no rodapé da sidebar, a última linha do texto copiado e o campo `disclaimer` em todas as respostas da API.

### Seed JSON comentado

JSON estrito não aceita comentários. O arquivo `database/data/demo_seed.json` documenta cada decisão em:

- `_meta.description`, `_meta.disclaimer` e `_meta.schema_version`;
- `_meta.field_notes` (CPF, nomes, dados ocultos, idade dos payloads);
- `notes` por fonte, módulo, plano e registro.

O arquivo contém 6 registros, 4 fontes, 6 módulos e 3 planos — todos inventados.

## Estrutura principal

```text
app/
  Console/Commands/DemoIngestCommand.php
  Console/Commands/UserRoleCommand.php   # user:role
  Enums/Role.php, Permission.php         # papéis e permissões
  Demo/                      # domínio demonstrativo
    Consultation/            # registro e feedback de consultas
    Data/                    # leitura do seed JSON
    Ingestion/               # coleta + persistência de payloads
    Merge/                   # merge multi-fonte + trust score
    Plans/                   # plano em sessão e gating
    Providers/               # contrato, registry e provider JSON
    Search/                  # busca nas fontes compatíveis + normalização
  Http/Controllers/Demo/     # controllers Inertia e API
  Models/Demo*.php           # modelos do protótipo
database/
  data/demo_seed.json        # seed sintético comentado
  migrations/*demo*          # 8 tabelas do protótipo
  seeders/DemoSeeder.php     # metadados + ingestão
  seeders/RolesAndPermissionsSeeder.php  # sincroniza papéis com os enums
resources/js/
  pages/demo/                # Home, Consulta, Planos, Painel, Modulos, NotFound
  components/demo/           # badge, modal de plano, trust score
  layouts/DemoLayout.vue
  lib/demoApi.ts             # cliente fetch com CSRF
  types/demo.ts
tests/Feature/Demo/          # API e páginas
```

## Testes e qualidade

```bash
php artisan test
php artisan test tests/Feature/Demo

vendor/bin/phpstan analyse --memory-limit=512M
npm run types:check
vendor/bin/pint --test
```

Cobertura dos testes de feature:

- busca válida com dados fictícios, fontes e score;
- busca em todas as fontes compatíveis, fonte inativa, fonte fora do ar e gravação do que a fonte devolveu (`DemoSourceSearchTest`);
- busca composta por nome + data de nascimento, sem gravar quem não passa no filtro (`DemoCompoundSearchTest`);
- busca inválida com 404 demonstrativo;
- ativação de plano demo sem pagamento;
- bloqueio de informações ocultas sem plano e revelação com plano;
- avaliação de consulta após a busca;
- registro incompleto com score menor;
- páginas Inertia de home, consulta, planos, painel, módulos e 404;
- acesso por papel: visitante, cliente, operador, admin, usuário sem papel e e-mail não verificado (`DemoAccessTest`);
- comando `user:role`.

## Limitações intencionais

- Não há cobrança, integração real ou persistência de plano em banco: o plano continua na sessão, sem ligação com o usuário.
- Ainda não há tela de administração; papéis são trocados pelo comando `user:role`.
- Os "provedores" são locais e leem o mesmo seed JSON; a arquitetura existe para demonstrar como múltiplas fontes seriam ingeridas, armazenadas e mescladas.
- Nenhum dado deve ser usado para identificar, avaliar ou tomar decisões sobre pessoas.
