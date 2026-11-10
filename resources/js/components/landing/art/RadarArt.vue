<script setup lang="ts">
import { ShieldAlert } from '@lucide/vue';
</script>

<template>
    <div
        class="relative flex h-full items-center justify-center"
        aria-hidden="true"
    >
        <div class="relative size-28">
            <span
                v-for="ring in 3"
                :key="ring"
                class="absolute inset-0 rounded-full border border-brand-blue/25"
                :style="{ transform: `scale(${0.4 + ring * 0.2})` }"
            />
            <span
                class="absolute inset-0 animate-spin rounded-full [animation-duration:4s]"
                style="
                    background: conic-gradient(
                        from 0deg,
                        transparent 0deg,
                        rgb(91 140 255 / 0.32) 60deg,
                        transparent 61deg
                    );
                "
            />
            <span
                class="absolute top-5 right-6 size-1.5 rounded-full bg-[#ff8a5b]"
            />
            <span
                class="lp-pulse-ring absolute top-4 right-5 size-3.5 rounded-full border border-[#ff8a5b]"
            />
            <span
                class="absolute bottom-7 left-6 size-1 rounded-full bg-brand-blue"
            />
            <span
                class="absolute inset-0 m-auto flex size-10 items-center justify-center rounded-xl border border-border bg-popover text-brand-blue shadow-lg"
            >
                <ShieldAlert class="size-5" />
            </span>
        </div>
    </div>
</template>
