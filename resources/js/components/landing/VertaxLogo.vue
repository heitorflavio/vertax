<script setup lang="ts">
import { useId } from 'vue';

const gradient = useId();
</script>

<template>
    <span class="flex items-center gap-2 text-foreground">
        <svg viewBox="0 0 28 28" class="size-7" aria-hidden="true">
            <defs>
                <linearGradient :id="gradient" x1="0" y1="0" x2="1" y2="1">
                    <stop offset="0" stop-color="#5b8cff" />
                    <stop offset="1" stop-color="#ff8a5b" />
                </linearGradient>
            </defs>
            <path
                d="M5 6.5 14 21.5 23 6.5"
                fill="none"
                :stroke="`url(#${gradient})`"
                stroke-width="2.6"
                stroke-linecap="round"
                stroke-linejoin="round"
            />
            <circle cx="5" cy="6.5" r="2.3" fill="currentColor" />
            <circle cx="23" cy="6.5" r="2.3" fill="currentColor" />
            <circle cx="14" cy="21.5" r="3" fill="#ff8a5b" />
        </svg>
        <span class="text-[19px] font-semibold tracking-tight">Vertax</span>
    </span>
</template>
