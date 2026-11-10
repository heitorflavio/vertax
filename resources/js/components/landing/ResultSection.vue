<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { Check, Copy, Gauge, GitMerge, ListChecks } from '@lucide/vue';
import { computed, ref } from 'vue';
import SectionHeading from '@/components/landing/SectionHeading.vue';
import { vReveal } from '@/lib/landing/reveal';
import demo from '@/routes/demo';

type TokenKind = 'key' | 'string' | 'number' | 'literal' | 'plain';

// Trechos de "data" na resposta de /api/demo/consulta/REG-0001 (registro fictício).
const tabs = [
    {
        label: 'Registro',
        json: `{
  "public_id": "REG-0001",
  "full_name": "Aurora Mendes Lacerda",
  "cpf": "301.482.115-10",
  "fields": [
    { "key": "birth_date", "label": "Data de nascimento", "value": "12/04/1990" },
    { "key": "email", "label": "E-mail", "value": "aurora.lacerda@mail.test" },
    { "key": "phone", "label": "Telefone", "value": "+55 11 90000-0001" },
    { "key": "address", "label": "Endereço", "value": "Rua das Acácias, 100" },
    { "key": "city", "label": "Cidade", "value": "Campinas" },
    { "key": "state", "label": "UF", "value": "SP" }
  ],
  "trust_score": 90,
  "trust_label": "Alto",
  "sources_count": 4,
  "hidden_locked": true,
  "hidden_fields_count": 6
}`,
    },
    {
        label: 'Confiança',
        json: `{
  "trust": {
    "score": 90,
    "label": "Alto",
    "breakdown": {
      "source_quality": 73.5,
      "completeness": 100,
      "consistency": 100,
      "freshness": 97.5,
      "weights": {
        "source_quality": 0.35,
        "completeness": 0.25,
        "consistency": 0.25,
        "freshness": 0.15
      },
      "sources_evaluated": 4,
      "fields_filled": 9,
      "fields_total": 9,
      "explanation": "As fontes concordam na maior parte dos campos e os dados são recentes."
    }
  }
}`,
    },
    {
        label: 'Fontes',
        json: `{
  "sources": [
    {
      "name": "Cadastro Civil",
      "reliability": 92,
      "contributed_fields": ["Nome completo", "CPF", "Data de nascimento", "CNH", "Nome da mãe"]
    },
    {
      "name": "Contatos",
      "reliability": 76,
      "contributed_fields": ["E-mail", "Telefone", "Cidade", "UF"]
    },
    {
      "name": "Localização",
      "reliability": 68,
      "contributed_fields": ["Endereço"]
    },
    {
      "name": "Crédito",
      "reliability": 58,
      "contributed_fields": []
    }
  ]
}`,
    },
];

const features = [
    {
        icon: GitMerge,
        title: 'Mesclagem automática',
        text: 'Campos repetidos entre fontes viram um valor só, normalizado.',
    },
    {
        icon: Gauge,
        title: 'Score explicado',
        text: 'Qualidade, completude, consistência e atualidade, com os pesos.',
    },
    {
        icon: ListChecks,
        title: 'Origem de cada campo',
        text: 'Saiba qual fonte forneceu cada dado e quando ela foi lida.',
    },
];

const TOKEN =
    /("(?:\\.|[^"\\])*")(\s*:)?|\b(true|false|null)\b|(-?\d+(?:\.\d+)?)/g;

function tokenize(line: string): Array<{ text: string; kind: TokenKind }> {
    const tokens: Array<{ text: string; kind: TokenKind }> = [];
    let last = 0;

    for (const match of line.matchAll(TOKEN)) {
        const index = match.index ?? 0;

        if (index > last) {
            tokens.push({ text: line.slice(last, index), kind: 'plain' });
        }

        if (match[1]) {
            tokens.push({ text: match[1], kind: match[2] ? 'key' : 'string' });

            if (match[2]) tokens.push({ text: match[2], kind: 'plain' });
        } else if (match[3]) {
            tokens.push({ text: match[3], kind: 'literal' });
        } else {
            tokens.push({ text: match[0], kind: 'number' });
        }

        last = index + match[0].length;
    }

    if (last < line.length) {
        tokens.push({ text: line.slice(last), kind: 'plain' });
    }

    return tokens;
}

const tokenClass: Record<TokenKind, string> = {
    key: 'text-(--code-key)',
    string: 'text-(--code-string)',
    number: 'text-(--code-number)',
    literal: 'text-(--code-literal)',
    plain: 'text-muted-foreground',
};

const active = ref(0);
const copied = ref<'idle' | 'done' | 'failed'>('idle');
const lines = computed(() => tabs[active.value].json.split('\n').map(tokenize));

async function copy(): Promise<void> {
    try {
        await navigator.clipboard.writeText(tabs[active.value].json);
        copied.value = 'done';
    } catch {
        copied.value = 'failed';
    }

    window.setTimeout(() => (copied.value = 'idle'), 1600);
}
</script>

<template>
    <section id="resultado" class="lp-section border-b border-border">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div
                class="grid items-center gap-10 lg:grid-cols-[5fr_7fr] lg:gap-16"
            >
                <div class="min-w-0">
                    <SectionHeading eyebrow="Resultado estruturado">
                        <template #title>
                            Um registro.<br />
                            Pronto para usar.
                        </template>
                        Cada consulta devolve o registro mesclado, o score
                        explicado e a lista de fontes com os campos que cada uma
                        forneceu.
                    </SectionHeading>

                    <ul class="mt-8 space-y-5">
                        <li
                            v-for="(feature, index) in features"
                            :key="feature.title"
                            v-reveal="index * 80"
                            class="flex gap-4"
                        >
                            <component
                                :is="feature.icon"
                                class="mt-0.5 size-4.5 shrink-0 text-muted-foreground"
                            />
                            <div>
                                <p
                                    class="text-[15px] font-semibold text-foreground"
                                >
                                    {{ feature.title }}
                                </p>
                                <p class="mt-0.5 text-sm text-muted-foreground">
                                    {{ feature.text }}
                                </p>
                            </div>
                        </li>
                    </ul>

                    <p class="mt-8 text-sm text-muted-foreground">
                        Quer ver mais campos? Os
                        <Link
                            :href="demo.plans()"
                            class="text-foreground underline decoration-foreground/30 underline-offset-4 hover:decoration-foreground"
                            >planos</Link
                        >
                        liberam as informações ocultas.
                    </p>
                </div>

                <div
                    v-reveal="80"
                    class="min-w-0 overflow-hidden rounded-xl border border-border bg-popover shadow-2xl shadow-black/30"
                >
                    <div
                        class="relative flex items-center gap-2 border-b border-border px-3 py-2"
                    >
                        <span class="flex gap-1.5" aria-hidden="true">
                            <span
                                v-for="dot in 3"
                                :key="dot"
                                class="size-2.5 rounded-full bg-foreground/15"
                            />
                        </span>
                        <span
                            class="lp-mono absolute left-1/2 -translate-x-1/2 text-xs text-muted-foreground"
                        >
                            REG-0001.json
                        </span>
                        <button
                            type="button"
                            class="ml-auto flex items-center gap-1.5 rounded-md border border-border px-2 py-1 text-[11px] text-muted-foreground transition-colors hover:text-foreground"
                            @click="copy"
                        >
                            <Check v-if="copied === 'done'" class="size-3.5" />
                            <Copy v-else class="size-3.5" />
                            {{
                                copied === 'done'
                                    ? 'Copiado'
                                    : copied === 'failed'
                                      ? 'Não copiou'
                                      : 'Copiar'
                            }}
                        </button>
                    </div>

                    <div
                        role="tablist"
                        aria-label="Partes do resultado"
                        class="lp-no-scrollbar flex gap-1 overflow-x-auto border-b border-border px-2"
                    >
                        <button
                            v-for="(tab, index) in tabs"
                            :id="`resultado-aba-${index}`"
                            :key="tab.label"
                            type="button"
                            role="tab"
                            :aria-selected="active === index"
                            aria-controls="resultado-painel"
                            class="relative px-3 py-2.5 text-xs font-medium transition-colors"
                            :class="
                                active === index
                                    ? 'text-foreground'
                                    : 'text-muted-foreground hover:text-foreground/80'
                            "
                            @click="active = index"
                        >
                            {{ tab.label }}
                            <span
                                v-if="active === index"
                                class="absolute inset-x-2 -bottom-px h-0.5 rounded-full bg-brand-blue"
                            />
                        </button>
                    </div>

                    <div
                        id="resultado-painel"
                        role="tabpanel"
                        :aria-labelledby="`resultado-aba-${active}`"
                        tabindex="0"
                        class="lp-mono h-105 overflow-auto py-3 text-[12.5px] leading-6"
                    >
                        <div
                            v-for="(tokens, index) in lines"
                            :key="index"
                            class="flex min-w-max"
                        >
                            <span
                                class="w-10 shrink-0 pr-4 text-right text-muted-foreground/50 select-none"
                            >
                                {{ index + 1 }}
                            </span>
                            <span class="pr-6 whitespace-pre"
                                ><span
                                    v-for="(token, t) in tokens"
                                    :key="t"
                                    :class="tokenClass[token.kind]"
                                    >{{ token.text }}</span
                                ></span
                            >
                        </div>
                    </div>

                    <div
                        class="flex items-center justify-between gap-3 border-t border-border px-3 py-1.5 text-[11px] text-muted-foreground"
                    >
                        <span
                            class="lp-mono flex min-w-0 items-center gap-1.5 truncate"
                        >
                            <span
                                class="size-1.5 shrink-0 rounded-full bg-brand-blue"
                            />
                            GET /api/demo/consulta/REG-0001
                        </span>
                        <span class="shrink-0">
                            trecho de <span class="lp-mono">data</span> ·
                            {{ lines.length }} linhas
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </section>
</template>
