<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ArrowRight } from '@lucide/vue';
import { computed } from 'vue';
import NetworkArt from '@/components/landing/art/NetworkArt.vue';
import RadarArt from '@/components/landing/art/RadarArt.vue';
import SearchArt from '@/components/landing/art/SearchArt.vue';
import TrustArt from '@/components/landing/art/TrustArt.vue';
import SectionHeading from '@/components/landing/SectionHeading.vue';
import { vReveal } from '@/lib/landing/reveal';
import { login, register } from '@/routes';
import demo from '@/routes/demo';
import type { LandingModule, LandingSource } from '@/types/landing';

const props = defineProps<{
    canRegister: boolean;
    sources: LandingSource[];
    modules: LandingModule[];
    trustWeights: Record<string, number>;
}>();

const percent = (key: string): string =>
    `${Math.round((props.trustWeights[key] ?? 0) * 100)}%`;

const coverageWeights = computed(() => {
    const completeness = percent('completeness');
    const consistency = percent('consistency');

    return completeness === consistency
        ? `${completeness} cada`
        : `${completeness} e ${consistency}`;
});

const featured = computed(() => {
    const wanted = ['vinculos-demo', 'alerta-fraude-demo'];
    const picked = props.modules.filter((module) =>
        wanted.includes(module.slug),
    );

    return (picked.length ? picked : props.modules).slice(0, 2);
});
</script>

<template>
    <section id="produto" class="lp-section border-b border-border">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <SectionHeading eyebrow="Produto" class="mb-8 sm:mb-16">
                <template #title>
                    Consulta multi-fonte e score de confiança. Módulos para ir
                    além.
                </template>
                Informe um nome, CPF, CNPJ, CNH, e-mail ou telefone e receba um
                registro único, montado a partir de todas as fontes que aceitam
                aquele tipo de busca:
                <strong class="font-semibold text-foreground">
                    consulta multi-fonte com score de confiança</strong
                >. Módulos como vínculos societários e alerta de fraude liberam
                as informações ocultas.
                <template #note>
                    A busca exige uma conta. Compare os
                    <Link
                        :href="demo.plans()"
                        class="text-foreground underline decoration-foreground/30 underline-offset-4 hover:decoration-foreground"
                        >planos</Link
                    >
                    ou veja o
                    <Link
                        :href="demo.modules()"
                        class="text-foreground underline decoration-foreground/30 underline-offset-4 hover:decoration-foreground"
                        >catálogo de módulos</Link
                    >.
                </template>
            </SectionHeading>

            <div
                class="flex flex-col gap-4 sm:grid sm:grid-cols-2 sm:gap-5 lg:grid-cols-3 lg:grid-rows-2"
            >
                <article
                    v-reveal
                    class="lp-card-wash relative flex flex-col overflow-hidden rounded-2xl border border-border bg-card ring-1 ring-brand-blue/20 ring-inset lg:row-span-2"
                >
                    <div class="h-56 w-full">
                        <SearchArt :sources="sources.map((s) => s.name)" />
                    </div>
                    <div class="flex flex-1 flex-col gap-3 p-6">
                        <h3 class="text-lg font-semibold text-foreground">
                            Consulta multi-fonte
                        </h3>
                        <p
                            class="text-sm leading-relaxed text-muted-foreground"
                        >
                            Um registro consolidado, sem duplicidade, a partir
                            de todas as fontes que respondem à busca.
                        </p>
                        <ul class="flex flex-1 flex-col gap-1.5">
                            <li
                                v-for="item in [
                                    'Nome, CPF, CNPJ, CNH, e-mail e telefone',
                                    'Vence a fonte mais confiável em cada campo',
                                    'Origem de cada campo registrada',
                                ]"
                                :key="item"
                                class="flex items-center gap-2 text-sm text-muted-foreground"
                            >
                                <span
                                    class="size-1 shrink-0 rounded-full bg-brand-blue"
                                />
                                {{ item }}
                            </li>
                        </ul>
                        <Link
                            :href="canRegister ? register() : login()"
                            class="mt-1 inline-flex h-8 w-fit items-center gap-1.5 rounded-lg bg-primary px-3 text-sm font-medium text-primary-foreground transition-opacity hover:opacity-85"
                        >
                            Começar a consultar
                            <ArrowRight class="size-3.5" />
                        </Link>
                    </div>
                </article>

                <article
                    v-reveal="80"
                    class="lp-card-wash-alt relative flex flex-col overflow-hidden rounded-2xl border border-border bg-card lg:row-span-2"
                >
                    <div class="h-56 w-full">
                        <TrustArt />
                    </div>
                    <div class="flex flex-1 flex-col gap-3 p-6">
                        <h3 class="text-lg font-semibold text-foreground">
                            Score de confiança
                        </h3>
                        <p
                            class="text-sm leading-relaxed text-muted-foreground"
                        >
                            Uma nota de 0 a 100 para cada registro, explicada em
                            quatro dimensões.
                        </p>
                        <ul class="flex flex-1 flex-col gap-1.5">
                            <li
                                v-for="item in [
                                    `Qualidade da fonte · ${percent('source_quality')}`,
                                    `Completude e consistência · ${coverageWeights}`,
                                    `Atualidade dos dados · ${percent('freshness')}`,
                                ]"
                                :key="item"
                                class="flex items-center gap-2 text-sm text-muted-foreground"
                            >
                                <span
                                    class="size-1 shrink-0 rounded-full bg-brand-orange"
                                />
                                {{ item }}
                            </li>
                        </ul>
                        <Link
                            :href="demo.plans()"
                            class="mt-1 inline-flex h-8 w-fit items-center gap-1.5 rounded-lg border border-border bg-background px-3 text-sm font-medium text-foreground transition-colors hover:bg-muted"
                        >
                            Ver planos
                            <ArrowRight class="size-3.5" />
                        </Link>
                    </div>
                </article>

                <article
                    v-for="(module, index) in featured"
                    :key="module.slug"
                    v-reveal="160 + index * 80"
                    class="relative flex flex-col overflow-hidden rounded-2xl border border-border bg-card"
                    :class="index % 2 ? 'lp-card-wash-alt' : 'lp-card-wash'"
                >
                    <span
                        class="absolute top-4 right-4 z-10 rounded-full border border-brand-blue/30 bg-brand-blue/10 px-2 py-0.5 text-[10px] font-semibold tracking-wider text-brand-blue uppercase"
                    >
                        Módulo
                    </span>
                    <div class="h-32 w-full px-6 pt-4">
                        <NetworkArt v-if="module.icon === 'network'" />
                        <RadarArt v-else />
                    </div>
                    <div class="flex flex-1 flex-col gap-2 p-6 pt-3">
                        <h3 class="text-base font-semibold text-foreground">
                            {{ module.name }}
                        </h3>
                        <p
                            class="flex-1 text-xs leading-relaxed text-muted-foreground"
                        >
                            {{ module.description }}
                            Liberado com um plano ativo.
                        </p>
                        <Link
                            :href="demo.modules()"
                            class="mt-1 inline-flex h-8 w-fit items-center gap-1.5 rounded-lg border border-border bg-background px-3 text-sm font-medium text-foreground transition-colors hover:bg-muted"
                        >
                            Ver módulos
                            <ArrowRight class="size-3.5" />
                        </Link>
                    </div>
                </article>
            </div>
        </div>
    </section>
</template>
