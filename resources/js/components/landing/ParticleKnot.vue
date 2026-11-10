<script setup lang="ts">
import { onBeforeUnmount, onMounted, ref, watch } from 'vue';
import {
    createParticleKnot,
    type ParticleKnot,
} from '@/lib/landing/particleKnot';

const props = defineProps<{
    light: boolean;
}>();

const host = ref<HTMLDivElement | null>(null);
let knot: ParticleKnot | null = null;

onMounted(() => {
    if (!host.value) return;

    knot = createParticleKnot(host.value, {
        light: props.light,
        reducedMotion: window.matchMedia('(prefers-reduced-motion: reduce)')
            .matches,
    });
});

watch(
    () => props.light,
    (light) => knot?.setLight(light),
);

onBeforeUnmount(() => {
    knot?.destroy();
    knot = null;
});
</script>

<template>
    <div ref="host" aria-hidden="true" class="absolute inset-0" />
</template>
