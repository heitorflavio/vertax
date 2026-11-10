<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import FloatingIdsCanvas from '@/components/landing/FloatingIdsCanvas.vue';
import FieldsMerge from '@/components/landing/stats/FieldsMerge.vue';
import SourceLanes from '@/components/landing/stats/SourceLanes.vue';
import TrustBlend from '@/components/landing/stats/TrustBlend.vue';
import type { LandingSource } from '@/types/landing';

const props = defineProps<{
    searchTypes: string[];
    sources: LandingSource[];
    mergedFields: string[];
    trustWeights: Record<string, number>;
    light: boolean;
}>();

const ROTATE_MS = 6000;

function humanList(items: string[]): string {
    return items.length > 1
        ? `${items.slice(0, -1).join(', ')} e ${items[items.length - 1]}`
        : (items[0] ?? '');
}

const stats = computed(() => [
    {
        value: props.searchTypes.length,
        prefix: '',
        label: 'Tipos de busca',
        description: humanList(props.searchTypes),
        caption: 'Identificadores mascarados, gerados na hora',
    },
    {
        value: props.sources.length,
        prefix: '',
        label: 'Fontes integradas',
        description: humanList(props.sources.map((source) => source.name)),
        caption: 'Cada fonte com seu peso de confiabilidade',
    },
    {
        value: props.mergedFields.length,
        prefix: '',
        label: 'Campos mesclados',
        description: 'Um registro único, sem duplicidade entre fontes',
        caption: 'De onde costuma vir cada campo',
    },
    {
        value: 100,
        prefix: '0–',
        label: 'Score de confiança',
        description: 'Qualidade, completude, consistência e atualidade',
        caption: 'Como as quatro dimensões viram uma nota',
    },
]);

const active = ref(0);
const auto = ref(true);
// Começa nos valores finais (é o que o SSR entrega); a contagem só roda
// quando a faixa entra na tela.
const progress = ref(1);
const visible = ref(false);
const band = ref<HTMLElement | null>(null);
let counted = false;
let timer: number | undefined;
let observer: IntersectionObserver | null = null;

function select(index: number): void {
    active.value = index;
    auto.value = false;
    window.clearInterval(timer);
}

function countUp(): void {
    counted = true;

    if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;

    progress.value = 0;
    const start = performance.now();
    const tick = (now: number): void => {
        const t = Math.min((now - start) / 1100, 1);
        progress.value = 1 - (1 - t) ** 3;

        if (t < 1) requestAnimationFrame(tick);
    };
    requestAnimationFrame(tick);
}

onMounted(() => {
    observer = new IntersectionObserver(([entry]) => {
        visible.value = entry.isIntersecting;

        if (entry.isIntersecting && !counted) countUp();
    });

    if (band.value) observer.observe(band.value);

    timer = window.setInterval(() => {
        if (auto.value && visible.value && !document.hidden) {
            active.value = (active.value + 1) % stats.value.length;
        }
    }, ROTATE_MS);
});

onBeforeUnmount(() => {
    window.clearInterval(timer);
    observer?.disconnect();
});
</script>

<template>
    <section
        ref="band"
        class="border-y border-border bg-band"
        aria-label="A plataforma em números"
    >
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div
                class="grid grid-cols-2 border-border md:flex md:divide-x md:divide-border"
            >
                <button
                    v-for="(stat, index) in stats"
                    :key="stat.label"
                    type="button"
                    class="relative flex min-w-0 flex-1 cursor-pointer flex-col items-start overflow-hidden px-4 py-5 text-left transition-colors duration-200 outline-none focus-visible:ring-2 focus-visible:ring-brand-blue/45 focus-visible:ring-inset lg:px-6"
                    :class="active === index ? 'bg-foreground/3' : ''"
                    :aria-pressed="active === index"
                    @click="select(index)"
                >
                    <span
                        v-if="active === index"
                        class="pointer-events-none absolute inset-x-0 bottom-0 h-0.5 bg-foreground/8"
                    >
                        <span
                            :key="`${index}-${auto}`"
                            class="block h-full bg-linear-to-r from-brand-orange to-brand-blue"
                            :class="auto ? 'lp-progress' : ''"
                            :style="{ '--duration': `${ROTATE_MS}ms` }"
                        />
                    </span>
                    <span
                        class="text-3xl font-bold tracking-tight tabular-nums transition-colors duration-200"
                        :class="
                            active === index
                                ? 'text-foreground'
                                : 'text-foreground/70'
                        "
                    >
                        {{ stat.prefix }}{{ Math.round(stat.value * progress) }}
                    </span>
                    <span
                        class="mt-1 text-sm font-semibold transition-colors duration-200"
                        :class="
                            active === index
                                ? 'text-foreground'
                                : 'text-muted-foreground'
                        "
                    >
                        {{ stat.label }}
                    </span>
                    <span
                        class="mt-1 text-xs leading-snug text-muted-foreground"
                    >
                        {{ stat.description }}
                    </span>
                </button>
            </div>

            <div class="hidden border-t border-border md:block">
                <div class="relative h-85 overflow-hidden">
                    <p
                        class="absolute right-4 bottom-3 z-10 rounded-md bg-band/85 px-2 py-1 text-[11px] text-muted-foreground lg:right-6"
                    >
                        {{ stats[active].caption }}
                    </p>
                    <div
                        class="flex h-full transition-transform duration-700 ease-[cubic-bezier(0.2,0.7,0.2,1)]"
                        :style="{ transform: `translateX(-${active * 100}%)` }"
                    >
                        <div
                            class="relative h-full w-full shrink-0"
                            :inert="active !== 0"
                        >
                            <FloatingIdsCanvas
                                :light="light"
                                :active="active === 0 && visible"
                            />
                        </div>
                        <div
                            class="relative h-full w-full shrink-0 px-4 py-6"
                            :inert="active !== 1"
                        >
                            <SourceLanes :sources="sources" />
                        </div>
                        <div
                            class="relative h-full w-full shrink-0"
                            :inert="active !== 2"
                        >
                            <FieldsMerge :fields="mergedFields" />
                        </div>
                        <div
                            class="relative h-full w-full shrink-0"
                            :inert="active !== 3"
                        >
                            <TrustBlend :weights="trustWeights" />
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</template>
