<script setup lang="ts">
import { onBeforeUnmount, onMounted, ref, watch } from 'vue';
import { createDotGlobe, type DotGlobe } from '@/lib/landing/dotGlobe';

const props = defineProps<{
    light: boolean;
}>();

const host = ref<HTMLDivElement | null>(null);
let globe: DotGlobe | null = null;

onMounted(() => {
    if (!host.value) return;

    globe = createDotGlobe(host.value, {
        light: props.light,
        reducedMotion: window.matchMedia('(prefers-reduced-motion: reduce)')
            .matches,
    });
});

watch(
    () => props.light,
    (light) => globe?.setLight(light),
);

onBeforeUnmount(() => {
    globe?.destroy();
    globe = null;
});
</script>

<template>
    <div ref="host" class="absolute inset-0 select-none" />
</template>
