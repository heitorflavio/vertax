<script setup lang="ts">
import { vReveal } from '@/lib/landing/reveal';

withDefaults(
    defineProps<{
        eyebrow: string;
        title?: string;
        centered?: boolean;
    }>(),
    { title: undefined, centered: false },
);
</script>

<template>
    <div :class="centered ? 'mx-auto max-w-2xl text-center' : 'max-w-2xl'">
        <p v-reveal class="mb-3 text-sm font-medium text-brand-blue">
            {{ eyebrow }}
        </p>
        <h2
            v-reveal="60"
            class="mb-4 text-3xl font-bold tracking-tight text-balance text-foreground sm:text-4xl"
        >
            <slot name="title">{{ title }}</slot>
        </h2>
        <div
            v-if="$slots.default"
            v-reveal="120"
            class="text-lg leading-relaxed text-muted-foreground"
        >
            <slot />
        </div>
        <div
            v-if="$slots.note"
            v-reveal="160"
            class="mt-4 text-sm leading-relaxed text-muted-foreground"
        >
            <slot name="note" />
        </div>
    </div>
</template>
