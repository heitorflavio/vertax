# Frontend do ConsultaDEMO

> Toda a interface trabalha apenas com dados fictícios. O aviso de demonstração aparece em todas as telas.

Este diretório é um **índice de documentação** do frontend. O código real usa a estrutura do starter kit Laravel + Inertia + Vue e vive em `resources/js`.

## Mapa de caminhos

| Área                    | Caminho real                                                                 |
| ----------------------- | ---------------------------------------------------------------------------- |
| Layout do protótipo     | `resources/js/layouts/DemoLayout.vue` (dentro do `AppLayout` do starter kit) |
| Home / busca            | `resources/js/pages/demo/Home.vue`                                           |
| Resultados da busca     | `resources/js/pages/demo/Busca.vue`                                          |
| Formulário de busca     | `resources/js/components/demo/DemoSearchForm.vue`                            |
| Resultado da consulta   | `resources/js/pages/demo/Consulta.vue`                                       |
| Planos e ativação       | `resources/js/pages/demo/Planos.vue`                                         |
| Painel de qualidade     | `resources/js/pages/demo/Painel.vue`                                         |
| Catálogo de módulos     | `resources/js/pages/demo/Modulos.vue`                                        |
| 404 demonstrativo       | `resources/js/pages/demo/NotFound.vue`                                       |
| Formatação (score/data) | `resources/js/lib/demoFormat.ts`                                             |
| Modal de planos         | `resources/js/components/demo/DemoPlanModal.vue`                             |
| Score de confiança      | `resources/js/components/demo/TrustScore.vue`                                |
| Cliente da API          | `resources/js/lib/demoApi.ts`                                                |
| Ícones                  | `resources/js/lib/demoIcons.ts`                                              |
| Tipos TypeScript        | `resources/js/types/demo.ts`                                                 |

## Fluxos principais

1. **Busca**: a home aceita nome, CPF, CNPJ, CNH, e-mail, telefone ou ID. Nome lista os resultados em `/buscar`; documento, e-mail, telefone ou ID com um único registro abre direto `/consultas/{publicId}`. IDs desconhecidos exibem a página 404 demonstrativa.
2. **Resultado**: mostra nome, CPF completo (fictício e inválido), nascimento, fontes e score. O bloco de informações ocultas aparece bloqueado.
3. **Plano**: sem plano ativo, o botão **Exibir informação** abre o modal. A ativação chama `POST /api/demo/plano/ativar` e recarrega a página com os dados ocultos.
4. **Copiar**: o texto copiado inclui o aviso de que o resultado é fictício e sem validade legal.
5. **Qualidade**: a página de resultado permite avaliar a consulta; o painel consolida fontes, scores e execuções de ingestão.

## Integração com a API

`resources/js/lib/demoApi.ts` faz `fetch` same-origin, lê o cookie `XSRF-TOKEN` e envia `X-XSRF-TOKEN` nos `POST`. Isso é necessário porque as rotas demo usam o middleware `web` para manter o plano na sessão.

## Comandos

```bash
npm install
npm run dev       # hot reload
npm run build     # build de produção
npm run types:check
```

O frontend consome os props compartilhados `demo` (status do plano, disclaimers e rotas) definidos em `HandleInertiaRequests`, além dos dados de cada página.
