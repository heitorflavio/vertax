<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { KeyRound, ShieldCheck, UsersRound } from '@lucide/vue';
import VertaxLogo from '@/components/landing/VertaxLogo.vue';
import { isAnchor } from '@/lib/landing/navigation';
import { login, register } from '@/routes';
import demo from '@/routes/demo';
import { request } from '@/routes/password';

const props = defineProps<{
    canRegister: boolean;
}>();

const page = usePage();
const year = new Date().getFullYear();

const columns = [
    {
        title: 'Produto',
        links: [
            { label: 'Consulta multi-fonte', href: '#produto' },
            { label: 'Score de confiança', href: '#produto' },
            { label: 'Módulos', href: demo.modules.url() },
            { label: 'Planos', href: demo.plans.url() },
        ],
    },
    {
        title: 'Plataforma',
        links: [
            { label: 'Casos de uso', href: '#casos-de-uso' },
            { label: 'Fontes integradas', href: '#fontes' },
            { label: 'Resultado estruturado', href: '#resultado' },
            { label: 'Perguntas frequentes', href: '#perguntas' },
        ],
    },
    {
        title: 'Conta',
        links: [
            { label: 'Entrar', href: login.url() },
            ...(props.canRegister
                ? [{ label: 'Criar conta', href: register.url() }]
                : []),
            { label: 'Esqueci minha senha', href: request.url() },
        ],
    },
];

const security = [
    { icon: KeyRound, label: 'Passkeys' },
    { icon: ShieldCheck, label: 'Verificação em 2 etapas' },
    { icon: UsersRound, label: 'Permissões por papel' },
];
</script>

<template>
    <footer
        class="relative overflow-hidden border-t border-border bg-background"
    >
        <div class="mx-auto max-w-7xl px-4 pt-14 pb-10 sm:px-6 lg:px-8">
            <div class="grid gap-12 lg:grid-cols-12">
                <div class="lg:col-span-5">
                    <VertaxLogo />
                    <p
                        class="mt-4 max-w-sm text-sm leading-relaxed text-muted-foreground"
                    >
                        Consulta de dados cadastrais com mesclagem de várias
                        fontes, score de confiança e a origem de cada campo.
                    </p>
                    <p class="mt-6 text-sm font-semibold text-foreground">
                        Acesso seguro
                    </p>
                    <ul class="mt-3 flex flex-wrap gap-2">
                        <li
                            v-for="item in security"
                            :key="item.label"
                            class="flex items-center gap-1.5 rounded-lg border border-border bg-card px-2.5 py-1.5 text-xs text-muted-foreground"
                        >
                            <component
                                :is="item.icon"
                                class="size-3.5 text-brand-blue"
                            />
                            {{ item.label }}
                        </li>
                    </ul>
                </div>

                <nav
                    class="grid grid-cols-2 gap-8 sm:grid-cols-3 lg:col-span-7"
                    aria-label="Rodapé"
                >
                    <div v-for="column in columns" :key="column.title">
                        <p class="text-sm font-semibold text-foreground">
                            {{ column.title }}
                        </p>
                        <ul class="mt-4 space-y-3">
                            <li v-for="link in column.links" :key="link.label">
                                <component
                                    :is="isAnchor(link.href) ? 'a' : Link"
                                    :href="link.href"
                                    class="text-sm text-muted-foreground transition-colors hover:text-foreground"
                                >
                                    {{ link.label }}
                                </component>
                            </li>
                        </ul>
                    </div>
                </nav>
            </div>

            <div
                class="mt-14 flex flex-col gap-4 border-t border-border pt-6 text-xs text-muted-foreground sm:flex-row sm:items-center sm:justify-between"
            >
                <p>© {{ year }} Vertax. Todos os direitos reservados.</p>
                <p class="flex items-center gap-2">
                    <span class="size-2 rounded-full bg-emerald-500" />
                    {{ page.props.demo.shortDisclaimer }} · ambiente de
                    demonstração
                </p>
            </div>
        </div>

        <p
            class="pointer-events-none -mb-[4vw] text-center text-[22vw] leading-none font-bold tracking-tighter text-foreground/[0.04] select-none"
            aria-hidden="true"
        >
            Vertax
        </p>
    </footer>
</template>
