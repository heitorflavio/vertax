<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ArrowRight, History, KeyRound, UsersRound } from '@lucide/vue';
import { onBeforeUnmount, onMounted, ref } from 'vue';
import GlowButton from '@/components/landing/GlowButton.vue';
import ParticleKnot from '@/components/landing/ParticleKnot.vue';
import { login, register } from '@/routes';
import demo from '@/routes/demo';
import type { LandingSource } from '@/types/landing';

defineProps<{
    canRegister: boolean;
    sources: LandingSource[];
    light: boolean;
}>();

const benefits = [
    { icon: KeyRound, label: 'Passkeys e 2FA' },
    { icon: UsersRound, label: 'Acesso por papéis' },
    { icon: History, label: 'Histórico de consultas' },
];

// A arte só monta no cliente, já no lugar certo (lateral no desktop,
// abaixo do texto no celular), para não criar dois contextos WebGL.
const layout = ref<'desktop' | 'mobile' | null>(null);
let media: MediaQueryList | null = null;

function updateLayout(): void {
    layout.value = media?.matches ? 'desktop' : 'mobile';
}

onMounted(() => {
    media = window.matchMedia('(min-width: 1024px)');
    updateLayout();
    media.addEventListener('change', updateLayout);
});

onBeforeUnmount(() => media?.removeEventListener('change', updateLayout));

function filled(reliability: number): number {
    return Math.round(reliability / 20);
}
</script>

<template>
    <section
        id="topo"
        class="relative flex flex-col justify-center overflow-hidden pt-28 pb-16 sm:min-h-svh sm:py-24 lg:py-28"
        aria-label="Apresentação"
    >
        <div class="lp-ribbon-wrap" aria-hidden="true">
            <div class="lp-ribbon" />
        </div>

        <div
            v-if="layout === 'desktop'"
            class="pointer-events-none absolute inset-y-0 right-0 z-0 w-[60%]"
        >
            <ParticleKnot :light="light" />
        </div>

        <div
            class="relative z-10 mx-auto w-full max-w-7xl px-4 sm:px-6 lg:px-8"
        >
            <div class="lg:grid lg:grid-cols-2 lg:gap-8">
                <div
                    class="flex max-w-xl flex-col items-center text-center lg:items-start lg:text-left"
                >
                    <a
                        href="#produto"
                        class="lp-enter group inline-flex items-center gap-2 rounded-full border border-black/10 bg-white px-3.5 py-1.5 text-xs font-medium shadow-sm transition-colors hover:bg-zinc-100 sm:text-sm"
                    >
                        <span
                            class="rounded-full bg-[#2563eb14] px-2 py-0.5 text-[10px] font-semibold text-[#2563eb]"
                        >
                            Novo
                        </span>
                        <span
                            class="text-zinc-600 transition-colors group-hover:text-zinc-900"
                        >
                            Busca por CNH e CNPJ
                        </span>
                        <ArrowRight
                            class="size-3.5 text-zinc-500 transition-transform group-hover:translate-x-0.5"
                        />
                    </a>

                    <h1
                        class="lp-enter mt-5 text-4xl leading-[1.1] font-bold tracking-tight text-foreground sm:text-5xl lg:text-6xl"
                        style="animation-delay: 60ms"
                    >
                        <span class="block">Uma consulta.</span>
                        <span class="block">Todas as fontes.</span>
                    </h1>

                    <p
                        class="lp-enter mt-4 text-sm leading-relaxed font-medium text-muted-foreground sm:text-base"
                        style="animation-delay: 120ms"
                    >
                        Cruzamos cadastro, contato, localização e crédito em um
                        único registro, com score de confiança e a origem de
                        cada campo. Busque por nome, CPF, CNPJ, CNH, e-mail ou
                        telefone.
                    </p>

                    <div
                        class="lp-enter mt-6 flex w-full flex-col items-center lg:items-start"
                        style="animation-delay: 180ms"
                    >
                        <div
                            class="flex flex-wrap items-center justify-center gap-3 lg:justify-start"
                        >
                            <GlowButton
                                :href="
                                    canRegister ? register.url() : login.url()
                                "
                            >
                                {{
                                    canRegister
                                        ? 'Criar conta grátis'
                                        : 'Entrar'
                                }}
                            </GlowButton>
                            <Link
                                :href="demo.plans()"
                                class="inline-flex h-11 items-center gap-2 rounded-lg border border-input bg-input/30 px-6 text-sm font-medium text-foreground transition-colors hover:bg-input/60"
                            >
                                Ver planos
                            </Link>
                        </div>
                        <ul
                            class="mt-4 flex flex-wrap justify-center gap-x-5 gap-y-2 lg:justify-start"
                            aria-label="Destaques"
                        >
                            <li
                                v-for="benefit in benefits"
                                :key="benefit.label"
                                class="flex items-center gap-2 text-sm text-muted-foreground"
                            >
                                <component :is="benefit.icon" class="size-4" />
                                {{ benefit.label }}
                            </li>
                        </ul>
                    </div>

                    <div
                        v-if="sources.length"
                        class="lp-enter mt-10 flex w-full max-w-xl flex-wrap items-center justify-center gap-x-5 gap-y-3 border-t border-border/60 pt-6 lg:justify-start"
                        style="animation-delay: 240ms"
                        aria-label="Fontes ativas e confiabilidade"
                    >
                        <span class="text-xs font-medium text-muted-foreground">
                            Fontes ativas
                        </span>
                        <span
                            v-for="source in sources"
                            :key="source.slug"
                            class="inline-flex items-center gap-2 text-[13px] font-semibold tracking-tight text-foreground/85"
                            :title="`Confiabilidade ${source.reliability}%`"
                        >
                            {{ source.name }}
                            <span class="flex gap-0.5" aria-hidden="true">
                                <span
                                    v-for="bar in 5"
                                    :key="bar"
                                    class="h-2.5 w-0.75 rounded-full"
                                    :class="
                                        bar <= filled(source.reliability)
                                            ? 'bg-brand-blue'
                                            : 'bg-foreground/15'
                                    "
                                />
                            </span>
                            <span class="sr-only">
                                confiabilidade {{ source.reliability }}%
                            </span>
                        </span>
                    </div>
                </div>
            </div>

            <div
                v-if="layout === 'mobile'"
                class="relative mt-10 h-85 sm:h-105"
            >
                <ParticleKnot :light="light" />
            </div>
        </div>
    </section>
</template>
