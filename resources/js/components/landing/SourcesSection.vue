<script setup lang="ts">
import { ChevronDown, Move } from '@lucide/vue';
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import DotGlobeCanvas from '@/components/landing/DotGlobeCanvas.vue';
import SectionHeading from '@/components/landing/SectionHeading.vue';
import { vReveal } from '@/lib/landing/reveal';
import type { LandingSource } from '@/types/landing';

const props = defineProps<{
    sources: LandingSource[];
    light: boolean;
}>();

const selected = ref(0);
const menuOpen = ref(false);
const picker = ref<HTMLElement | null>(null);

const current = computed(() => props.sources[selected.value]);

const ranking = computed(() =>
    [...props.sources].sort((a, b) => b.reliability - a.reliability),
);

const cards = computed(() => {
    const source = current.value;

    if (!source) return [];

    return [
        {
            value: `${source.reliability}%`,
            label: 'Confiabilidade da fonte',
        },
        {
            value: `${ranking.value.findIndex((s) => s.slug === source.slug) + 1}º`,
            label: `Prioridade na mesclagem, entre ${props.sources.length}`,
        },
        {
            value: String(source.search_types.length),
            label: `Tipos de busca: ${source.search_types.join(', ')}`,
        },
        { value: source.category, label: 'Categoria' },
    ];
});

function choose(index: number): void {
    selected.value = index;
    menuOpen.value = false;
}

function onPointerDown(event: PointerEvent): void {
    if (picker.value && !picker.value.contains(event.target as Node)) {
        menuOpen.value = false;
    }
}

onMounted(() => document.addEventListener('pointerdown', onPointerDown));
onBeforeUnmount(() =>
    document.removeEventListener('pointerdown', onPointerDown),
);
</script>

<template>
    <section
        id="fontes"
        class="lp-section relative isolate overflow-hidden border-b border-border bg-band"
    >
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div
                class="mb-8 grid items-center gap-8 lg:grid-cols-2 lg:gap-x-16"
            >
                <div class="relative z-20 min-w-0">
                    <SectionHeading
                        eyebrow="Fontes integradas"
                        title="Feito para o Brasil. Um peso por fonte."
                    >
                        Cada fonte tem um peso de confiabilidade. Quando duas
                        discordam, vale a mais confiável, e a consistência entre
                        elas entra no score.
                    </SectionHeading>

                    <div v-if="current" v-reveal="160" class="mt-5">
                        <div
                            ref="picker"
                            class="relative"
                            @keydown.esc="menuOpen = false"
                        >
                            <button
                                type="button"
                                class="flex w-full items-center justify-between gap-3 rounded-xl border border-border bg-card px-4 py-2.5 text-sm font-medium transition-colors hover:border-brand-blue/40 3xl:py-3.5"
                                aria-haspopup="listbox"
                                :aria-expanded="menuOpen"
                                @click="menuOpen = !menuOpen"
                            >
                                <span class="flex min-w-0 items-center gap-2">
                                    <span
                                        class="size-2 shrink-0 rounded-full bg-brand-blue"
                                    />
                                    <span class="truncate text-foreground">
                                        {{ current.name }}
                                    </span>
                                </span>
                                <ChevronDown
                                    class="size-4 shrink-0 text-muted-foreground transition-transform"
                                    :class="menuOpen ? 'rotate-180' : ''"
                                />
                            </button>
                            <ul
                                v-if="menuOpen"
                                role="listbox"
                                aria-label="Fontes"
                                class="absolute inset-x-0 top-full z-30 mt-2 overflow-hidden rounded-xl border border-border bg-popover p-1 shadow-2xl shadow-black/30"
                            >
                                <li
                                    v-for="(source, index) in sources"
                                    :key="source.slug"
                                    role="option"
                                    :aria-selected="index === selected"
                                >
                                    <button
                                        type="button"
                                        class="flex w-full items-center justify-between gap-3 rounded-lg px-3 py-2 text-left text-sm hover:bg-foreground/5"
                                        :class="
                                            index === selected
                                                ? 'text-foreground'
                                                : 'text-foreground/75'
                                        "
                                        @click="choose(index)"
                                    >
                                        {{ source.name }}
                                        <span
                                            class="text-xs text-muted-foreground"
                                        >
                                            {{ source.category }} ·
                                            {{ source.reliability }}%
                                        </span>
                                    </button>
                                </li>
                            </ul>
                        </div>
                        <p
                            class="mt-2 flex flex-wrap items-center gap-1.5 text-xs text-muted-foreground"
                        >
                            Mostrando dados de
                            <span class="font-semibold text-foreground">
                                {{ current.name }}
                            </span>
                            ·
                            <span class="text-brand-orange">
                                {{ current.description }}
                            </span>
                        </p>

                        <div class="mt-6 grid grid-cols-2 gap-3 sm:gap-4">
                            <div
                                v-for="card in cards"
                                :key="card.label"
                                class="overflow-hidden rounded-xl border border-border bg-card p-4"
                            >
                                <div
                                    class="mb-0.5 truncate text-2xl font-bold text-brand-blue tabular-nums"
                                >
                                    {{ card.value }}
                                </div>
                                <div class="text-sm text-muted-foreground">
                                    {{ card.label }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div
                    class="relative z-10 mx-auto w-full max-w-md min-w-0 lg:max-w-none 3xl:max-w-[42rem]"
                >
                    <div class="relative aspect-square w-full">
                        <DotGlobeCanvas :light="light" />
                    </div>
                    <p
                        class="mt-2 flex items-center justify-center gap-1.5 text-xs text-muted-foreground"
                    >
                        <Move class="size-3.5" />
                        Arraste para girar · capitais em laranja
                    </p>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
                <button
                    v-for="(source, index) in sources"
                    :key="source.slug"
                    type="button"
                    class="flex flex-col items-center gap-1.5 rounded-xl border px-3 py-4 text-center transition-colors"
                    :class="
                        index === selected
                            ? 'border-brand-blue/50 bg-brand-blue/8'
                            : 'border-border bg-card hover:border-foreground/20'
                    "
                    :aria-pressed="index === selected"
                    @click="choose(index)"
                >
                    <span class="text-sm font-medium text-foreground">
                        {{ source.name }}
                    </span>
                    <span class="text-xs text-muted-foreground tabular-nums">
                        {{ source.category }} · {{ source.reliability }}%
                    </span>
                </button>
            </div>
        </div>
    </section>
</template>
