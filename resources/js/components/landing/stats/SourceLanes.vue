<script setup lang="ts">
import { computed } from 'vue';
import type { LandingSource } from '@/types/landing';

const props = defineProps<{
    sources: LandingSource[];
}>();

const MERGE_Y = 170;

const lanes = computed(() => {
    const total = props.sources.length;

    return props.sources.map((source, index) => {
        const y = total > 1 ? 50 + (index * 240) / (total - 1) : MERGE_Y;
        // Fonte mais confiável, fluxo mais rápido.
        const duration = 2.2 + (100 - source.reliability) / 30;

        return {
            ...source,
            y,
            duration,
            path: `M300 ${y} L600 ${y} C720 ${y}, 720 ${MERGE_Y}, 840 ${MERGE_Y}`,
        };
    });
});
</script>

<template>
    <svg
        viewBox="0 0 1200 340"
        preserveAspectRatio="xMidYMid meet"
        class="h-full w-full"
        aria-hidden="true"
    >
        <g v-for="lane in lanes" :key="lane.slug">
            <text
                x="280"
                :y="lane.y - 4"
                text-anchor="end"
                class="fill-foreground/85 text-[20px] font-semibold"
            >
                {{ lane.name }}
            </text>
            <text
                x="280"
                :y="lane.y + 20"
                text-anchor="end"
                class="fill-muted-foreground text-[14px]"
            >
                confiabilidade {{ lane.reliability }}%
            </text>
            <path
                :d="lane.path"
                fill="none"
                class="stroke-foreground/15"
                stroke-width="1.5"
            />
            <circle v-for="dot in 3" :key="dot" r="4" class="fill-brand-blue">
                <animateMotion
                    :dur="`${lane.duration}s`"
                    :begin="`-${(lane.duration / 3) * dot}s`"
                    repeatCount="indefinite"
                    :path="lane.path"
                />
            </circle>
        </g>

        <circle :cy="MERGE_Y" cx="840" r="26" class="fill-brand-blue/15" />
        <circle :cy="MERGE_Y" cx="840" r="10" class="fill-brand-blue" />
        <path
            :d="`M850 ${MERGE_Y} L960 ${MERGE_Y}`"
            class="stroke-brand-orange"
            stroke-width="1.5"
            stroke-dasharray="4 5"
        />

        <rect
            x="960"
            :y="MERGE_Y - 62"
            width="210"
            height="124"
            rx="16"
            class="fill-popover stroke-border"
        />
        <text
            x="984"
            :y="MERGE_Y - 26"
            class="fill-foreground text-[18px] font-semibold"
        >
            Registro único
        </text>
        <text
            x="984"
            :y="MERGE_Y + 2"
            class="lp-mono fill-muted-foreground text-[14px]"
        >
            REG-0001
        </text>
        <rect
            x="984"
            :y="MERGE_Y + 18"
            width="96"
            height="26"
            rx="7"
            class="fill-emerald-500/15"
        />
        <text
            x="1032"
            :y="MERGE_Y + 36"
            text-anchor="middle"
            class="fill-emerald-500 text-[14px] font-semibold"
        >
            90 · Alto
        </text>
    </svg>
</template>
