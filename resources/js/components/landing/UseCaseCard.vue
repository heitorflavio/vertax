<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ArrowUpRight } from '@lucide/vue';

withDefaults(
    defineProps<{
        eyebrow: string;
        title: string;
        href: string;
        description?: string;
        large?: boolean;
    }>(),
    { description: undefined, large: false },
);
</script>

<template>
    <article
        class="group relative isolate flex min-w-0 flex-col overflow-hidden rounded-2xl border border-border bg-card focus-within:ring-2 focus-within:ring-brand-blue/50"
    >
        <div class="lp-art" aria-hidden="true">
            <span
                class="lp-blob -top-16 -right-[10%] size-72 bg-[rgb(var(--rgb-orange)/0.55)]"
            />
            <span
                class="lp-blob top-24 -left-20 size-64 bg-[rgb(var(--rgb-blue)/0.35)] [animation-delay:-6s]"
            />
        </div>
        <div class="relative z-10 flex flex-1 flex-col p-6 sm:p-7">
            <div class="flex items-start justify-between gap-4">
                <div class="min-w-0">
                    <p
                        class="mb-1.5 text-xs font-semibold tracking-wide text-brand-blue"
                    >
                        {{ eyebrow }}
                    </p>
                    <h3
                        class="font-semibold tracking-[-0.01em] text-foreground"
                        :class="large ? 'text-xl' : 'text-[17px]'"
                    >
                        <Link
                            :href="href"
                            class="outline-none after:absolute after:inset-0 after:z-20"
                        >
                            {{ title }}
                        </Link>
                    </h3>
                </div>
                <span
                    class="flex size-9 shrink-0 items-center justify-center rounded-lg bg-brand-blue/10 text-brand-blue transition-colors duration-200 group-hover:bg-brand-blue group-hover:text-white"
                    aria-hidden="true"
                >
                    <ArrowUpRight class="size-4" />
                </span>
            </div>
            <p
                v-if="description"
                class="mt-1.5 max-w-md text-sm leading-relaxed text-foreground/80"
            >
                {{ description }}
            </p>
            <div class="mt-5 flex flex-1 flex-col justify-end">
                <slot />
            </div>
        </div>
    </article>
</template>
