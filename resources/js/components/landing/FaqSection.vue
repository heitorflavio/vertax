<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { Plus } from '@lucide/vue';
import { computed, ref } from 'vue';
import GlowButton from '@/components/landing/GlowButton.vue';
import SectionHeading from '@/components/landing/SectionHeading.vue';
import { vReveal } from '@/lib/landing/reveal';
import { login, register } from '@/routes';

const props = defineProps<{
    canRegister: boolean;
    searchTypes: string[];
    trustWeights: Record<string, number>;
}>();

const page = usePage();
const open = ref<number | null>(0);

const percent = (key: string): number =>
    Math.round((props.trustWeights[key] ?? 0) * 100);

const questions = computed(() => [
    {
        q: 'Quais tipos de busca a Vertax aceita?',
        a: `${props.searchTypes.join(', ')}. Você escolhe o tipo na busca e só as fontes que aceitam aquele tipo são consultadas.`,
    },
    {
        q: 'Como o score de confiança é calculado?',
        a: `É uma nota de 0 a 100 que combina qualidade da fonte (${percent('source_quality')}%), completude (${percent('completeness')}%), consistência entre as fontes (${percent('consistency')}%) e atualidade (${percent('freshness')}%). A partir de 85 o nível é Alto; de 65 a 84, Moderado; abaixo disso, Baixo.`,
    },
    {
        q: 'O que acontece quando as fontes discordam?',
        a: 'Em cada campo vale o valor da fonte mais confiável. A divergência não some: ela reduz a consistência e, com ela, o score do registro.',
    },
    {
        q: 'O que são as informações ocultas?',
        a: 'Campos como score de crédito, renda presumida, vínculos societários e alerta de fraude. Eles aparecem quando há um plano ativo.',
    },
    {
        q: 'Quem pode consultar e quem administra?',
        a: 'O acesso é por papéis: clientes consultam, operadores também acompanham o painel e administradores gerenciam e sincronizam as fontes.',
    },
    {
        q: 'Os dados exibidos são reais?',
        a: page.props.demo.disclaimer,
    },
]);

function toggle(index: number): void {
    open.value = open.value === index ? null : index;
}
</script>

<template>
    <section id="perguntas" class="lp-section border-b border-border bg-band">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="grid gap-10 lg:grid-cols-[5fr_7fr] lg:gap-16">
                <div class="lg:sticky lg:top-28 lg:self-start">
                    <SectionHeading
                        eyebrow="Perguntas frequentes"
                        title="Tudo o que você precisa saber."
                    >
                        Como a busca funciona, de onde vem o score e o que cada
                        plano libera.
                    </SectionHeading>
                    <div v-reveal="160" class="mt-6">
                        <GlowButton
                            :href="canRegister ? register.url() : login.url()"
                        >
                            {{ canRegister ? 'Criar conta grátis' : 'Entrar' }}
                        </GlowButton>
                        <p class="mt-3 text-sm text-muted-foreground">
                            Já tem conta?
                            <Link
                                :href="login()"
                                class="text-foreground underline decoration-foreground/30 underline-offset-4 hover:decoration-foreground"
                                >Entre aqui</Link
                            >.
                        </p>
                    </div>
                </div>

                <div
                    v-reveal="80"
                    class="divide-y divide-border border-y border-border"
                >
                    <div v-for="(item, index) in questions" :key="item.q">
                        <h3>
                            <button
                                :id="`pergunta-${index}`"
                                type="button"
                                class="flex w-full items-center justify-between gap-6 py-5 text-left text-[15px] font-medium text-foreground transition-colors hover:text-foreground/80"
                                :aria-expanded="open === index"
                                :aria-controls="`resposta-${index}`"
                                @click="toggle(index)"
                            >
                                {{ item.q }}
                                <Plus
                                    class="size-4 shrink-0 text-muted-foreground transition-transform duration-300"
                                    :class="open === index ? 'rotate-45' : ''"
                                />
                            </button>
                        </h3>
                        <div
                            :id="`resposta-${index}`"
                            role="region"
                            :aria-labelledby="`pergunta-${index}`"
                            class="grid transition-[grid-template-rows] duration-300 ease-out"
                            :class="
                                open === index
                                    ? 'grid-rows-[1fr]'
                                    : 'grid-rows-[0fr]'
                            "
                        >
                            <div class="overflow-hidden">
                                <p
                                    class="max-w-2xl pb-5 text-sm leading-relaxed text-muted-foreground"
                                >
                                    {{ item.a }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</template>
