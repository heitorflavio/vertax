<script setup lang="ts">
import { onBeforeUnmount, onMounted, ref, watch } from 'vue';
import { createFloatingIds, type FloatingIds } from '@/lib/landing/floatingIds';

const props = defineProps<{
    light: boolean;
    active: boolean;
}>();

const host = ref<HTMLDivElement | null>(null);
let cloud: FloatingIds | null = null;

onMounted(() => {
    if (!host.value) return;

    cloud = createFloatingIds(host.value, {
        light: props.light,
        reducedMotion: window.matchMedia('(prefers-reduced-motion: reduce)')
            .matches,
    });
    cloud?.setActive(props.active);
});

watch(
    () => props.light,
    (light) => cloud?.setLight(light),
);

watch(
    () => props.active,
    (active) => cloud?.setActive(active),
);

onBeforeUnmount(() => {
    cloud?.destroy();
    cloud = null;
});
</script>

<template>
    <div ref="host" aria-hidden="true" class="absolute inset-0" />
</template>
