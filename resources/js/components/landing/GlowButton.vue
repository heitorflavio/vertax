<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ArrowRight } from '@lucide/vue';

defineProps<{
    href: string;
}>();
</script>

<template>
    <span class="lp-glow-btn">
        <Link :href="href">
            <slot />
            <ArrowRight class="size-4" />
        </Link>
    </span>
</template>
