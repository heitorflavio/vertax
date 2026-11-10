<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { KeyRound, ThumbsDown, ThumbsUp } from '@lucide/vue';
import SectionHeading from '@/components/landing/SectionHeading.vue';
import { vReveal } from '@/lib/landing/reveal';
import demo from '@/routes/demo';
import type { LandingSource } from '@/types/landing';

defineProps<{
    sources: LandingSource[];
}>();

const records = [
    {
        id: 'REG-0001',
        name: 'Aurora Mendes Lacerda',
        sources: 4,
        score: 90,
        label: 'Alto',
    },
    {
        id: 'REG-0002',
        name: 'Bento Almeida Rocha',
        sources: 3,
        score: 88,
        label: 'Alto',
    },
    {
        id: 'REG-0003',
        name: 'Cora Ribeiro Antunes',
        sources: 4,
        score: 90,
        label: 'Alto',
    },
    {
        id: 'REG-0004',
        name: 'Davi Costa Moreira',
        sources: 4,
        score: 87,
        label: 'Alto',
    },
    {
        id: 'REG-0005',
        name: 'Eva Nunes Barbosa',
        sources: 3,
        score: 83,
        label: 'Moderado',
    },
];

// Histórico ilustrativo: duas execuções com falha no meio de sessenta.
const runs = Array.from({ length: 60 }, (_, index) =>
    index === 17 || index === 41 ? 'failed' : 'ok',
);

const tiles = [
    {
        title: 'Acesso por papéis',
        text: 'Administradores gerenciam fontes, operadores acompanham o painel e clientes consultam.',
    },
    {
        title: 'Passkeys e 2FA',
        text: 'Entre com passkey ou com senha e verificação em duas etapas.',
    },
    {
        title: 'Gestão de fontes',
        text: 'Cadastre fontes por API ou arquivo, com tipos de busca e confiabilidade próprios.',
    },
    {
        title: 'Avaliação de resultados',
        text: 'Marque cada resultado como útil ou não e acompanhe a qualidade no painel.',
    },
];
</script>

<template>
    <section id="por-que" class="lp-section border-b border-border">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <SectionHeading eyebrow="Por que Vertax" class="mb-14">
                <template #title>
                    Feito para dados confiáveis.
                    <span class="text-muted-foreground">
                        Fontes ponderadas, mesclagem transparente e acesso
                        controlado, sem planilhas nem retrabalho.
                    </span>
                </template>
                <template #note>
                    Veja como funciona o
                    <a
                        href="#produto"
                        class="text-foreground underline decoration-foreground/30 underline-offset-4 hover:decoration-foreground"
                        >score de confiança</a
                    >
                    ou compare os
                    <Link
                        :href="demo.plans()"
                        class="text-foreground underline decoration-foreground/30 underline-offset-4 hover:decoration-foreground"
                        >planos</Link
                    >.
                </template>
            </SectionHeading>

            <div class="grid gap-4 sm:gap-5 lg:grid-cols-2">
                <article
                    v-reveal
                    class="relative isolate flex flex-col overflow-hidden rounded-2xl border border-border bg-card p-6 sm:p-8"
                >
                    <div class="lp-art" aria-hidden="true">
                        <span
                            class="lp-blob -top-20 right-0 size-80 bg-[rgb(var(--rgb-orange)/0.5)]"
                        />
                    </div>
                    <div class="relative z-10 flex flex-1 flex-col">
                        <h3
                            class="text-[19px] font-semibold tracking-[-0.01em] text-foreground"
                        >
                            Painel de qualidade
                        </h3>
                        <p
                            class="mt-1.5 max-w-md text-sm leading-relaxed text-muted-foreground"
                        >
                            Acompanhe score, fontes e completude de cada
                            registro, além das avaliações feitas depois das
                            consultas.
                        </p>
                        <div class="mt-6 flex flex-1 items-end">
                            <div
                                class="w-full overflow-hidden rounded-xl border border-border bg-popover"
                                aria-hidden="true"
                            >
                                <div
                                    class="flex items-center gap-2 border-b border-border px-3 py-2"
                                >
                                    <span class="flex gap-1.5">
                                        <span
                                            v-for="dot in 3"
                                            :key="dot"
                                            class="size-2 rounded-full bg-foreground/15"
                                        />
                                    </span>
                                    <span
                                        class="ml-1 min-w-0 flex-1 truncate rounded-md bg-foreground/5 px-2 py-1 text-[10px] text-muted-foreground"
                                    >
                                        /painel
                                    </span>
                                </div>
                                <div class="p-3">
                                    <div
                                        class="mb-2 flex gap-3 border-b border-border text-[11px]"
                                    >
                                        <span
                                            class="relative pb-2 font-medium text-foreground"
                                        >
                                            Registros
                                            <span
                                                class="absolute inset-x-0 bottom-0 h-0.5 rounded-full bg-brand-blue"
                                            />
                                        </span>
                                        <span
                                            class="pb-2 text-muted-foreground"
                                        >
                                            Fontes
                                        </span>
                                        <span
                                            class="pb-2 text-muted-foreground"
                                        >
                                            Sincronização
                                        </span>
                                    </div>
                                    <div
                                        v-for="record in records"
                                        :key="record.id"
                                        class="grid grid-cols-[4.5rem_1fr_auto_auto] items-center gap-3 py-1.5 text-[11px]"
                                    >
                                        <span
                                            class="lp-mono text-muted-foreground"
                                        >
                                            {{ record.id }}
                                        </span>
                                        <span
                                            class="truncate text-foreground/90"
                                        >
                                            {{ record.name }}
                                        </span>
                                        <span class="text-muted-foreground">
                                            {{ record.sources }} fontes
                                        </span>
                                        <span
                                            class="min-w-20 rounded px-1.5 py-0.5 text-center font-semibold whitespace-nowrap tabular-nums"
                                            :class="
                                                record.label === 'Alto'
                                                    ? 'bg-emerald-500/15 text-emerald-500'
                                                    : 'bg-amber-500/15 text-amber-500'
                                            "
                                        >
                                            {{ record.score }} ·
                                            {{ record.label }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </article>

                <article
                    v-reveal="80"
                    class="relative isolate flex flex-col overflow-hidden rounded-2xl border border-border bg-card p-6 sm:p-8"
                >
                    <div class="lp-art" aria-hidden="true">
                        <span
                            class="lp-blob -top-24 -right-10 size-80 bg-[rgb(var(--rgb-orange)/0.45)] [animation-delay:-8s]"
                        />
                    </div>
                    <div class="relative z-10 flex flex-1 flex-col">
                        <h3
                            class="text-[19px] font-semibold tracking-[-0.01em] text-foreground"
                        >
                            Sincronização de fontes
                        </h3>
                        <p
                            class="mt-1.5 max-w-md text-sm leading-relaxed text-muted-foreground"
                        >
                            Atualize as fontes quando precisar e acompanhe cada
                            execução, com limite de seis por minuto.
                        </p>
                        <div class="mt-6 flex flex-1 items-end">
                            <div
                                class="w-full rounded-xl border border-border bg-popover p-4"
                                aria-hidden="true"
                            >
                                <div
                                    class="flex items-center justify-between text-[11px]"
                                >
                                    <span
                                        class="flex items-center gap-1.5 font-medium text-brand-blue"
                                    >
                                        <span
                                            class="size-1.5 rounded-full bg-brand-blue"
                                        />
                                        Fontes sincronizadas
                                    </span>
                                    <span class="text-muted-foreground">
                                        Histórico de execuções (exemplo)
                                    </span>
                                </div>
                                <div
                                    class="mt-3 flex h-12 items-stretch gap-0.75"
                                >
                                    <span
                                        v-for="(run, index) in runs"
                                        :key="index"
                                        class="flex-1 rounded-[2px]"
                                        :class="
                                            run === 'ok'
                                                ? 'bg-brand-blue/70'
                                                : 'bg-brand-orange'
                                        "
                                    />
                                </div>
                                <div
                                    class="mt-4 flex items-end justify-between border-t border-border pt-3"
                                >
                                    <div>
                                        <p
                                            class="text-2xl font-bold tracking-tight text-foreground tabular-nums"
                                        >
                                            {{ sources.length }}
                                        </p>
                                        <p
                                            class="text-[11px] text-muted-foreground"
                                        >
                                            fontes ativas
                                        </p>
                                    </div>
                                    <div class="text-right">
                                        <p
                                            class="text-2xl font-bold tracking-tight text-foreground tabular-nums"
                                        >
                                            6/min
                                        </p>
                                        <p
                                            class="text-[11px] text-muted-foreground"
                                        >
                                            limite de sincronização
                                        </p>
                                    </div>
                                </div>
                                <p
                                    class="mt-3 border-t border-border pt-2.5 text-center text-[11px] text-muted-foreground"
                                >
                                    Cada execução fica registrada no painel de
                                    ingestão
                                </p>
                            </div>
                        </div>
                    </div>
                </article>
            </div>

            <div
                class="mt-4 grid gap-4 sm:mt-5 sm:grid-cols-2 sm:gap-5 lg:grid-cols-4"
            >
                <article
                    v-for="(tile, index) in tiles"
                    :key="tile.title"
                    v-reveal="index * 70"
                    class="flex flex-col rounded-2xl border border-border bg-card p-5"
                    :class="index % 2 ? 'lp-card-wash-alt' : 'lp-card-wash'"
                >
                    <div
                        class="mb-4 flex h-24 items-center justify-center"
                        aria-hidden="true"
                    >
                        <div
                            v-if="index === 0"
                            class="flex flex-col items-center gap-2"
                        >
                            <span
                                class="rounded-md border border-border bg-popover px-2 py-0.5 text-[10px] text-muted-foreground"
                            >
                                Admin
                            </span>
                            <span class="h-3 w-px bg-brand-blue/50" />
                            <span class="flex gap-3">
                                <span
                                    class="rounded-full border border-brand-blue/50 bg-brand-blue/15 px-2.5 py-1 text-[10px] font-semibold text-brand-blue"
                                >
                                    Operador
                                </span>
                                <span
                                    class="rounded-full border border-border bg-popover px-2.5 py-1 text-[10px] font-semibold text-foreground/80"
                                >
                                    Cliente
                                </span>
                            </span>
                        </div>
                        <div
                            v-else-if="index === 1"
                            class="flex items-center gap-2.5"
                        >
                            <span
                                class="flex size-9 items-center justify-center rounded-full border border-brand-blue/40 bg-brand-blue/15 text-brand-blue"
                            >
                                <KeyRound class="size-4" />
                            </span>
                            <span class="flex gap-1">
                                <span
                                    v-for="(digit, d) in [
                                        '4',
                                        '8',
                                        '2',
                                        '1',
                                        '1',
                                        '',
                                    ]"
                                    :key="d"
                                    class="flex h-8 w-6 items-center justify-center rounded-md border bg-popover text-xs font-semibold text-foreground"
                                    :class="
                                        digit
                                            ? 'border-border'
                                            : 'border-brand-blue'
                                    "
                                >
                                    {{ digit }}
                                </span>
                            </span>
                        </div>
                        <div
                            v-else-if="index === 2"
                            class="w-full max-w-44 space-y-1.5"
                        >
                            <div
                                v-for="(source, s) in sources.slice(0, 3)"
                                :key="source.slug"
                                class="flex items-center justify-between rounded-md border border-border bg-popover px-2 py-1 text-[10px] text-foreground/85"
                            >
                                {{ source.name }}
                                <span
                                    class="flex h-3.5 w-6 items-center rounded-full p-0.5"
                                    :class="
                                        s < 2
                                            ? 'justify-end bg-brand-blue'
                                            : 'bg-foreground/15'
                                    "
                                >
                                    <span
                                        class="size-2.5 rounded-full bg-white"
                                    />
                                </span>
                            </div>
                        </div>
                        <div v-else class="flex flex-col items-center gap-2">
                            <span
                                class="rounded-full bg-brand-blue px-3 py-1 text-[10px] font-medium text-white"
                            >
                                Este resultado foi útil?
                            </span>
                            <span class="flex gap-2">
                                <span
                                    class="flex items-center gap-1 rounded-full border border-emerald-500/40 bg-emerald-500/15 px-2.5 py-1 text-[10px] font-semibold text-emerald-500"
                                >
                                    <ThumbsUp class="size-3" /> Útil
                                </span>
                                <span
                                    class="flex items-center gap-1 rounded-full border border-border bg-popover px-2.5 py-1 text-[10px] text-muted-foreground"
                                >
                                    <ThumbsDown class="size-3" /> Não útil
                                </span>
                            </span>
                        </div>
                    </div>
                    <h3 class="text-[15px] font-semibold text-foreground">
                        {{ tile.title }}
                    </h3>
                    <p
                        class="mt-2 text-sm leading-relaxed text-muted-foreground"
                    >
                        {{ tile.text }}
                    </p>
                </article>
            </div>
        </div>
    </section>
</template>
