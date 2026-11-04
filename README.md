# ConsultaDEMO — protótipo de consultas com dados 100% fictícios

> **AVISO LEGAL**
>
> Este projeto é um **protótipo de demonstração**. Todos os nomes, CPFs, datas, endereços, e-mails, telefones, empresas, scores e demais informações são **fictícios, sintéticos, inválidos e sem qualquer validade legal**. Nada aqui consulta bases reais, bureaus de crédito, órgãos públicos ou identifica pessoas. Os CPFs são inválidos de propósito (dígito verificador errado): aparecem completos na tela de resultado e mascarados nas listagens.

O ConsultaDEMO simula um produto de consulta cadastral: busca por nome, CPF, CNPJ, CNH, e-mail, telefone ou ID fictício, resultado mockado, bloco de informações ocultas com gating de plano, catálogo de módulos marcados como **DEMO**, painel de qualidade e uma API REST demonstrativa.

## O que está implementado

- Home com busca geral (nome, CPF, CNPJ, CNH, e-mail, telefone ou ID `REG-0001` … `REG-0006`), últimas buscas da sessão e catálogo de módulos.
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

| Método | Rota                    | Descrição                                              |
| ------ | ----------------------- | ------------------------------------------------------ |
| GET    | `/`                     | Home com busca, últimas buscas e módulos               |
| GET    | `/buscar?q=`            | Busca por nome, CPF, CNPJ, CNH, e-mail, telefone ou ID |
| GET    | `/consultas/{publicId}` | Resultado da consulta fictícia                         |
| GET    | `/planos`               | Planos demo e ativação                                 |
| GET    | `/painel`               | Painel de registros, fontes e qualidade                |
| GET    | `/modulos`              | Catálogo de módulos DEMO                               |

### API REST

| Método | Rota                                    | Descrição                                      |
| ------ | --------------------------------------- | ---------------------------------------------- |
| GET    | `/api/demo/registros`                   | Lista os registros fictícios                   |
| GET    | `/api/demo/busca?q=`                    | Busca geral; cada resultado traz `matched_by`  |
| GET    | `/api/demo/consulta/{publicId}`         | Detalhe da consulta (oculta dados sem plano)   |
| GET    | `/api/demo/status`                      | Status do plano demo na sessão                 |
| POST   | `/api/demo/plano/ativar`                | Ativa um plano demo, sem pagamento             |
| POST   | `/api/demo/consulta/{publicId}/avaliar` | Registra feedback da consulta                  |
| POST   | `/api/demo/sincronizar`                 | Reexecuta a ingestão simulada (throttle 6/min) |
| GET    | `/api/demo/modulos`                     | Lista módulos DEMO                             |
| GET    | `/api/demo/planos`                      | Lista planos DEMO                              |

As rotas `/api/demo/*` ficam no middleware `web` de propósito: o plano é mantido na sessão e a ativação usa cookie + CSRF. Em chamadas de fora do navegador, envie `Accept: application/json` e obtenha o cookie/`XSRF-TOKEN` antes do `POST`.

Exemplos:

```bash
curl -s http://localhost:8000/api/demo/registros | jq
curl -s http://localhost:8000/api/demo/consulta/REG-0001 | jq
curl -s http://localhost:8000/api/demo/consulta/ID-INEXISTENTE | jq
```

Resposta de ID inexistente (HTTP 404):

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
2. `SeedJsonProvider` lê `database/data/demo_seed.json` e simula a resposta de cada fonte.
3. `DemoIngestionService` grava cada payload bruto em `demo_record_sources` e registra a execução em `demo_ingestion_runs`. Payloads que uma fonte deixou de devolver são removidos, e registros sem nenhuma fonte saem da base (com consultas e termos de busca). Uma resposta vazia do provedor não apaga nada.
4. `DemoMergeService` escolhe o valor de maior confiabilidade por campo, coleta dados `_hidden` e monta o registro canônico em `demo_records`.
5. `DemoTrustScoreService` avalia o resultado depois da consulta/mesclagem.

```bash
php artisan demo:ingest
php artisan demo:ingest --source=cadastro-civil-demo
```

### Busca

`DemoSearchService` detecta o tipo do termo digitado na home:

| Termo                      | Exemplo                                               | Comportamento                                                           |
| -------------------------- | ----------------------------------------------------- | ----------------------------------------------------------------------- |
| ID                         | `REG-0004`                                            | Abre a consulta (ID inexistente cai no 404 demonstrativo)               |
| CPF, CNPJ, CNH ou telefone | `301.482.115-10`, `12905330000130`, `(11) 90000-0001` | Compara só os dígitos; com um único resultado, abre a consulta          |
| E-mail                     | `bento.rocha@mail.test`                               | Igualdade sem diferenciar maiúsculas                                    |
| Nome                       | `aurora ficticia`                                     | Parcial, sem acento nem maiúsculas; todas as palavras precisam aparecer |

O índice fica em `demo_search_terms` e é reconstruído a cada ingestão. Só campos visíveis são indexados: os dados ocultos do plano nunca são pesquisáveis. Os resultados mostram o CPF mascarado; o CPF completo aparece só na tela da consulta.

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
  Demo/                      # domínio demonstrativo
    Consultation/            # registro e feedback de consultas
    Data/                    # leitura do seed JSON
    Ingestion/               # coleta + persistência de payloads
    Merge/                   # merge multi-fonte + trust score
    Plans/                   # plano em sessão e gating
    Providers/               # contrato e provider JSON
  Http/Controllers/Demo/     # controllers Inertia e API
  Models/Demo*.php           # modelos do protótipo
database/
  data/demo_seed.json        # seed sintético comentado
  migrations/*demo*          # 9 tabelas do protótipo
  seeders/DemoSeeder.php     # metadados + ingestão
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
- busca inválida com 404 demonstrativo;
- ativação de plano demo sem pagamento;
- bloqueio de informações ocultas sem plano e revelação com plano;
- avaliação de consulta após a busca;
- registro incompleto com score menor;
- páginas Inertia de home, consulta, planos, painel, módulos e 404.

## Limitações intencionais

- Não há autenticação obrigatória, cobrança, integração real ou persistência de plano em banco.
- Os "provedores" são locais e leem o mesmo seed JSON; a arquitetura existe para demonstrar como múltiplas fontes seriam ingeridas, armazenadas e mescladas.
- Nenhum dado deve ser usado para identificar, avaliar ou tomar decisões sobre pessoas.
