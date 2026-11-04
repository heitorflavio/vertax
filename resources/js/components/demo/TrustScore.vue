<script setup lang="ts">
import { computed } from 'vue';
import { Badge } from '@/components/ui/badge';
import { trustVariant } from '@/lib/demoFormat';
import type { DemoTrust } from '@/types/demo';

const props = defineProps<{
    trust: DemoTrust;
}>();

const dimensions = computed(() => {
    const breakdown = props.trust.breakdown;

    return [
        {
            label: 'Qualidade das fontes',
            value: breakdown.source_quality,
            hint: `Média de ${breakdown.sources_evaluated} fontes`,
        },
        {
            label: 'Completude',
            value: breakdown.completeness,
            hint: `${breakdown.fields_filled} de ${breakdown.fields_total} campos`,
        },
        {
            label: 'Consistência',
            value: breakdown.consistency,
            hint: 'Concordância entre fontes',
        },
        {
            label: 'Atualidade',
            value: breakdown.freshness,
            hint:
                breakdown.newest_payload_age_days === null
                    ? 'Sem data de coleta'
                    : `Coleta mais recente há ${breakdown.newest_payload_age_days} dia(s)`,
        },
    ];
});
</script>

<template>
    <div class="space-y-6">
        <div class="flex items-end justify-between gap-4">
            <p class="text-4xl font-semibold tracking-tight tabular-nums">
                {{ trust.score }}
                <span class="text-base font-normal text-muted-foreground">
                    / 100
                </span>
            </p>
            <Badge :variant="trustVariant(trust.score)">
                {{ trust.label }}
            </Badge>
        </div>

        <div class="space-y-4">
            <div
                v-for="dimension in dimensions"
                :key="dimension.label"
                class="space-y-1.5"
            >
                <div class="flex items-center justify-between text-sm">
                    <span>{{ dimension.label }}</span>
                    <span class="text-muted-foreground tabular-nums">
                        {{ dimension.value }}%
                    </span>
                </div>
                <div class="h-1.5 overflow-hidden rounded-full bg-muted">
                    <div
                        class="h-full rounded-full bg-primary"
                        :style="{ width: `${dimension.value}%` }"
                    />
                </div>
                <p class="text-xs text-muted-foreground">
                    {{ dimension.hint }}
                </p>
            </div>
        </div>

        <p class="text-sm text-muted-foreground">
            {{ trust.breakdown.explanation }}
            <template v-if="trust.breakdown.missing_fields.length > 0">
                Campos ausentes:
                {{ trust.breakdown.missing_fields.join(', ') }}.
            </template>
        </p>
    </div>
</template>
