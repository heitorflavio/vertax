<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { History, KeyRound, UsersRound } from '@lucide/vue';
import { computed } from 'vue';
import GlowButton from '@/components/landing/GlowButton.vue';
import { vReveal } from '@/lib/landing/reveal';
import { login, register } from '@/routes';
import demo from '@/routes/demo';
import type { LandingPlan } from '@/types/landing';

const props = defineProps<{
    canRegister: boolean;
    plans: LandingPlan[];
}>();

const pitch = computed(() => {
    const [first, ...others] = [...props.plans].sort(
        (a, b) => a.price_cents - b.price_cents,
    );

    if (!first) {
        return 'Crie sua conta e faça a primeira consulta em minutos.';
    }

    const start =
        first.price_cents === 0
            ? `comece pelo plano ${first.name}, sem custo`
            : `comece pelo plano ${first.name}`;
    const upgrade = others.length
        ? ` Ative o ${others.map((plan) => plan.name).join(' ou o ')} quando precisar de mais.`
        : '';

    return `Crie sua conta e ${start}.${upgrade}`;
});

const benefits = [
    { icon: KeyRound, label: 'Passkeys e 2FA' },
    { icon: UsersRound, label: 'Acesso por papéis' },
    { icon: History, label: 'Histórico de consultas' },
];
</script>

<template>
    <section class="lp-cta-wash lp-section" aria-labelledby="cta-titulo">
        <div class="mx-auto max-w-7xl px-4 text-center sm:px-6 lg:px-8">
            <h2
                id="cta-titulo"
                v-reveal
                class="text-3xl font-bold tracking-tight text-balance text-foreground sm:text-4xl"
            >
                Pronto para consultar com confiança?
            </h2>
            <p
                v-reveal="80"
                class="mx-auto mt-4 max-w-xl text-base text-muted-foreground"
            >
                {{ pitch }}
            </p>
            <div
                v-reveal="140"
                class="mt-8 flex flex-wrap items-center justify-center gap-3"
            >
                <GlowButton :href="canRegister ? register.url() : login.url()">
                    {{ canRegister ? 'Criar conta grátis' : 'Entrar' }}
                </GlowButton>
                <Link
                    :href="demo.plans()"
                    class="inline-flex h-11 items-center gap-2 rounded-lg border border-input bg-input/30 px-6 text-sm font-medium text-foreground transition-colors hover:bg-input/60"
                >
                    Ver planos
                </Link>
            </div>
            <ul
                v-reveal="200"
                class="mt-8 flex flex-wrap justify-center gap-x-6 gap-y-2"
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
    </section>
</template>
