<script setup lang="ts">
import { useId } from 'vue';

const gradient = useId();

const score = 90;
const dimensions = [
    { label: 'Qualidade', value: 73.5 },
    { label: 'Completude', value: 100 },
    { label: 'Consistência', value: 100 },
    { label: 'Atualidade', value: 97.5 },
];

// Semicírculo de raio 70 centrado em (90, 90): comprimento = π·r.
const arcLength = Math.PI * 70;
</script>

<template>
    <div
        class="flex h-full flex-col items-center justify-center gap-3 px-6"
        aria-hidden="true"
    >
        <div class="relative">
            <svg viewBox="0 0 180 100" class="h-24 w-44">
                <defs>
                    <linearGradient :id="gradient" x1="0" y1="0" x2="1" y2="0">
                        <stop offset="0" stop-color="#ff8a5b" />
                        <stop offset="0.55" stop-color="#5b8cff" />
                        <stop offset="1" stop-color="#3e8bff" />
                    </linearGradient>
                </defs>
                <path
                    d="M20 90 A70 70 0 0 1 160 90"
                    fill="none"
                    stroke="currentColor"
                    class="text-foreground/10"
                    stroke-width="10"
                    stroke-linecap="round"
                />
                <path
                    d="M20 90 A70 70 0 0 1 160 90"
                    fill="none"
                    :stroke="`url(#${gradient})`"
                    stroke-width="10"
                    stroke-linecap="round"
                    :stroke-dasharray="arcLength"
                    :stroke-dashoffset="arcLength * (1 - score / 100)"
                />
            </svg>
            <div class="absolute inset-x-0 bottom-0 flex flex-col items-center">
                <span
                    class="text-3xl leading-none font-bold tracking-tight text-foreground tabular-nums"
                >
                    {{ score }}
                </span>
                <span class="text-[11px] font-medium text-emerald-500">
                    Confiança alta
                </span>
            </div>
        </div>
        <div class="grid w-full max-w-xs grid-cols-2 gap-x-4 gap-y-2">
            <div
                v-for="dimension in dimensions"
                :key="dimension.label"
                class="text-[11px]"
            >
                <div class="flex justify-between text-muted-foreground">
                    <span>{{ dimension.label }}</span>
                    <span class="text-foreground/80 tabular-nums">
                        {{ dimension.value.toLocaleString('pt-BR') }}
                    </span>
                </div>
                <div class="mt-1 h-1 rounded-full bg-foreground/10">
                    <div
                        class="h-full rounded-full bg-brand-blue"
                        :style="{ width: `${dimension.value}%` }"
                    />
                </div>
            </div>
        </div>
    </div>
</template>
