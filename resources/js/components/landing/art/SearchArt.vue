<script setup lang="ts">
import { ChevronDown, CornerDownLeft } from '@lucide/vue';

defineProps<{
    sources: string[];
}>();
</script>

<template>
    <div
        class="relative flex h-full flex-col items-center justify-center gap-3 px-6"
        aria-hidden="true"
    >
        <div
            class="flex w-full max-w-xs items-center gap-2 rounded-full border border-border bg-surface-2/90 py-1.5 pr-1.5 pl-3 text-xs shadow-lg shadow-black/20"
        >
            <span
                class="flex items-center gap-1 rounded-full bg-brand-blue/15 px-2 py-0.5 text-[10px] font-semibold text-brand-blue"
            >
                CPF
                <ChevronDown class="size-3" />
            </span>
            <span class="lp-mono flex-1 truncate text-foreground/90">
                301.482.115-10<span
                    class="ml-px inline-block h-3 w-px translate-y-0.5 animate-pulse bg-foreground"
                />
            </span>
            <span
                class="flex size-6 items-center justify-center rounded-full bg-primary text-primary-foreground"
            >
                <CornerDownLeft class="size-3" />
            </span>
        </div>

        <div class="relative w-full max-w-xs">
            <div class="flex justify-between gap-1">
                <span
                    v-for="(name, index) in sources"
                    :key="name"
                    class="lp-light-up rounded-md border border-border px-1.5 py-0.5 text-[10px] whitespace-nowrap text-muted-foreground"
                    :style="{ animationDelay: `${index * 0.45}s` }"
                >
                    {{ name }}
                </span>
            </div>
            <svg
                class="h-7 w-full text-brand-blue"
                viewBox="0 0 100 28"
                preserveAspectRatio="none"
            >
                <path
                    v-for="x in [12, 37, 63, 88]"
                    :key="x"
                    :d="`M${x} 0 C${x} 16, 50 12, 50 28`"
                    fill="none"
                    stroke="currentColor"
                    stroke-opacity="0.45"
                    stroke-width="1"
                    stroke-dasharray="3 3"
                    vector-effect="non-scaling-stroke"
                />
            </svg>
        </div>

        <div
            class="flex w-full max-w-xs items-center gap-3 rounded-xl border border-border bg-popover px-3 py-2.5 shadow-xl shadow-black/25"
        >
            <span
                class="flex size-8 items-center justify-center rounded-full bg-brand-blue/15 text-[11px] font-semibold text-brand-blue"
            >
                AL
            </span>
            <span class="min-w-0 flex-1">
                <span
                    class="block truncate text-[13px] font-semibold text-foreground"
                >
                    Aurora Mendes Lacerda
                </span>
                <span class="block text-[11px] text-muted-foreground">
                    Registro único · 4 fontes
                </span>
            </span>
            <span
                class="rounded-md bg-emerald-500/15 px-1.5 py-0.5 text-[11px] font-semibold text-emerald-500 tabular-nums"
            >
                90 · Alto
            </span>
        </div>
    </div>
</template>
