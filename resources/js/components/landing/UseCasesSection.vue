<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import {
    ArrowRight,
    AtSign,
    Building2,
    Check,
    Lock,
    MapPin,
    Phone,
    ThumbsDown,
    ThumbsUp,
} from '@lucide/vue';
import SectionHeading from '@/components/landing/SectionHeading.vue';
import UseCaseCard from '@/components/landing/UseCaseCard.vue';
import { vReveal } from '@/lib/landing/reveal';
import { login, register } from '@/routes';
import demo from '@/routes/demo';

const props = defineProps<{
    canRegister: boolean;
}>();

const startHref = props.canRegister ? register.url() : login.url();
const modulesHref = demo.modules.url();

const record = [
    ['Nome completo', 'Aurora Mendes Lacerda', 'Cadastro Civil'],
    ['CPF', '301.482.115-10', 'Cadastro Civil'],
    ['Nascimento', '12/04/1990', 'Cadastro Civil'],
    ['E-mail', 'aurora.lacerda@mail.test', 'Contatos'],
    ['Telefone', '+55 11 90000-0001', 'Contatos'],
    ['Endereço', 'Rua das Acácias, 100', 'Localização'],
    ['Cidade/UF', 'Campinas/SP', 'Contatos'],
];

const contacts = [
    {
        icon: AtSign,
        value: 'aurora.lacerda@mail.test',
        source: 'Contatos',
        reliability: 76,
    },
    {
        icon: Phone,
        value: '+55 11 90000-0001',
        source: 'Contatos',
        reliability: 76,
    },
    {
        icon: MapPin,
        value: 'Rua das Acácias, 100',
        source: 'Localização',
        reliability: 68,
    },
];

const history = [
    {
        id: 'REG-0001',
        type: 'CPF',
        when: 'há 2 min',
        score: 90,
        feedback: 'up',
    },
    { id: 'REG-0004', type: 'Nome', when: 'há 1 h', score: 87, feedback: null },
    {
        id: 'REG-0005',
        type: 'E-mail',
        when: 'ontem',
        score: 83,
        feedback: 'down',
    },
];
</script>

<template>
    <section id="casos-de-uso" class="lp-section border-b border-border">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div
                class="mb-10 flex flex-col justify-between gap-6 sm:flex-row sm:items-end"
            >
                <SectionHeading
                    eyebrow="Casos de uso"
                    title="Dados confiáveis para cada decisão."
                >
                    Do cadastro de clientes à análise de risco: a mesma consulta
                    alimenta onboarding, crédito, cobrança e auditoria, sempre
                    com a origem de cada dado.
                </SectionHeading>
                <Link
                    :href="modulesHref"
                    class="flex shrink-0 items-center gap-1.5 text-sm font-medium text-muted-foreground transition-colors hover:text-foreground"
                >
                    Ver catálogo de módulos
                    <ArrowRight class="size-4" />
                </Link>
            </div>

            <div class="grid grid-cols-1 gap-4 sm:gap-5 lg:grid-cols-3">
                <UseCaseCard
                    v-reveal
                    large
                    class="lg:col-span-2 lg:row-span-2"
                    eyebrow="Onboarding"
                    title="Valide um cadastro em uma consulta."
                    description="Confira nome, documento, nascimento e contato de uma vez, com o score mostrando o quanto as fontes concordam entre si."
                    :href="startHref"
                >
                    <div class="relative min-h-96 flex-1" aria-hidden="true">
                        <div
                            v-for="(path, index) in ['/buscar?tipo=cpf', '/']"
                            :key="path"
                            class="absolute flex h-16 items-start gap-2 rounded-xl border border-border bg-popover px-3 py-2"
                            :class="
                                index === 0
                                    ? 'inset-x-8 top-0 opacity-60'
                                    : 'inset-x-4 top-6 opacity-80'
                            "
                        >
                            <span class="mt-1 flex gap-1.5">
                                <span
                                    v-for="dot in 3"
                                    :key="dot"
                                    class="size-2 rounded-full bg-foreground/15"
                                />
                            </span>
                            <span
                                class="truncate rounded-full bg-foreground/5 px-2.5 py-0.5 text-[11px] text-muted-foreground"
                            >
                                {{ path }}
                            </span>
                        </div>

                        <div
                            class="absolute inset-x-0 top-12 bottom-0 flex flex-col overflow-hidden rounded-xl border border-border bg-popover shadow-2xl shadow-black/30"
                        >
                            <div
                                class="flex shrink-0 items-center gap-2 border-b border-border px-3 py-2"
                            >
                                <span class="flex gap-1.5">
                                    <span
                                        class="size-2.5 rounded-full bg-[#ff5f57]"
                                    />
                                    <span
                                        class="size-2.5 rounded-full bg-[#febc2e]"
                                    />
                                    <span
                                        class="size-2.5 rounded-full bg-[#28c840]"
                                    />
                                </span>
                                <span
                                    class="flex h-6 min-w-0 flex-1 items-center rounded-full bg-foreground/5 px-2.5 text-[11px] text-muted-foreground"
                                >
                                    /consultas/REG-0001
                                </span>
                                <span
                                    class="flex items-center gap-1.5 rounded-md px-2 py-1 text-[11px] font-medium text-foreground/80"
                                >
                                    <span
                                        class="size-1.5 rounded-full bg-brand-blue"
                                    />
                                    4 fontes
                                </span>
                            </div>
                            <div
                                class="flex items-center gap-3 border-b border-border px-4 py-3"
                            >
                                <span
                                    class="flex size-9 items-center justify-center rounded-full bg-brand-blue/15 text-xs font-semibold text-brand-blue"
                                >
                                    AL
                                </span>
                                <span class="min-w-0 flex-1">
                                    <span
                                        class="block truncate text-sm font-semibold text-foreground"
                                    >
                                        Aurora Mendes Lacerda
                                    </span>
                                    <span
                                        class="block text-[11px] text-muted-foreground"
                                    >
                                        Dados fictícios · consultado agora
                                    </span>
                                </span>
                                <span
                                    class="rounded-md bg-emerald-500/15 px-2 py-1 text-xs font-semibold text-emerald-500 tabular-nums"
                                >
                                    90 · Alto
                                </span>
                            </div>
                            <ul
                                class="min-h-0 flex-1 divide-y divide-border overflow-hidden"
                            >
                                <li
                                    v-for="[label, value, source] in record"
                                    :key="label"
                                    class="grid grid-cols-[7rem_1fr_auto] items-center gap-3 px-4 py-2 text-xs"
                                >
                                    <span class="text-muted-foreground">{{
                                        label
                                    }}</span>
                                    <span class="truncate text-foreground/90">{{
                                        value
                                    }}</span>
                                    <span
                                        class="hidden rounded-md border border-border px-1.5 py-0.5 text-[10px] text-muted-foreground sm:inline"
                                    >
                                        {{ source }}
                                    </span>
                                </li>
                            </ul>
                        </div>
                    </div>
                    <p
                        class="mt-4 flex items-center gap-2 text-xs text-muted-foreground"
                    >
                        <span class="size-1.5 rounded-full bg-brand-blue" />
                        <span class="font-medium text-brand-blue">200 OK</span>
                        · busca por CPF · 4 fontes consultadas
                    </p>
                </UseCaseCard>

                <UseCaseCard
                    v-reveal="80"
                    eyebrow="Risco"
                    title="Divergências aparecem na hora."
                    :href="modulesHref"
                >
                    <div
                        class="space-y-2 rounded-xl border border-border bg-popover p-3 shadow-lg"
                        aria-hidden="true"
                    >
                        <div
                            class="flex items-center justify-between text-[11px]"
                        >
                            <span
                                class="flex items-center gap-1.5 text-muted-foreground"
                            >
                                <span
                                    class="size-1.5 animate-pulse rounded-full bg-emerald-500"
                                />
                                Verificação · REG-0004
                            </span>
                            <span class="text-foreground/80"
                                >Davi C. Moreira</span
                            >
                        </div>
                        <div
                            class="rounded-lg border border-brand-orange/30 bg-brand-orange/8 px-2.5 py-2"
                        >
                            <div
                                class="flex items-center justify-between text-xs"
                            >
                                <span class="font-medium text-foreground">
                                    UF diverge entre fontes
                                </span>
                                <span
                                    class="rounded bg-brand-orange/15 px-1.5 py-0.5 text-[10px] font-semibold text-brand-orange"
                                >
                                    Atenção
                                </span>
                            </div>
                            <p class="mt-1 text-[11px] text-muted-foreground">
                                Contatos diz
                                <b class="text-foreground">PR</b> (76%) ·
                                Localização diz
                                <b class="text-foreground">SC</b> (68%)
                            </p>
                        </div>
                        <div
                            class="flex items-center justify-between rounded-lg border border-border px-2.5 py-2 text-xs"
                        >
                            <span class="text-foreground/85"
                                >Nome e CPF conferem</span
                            >
                            <span
                                class="flex items-center gap-1 rounded bg-emerald-500/15 px-1.5 py-0.5 text-[10px] font-semibold text-emerald-500"
                            >
                                <Check class="size-3" /> 3 fontes
                            </span>
                        </div>
                        <div
                            class="flex items-center justify-between rounded-lg border border-border px-2.5 py-2 text-xs"
                        >
                            <span class="text-foreground/85"
                                >Alerta de fraude</span
                            >
                            <span
                                class="flex items-center gap-1 rounded bg-foreground/8 px-1.5 py-0.5 text-[10px] font-semibold text-muted-foreground"
                            >
                                <Lock class="size-3" /> Requer plano
                            </span>
                        </div>
                    </div>
                </UseCaseCard>

                <UseCaseCard
                    v-reveal="120"
                    eyebrow="Crédito"
                    title="Score e renda no mesmo registro."
                    :href="modulesHref"
                >
                    <div
                        class="rounded-xl border border-border bg-popover p-3 shadow-lg"
                        aria-hidden="true"
                    >
                        <div class="flex items-center justify-between">
                            <span class="text-[11px] text-muted-foreground">
                                Aurora M. Lacerda · REG-0001
                            </span>
                            <span
                                class="rounded-md bg-brand-blue/12 px-1.5 py-0.5 text-[10px] font-semibold text-brand-blue"
                            >
                                Plano ativo
                            </span>
                        </div>
                        <div class="mt-3 grid grid-cols-2 gap-4">
                            <div>
                                <p class="text-[11px] text-muted-foreground">
                                    Score de crédito
                                </p>
                                <p
                                    class="text-xl font-bold tracking-tight whitespace-nowrap text-foreground tabular-nums"
                                >
                                    712
                                </p>
                                <div
                                    class="mt-1.5 h-1 rounded-full bg-foreground/10"
                                >
                                    <div
                                        class="h-full w-[71%] rounded-full bg-linear-to-r from-brand-orange to-brand-blue"
                                    />
                                </div>
                            </div>
                            <div>
                                <p class="text-[11px] text-muted-foreground">
                                    Renda presumida
                                </p>
                                <p
                                    class="text-xl font-bold tracking-tight whitespace-nowrap text-foreground tabular-nums"
                                >
                                    R$ 6,8 mil
                                </p>
                                <p
                                    class="mt-1 text-[11px] text-muted-foreground"
                                >
                                    fonte: Crédito
                                </p>
                            </div>
                        </div>
                    </div>
                </UseCaseCard>

                <UseCaseCard
                    v-reveal
                    eyebrow="Cobrança"
                    title="O contato certo, com a fonte ao lado."
                    :href="modulesHref"
                >
                    <div
                        class="rounded-xl border border-border bg-popover shadow-lg"
                        aria-hidden="true"
                    >
                        <p
                            class="border-b border-border px-3 py-2 text-[11px] text-muted-foreground"
                        >
                            Canais de contato · Aurora M. Lacerda
                        </p>
                        <ul class="divide-y divide-border">
                            <li
                                v-for="contact in contacts"
                                :key="contact.value"
                                class="flex items-center gap-2.5 px-3 py-2.5"
                            >
                                <component
                                    :is="contact.icon"
                                    class="size-3.5 shrink-0 text-brand-blue"
                                />
                                <span class="min-w-0 flex-1">
                                    <span
                                        class="block truncate text-xs text-foreground/90"
                                    >
                                        {{ contact.value }}
                                    </span>
                                    <span
                                        class="mt-1 block h-1 rounded-full bg-foreground/10"
                                    >
                                        <span
                                            class="block h-full rounded-full bg-brand-blue"
                                            :style="{
                                                width: `${contact.reliability}%`,
                                            }"
                                        />
                                    </span>
                                </span>
                                <span
                                    class="text-right text-[10px] text-muted-foreground"
                                >
                                    {{ contact.source }}<br />
                                    <span
                                        class="text-foreground/80 tabular-nums"
                                    >
                                        {{ contact.reliability }}%
                                    </span>
                                </span>
                            </li>
                        </ul>
                    </div>
                </UseCaseCard>

                <UseCaseCard
                    v-reveal="80"
                    eyebrow="Due diligence"
                    title="Vínculos societários de um CPF."
                    :href="modulesHref"
                >
                    <div class="relative h-48" aria-hidden="true">
                        <svg
                            class="absolute inset-0 h-full w-full text-brand-blue"
                            viewBox="0 0 100 100"
                            preserveAspectRatio="none"
                        >
                            <path
                                v-for="d in [
                                    'M50 52 L22 16',
                                    'M50 52 L80 18',
                                    'M50 52 L20 86',
                                    'M50 52 L82 86',
                                ]"
                                :key="d"
                                :d="d"
                                stroke="currentColor"
                                stroke-opacity="0.45"
                                stroke-dasharray="3 3"
                                vector-effect="non-scaling-stroke"
                                fill="none"
                            />
                        </svg>
                        <span
                            class="absolute top-[52%] left-1/2 flex -translate-x-1/2 -translate-y-1/2 items-center gap-2 rounded-full border border-brand-blue/40 bg-popover py-1 pr-3 pl-1 shadow-lg"
                        >
                            <span
                                class="flex size-6 items-center justify-center rounded-full bg-brand-blue text-[10px] font-semibold text-white"
                            >
                                FT
                            </span>
                            <span
                                class="text-xs font-medium whitespace-nowrap text-foreground"
                            >
                                Fábio L. Teixeira
                            </span>
                        </span>
                        <span
                            class="absolute top-[6%] left-0 flex max-w-[48%] items-center gap-1.5 rounded-lg border border-border bg-popover px-2 py-1 text-[10px] text-foreground/85"
                        >
                            <Building2
                                class="size-3 shrink-0 text-brand-orange"
                            />
                            <span class="truncate"
                                >Teixeira Serviços Elétricos ME</span
                            >
                        </span>
                        <span
                            class="lp-mono absolute top-[8%] right-0 max-w-[46%] truncate rounded-lg border border-border bg-popover px-2 py-1 text-[10px] text-muted-foreground"
                        >
                            16.579.660/0001-60
                        </span>
                        <span
                            class="absolute bottom-[4%] left-0 rounded-lg border border-border bg-popover px-2 py-1 text-[10px] text-muted-foreground"
                        >
                            Caxias do Sul · RS
                        </span>
                        <span
                            class="absolute right-0 bottom-[4%] rounded-lg border border-border bg-popover px-2 py-1 text-[10px] text-muted-foreground"
                        >
                            fonte: Crédito
                        </span>
                    </div>
                </UseCaseCard>

                <UseCaseCard
                    v-reveal="160"
                    eyebrow="Auditoria"
                    title="Cada consulta fica registrada."
                    :href="modulesHref"
                >
                    <ul
                        class="divide-y divide-border rounded-xl border border-border bg-popover shadow-lg"
                        aria-hidden="true"
                    >
                        <li
                            v-for="entry in history"
                            :key="entry.id"
                            class="flex items-center gap-2.5 px-3 py-2.5 text-xs"
                        >
                            <span
                                class="size-1.5 shrink-0 rounded-full bg-brand-blue"
                            />
                            <span class="min-w-0 flex-1">
                                <span class="lp-mono block text-foreground/90">
                                    {{ entry.id }}
                                </span>
                                <span
                                    class="block text-[11px] text-muted-foreground"
                                >
                                    busca por {{ entry.type }} ·
                                    {{ entry.when }}
                                </span>
                            </span>
                            <span class="text-foreground/80 tabular-nums">
                                {{ entry.score }}
                            </span>
                            <ThumbsUp
                                v-if="entry.feedback === 'up'"
                                class="size-3.5 text-emerald-500"
                            />
                            <ThumbsDown
                                v-else-if="entry.feedback === 'down'"
                                class="size-3.5 text-brand-orange"
                            />
                            <span
                                v-else
                                class="w-3.5 text-center text-muted-foreground"
                            >
                                –
                            </span>
                        </li>
                    </ul>
                </UseCaseCard>
            </div>
        </div>
    </section>
</template>
