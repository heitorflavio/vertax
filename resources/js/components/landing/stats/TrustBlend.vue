<script setup lang="ts">
import { computed } from 'vue';

const props = defineProps<{
    weights: Record<string, number>;
}>();

// Breakdown real do registro fictício REG-0001.
const sample: Record<string, { label: string; value: number }> = {
    source_quality: { label: 'Qualidade da fonte', value: 73.5 },
    completeness: { label: 'Completude', value: 100 },
    consistency: { label: 'Consistência', value: 100 },
    freshness: { label: 'Atualidade', value: 97.5 },
};

const rows = computed(() =>
    Object.entries(sample).map(([key, item]) => ({
        key,
        ...item,
        weight: props.weights[key] ?? 0,
    })),
);

const score = computed(() =>
    rows.value.reduce((sum, row) => sum + row.weight * row.value, 0),
);

const number = (value: number): string => value.toLocaleString('pt-BR');
</script>

<template>
    <div
        class="flex h-full items-center gap-12 px-8 lg:px-12"
        aria-hidden="true"
    >
        <div class="flex-1 space-y-4">
            <div v-for="row in rows" :key="row.key">
                <div class="flex items-baseline justify-between text-sm">
                    <span class="text-foreground/85">
                        {{ row.label }}
                        <span class="ml-1.5 text-xs text-muted-foreground">
                            peso {{ Math.round(row.weight * 100) }}%
                        </span>
                    </span>
                    <span class="text-foreground tabular-nums">
                        {{ number(row.value) }}
                    </span>
                </div>
                <div class="mt-1.5 h-1.5 rounded-full bg-foreground/10">
                    <div
                        class="h-full rounded-full bg-linear-to-r from-brand-orange to-brand-blue"
                        :style="{ width: `${row.value}%` }"
                    />
                </div>
            </div>
        </div>
        <div class="w-72 shrink-0">
            <p class="text-xs text-muted-foreground">
                Exemplo: REG-0001, dados fictícios
            </p>
            <p
                class="mt-2 text-6xl font-bold tracking-tight text-foreground tabular-nums"
            >
                {{ Math.round(score) }}
                <span class="text-lg font-semibold text-emerald-500">Alto</span>
            </p>
            <p
                class="lp-mono mt-3 text-[11px] leading-relaxed text-muted-foreground"
            >
                <template v-for="(row, index) in rows" :key="row.key">
                    {{ index ? ' + ' : '' }}{{ number(row.weight) }} ×
                    {{ number(row.value) }}
                </template>
                ≈ {{ Math.round(score) }}
            </p>
        </div>
    </div>
</template>
