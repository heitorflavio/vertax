<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import CommandPalette from '@/components/landing/CommandPalette.vue';
import CoverageMarquee from '@/components/landing/CoverageMarquee.vue';
import CtaSection from '@/components/landing/CtaSection.vue';
import FaqSection from '@/components/landing/FaqSection.vue';
import HeroSection from '@/components/landing/HeroSection.vue';
import LandingFooter from '@/components/landing/LandingFooter.vue';
import LandingHeader from '@/components/landing/LandingHeader.vue';
import ProductSection from '@/components/landing/ProductSection.vue';
import ResultSection from '@/components/landing/ResultSection.vue';
import SourcesSection from '@/components/landing/SourcesSection.vue';
import StatsBand from '@/components/landing/StatsBand.vue';
import UseCasesSection from '@/components/landing/UseCasesSection.vue';
import WhySection from '@/components/landing/WhySection.vue';
import { useLandingTheme } from '@/composables/useLandingTheme';
import { navigation } from '@/lib/landing/navigation';
import type { LandingPageProps } from '@/types/landing';

const props = defineProps<LandingPageProps>();

const { theme, restore, toggle } = useLandingTheme();
const light = computed(() => theme.value === 'light');
const searchOpen = ref(false);
const groups = computed(() => navigation(props.plans));

function syncDocument(): void {
    document.documentElement.dataset.landingTheme = theme.value;
}

onMounted(() => {
    restore();
    document.documentElement.classList.add('landing-html');
    syncDocument();
});

watch(theme, syncDocument);

onBeforeUnmount(() => {
    document.documentElement.classList.remove('landing-html');
    delete document.documentElement.dataset.landingTheme;
});
</script>

<template>
    <Head title="Consulta de dados multi-fonte">
        <meta
            name="description"
            content="Consulte nome, CPF, CNPJ, CNH, e-mail ou telefone e receba um registro único, mesclado de várias fontes, com score de confiança e a origem de cada campo."
        />
    </Head>

    <div
        class="landing relative min-h-screen overflow-x-clip"
        :data-theme="theme"
    >
        <a
            href="#conteudo"
            class="sr-only z-70 rounded-lg bg-primary px-4 py-2 text-sm font-medium text-primary-foreground focus:not-sr-only focus:fixed focus:top-4 focus:left-4"
        >
            Pular para o conteúdo
        </a>

        <LandingHeader
            :can-register="canRegister"
            :plans="plans"
            :theme="theme"
            @search="searchOpen = true"
            @toggle-theme="toggle"
        />

        <main id="conteudo" class="relative" tabindex="-1">
            <div
                aria-hidden="true"
                class="pointer-events-none absolute inset-0 z-10 mx-auto hidden max-w-7xl  lg:block"
            />
            <HeroSection
                :can-register="canRegister"
                :sources="sources"
                :light="light"
            />
            <CoverageMarquee :merged-fields="mergedFields" :modules="modules" />
            <ProductSection
                :can-register="canRegister"
                :sources="sources"
                :modules="modules"
                :trust-weights="trustWeights"
            />
            <StatsBand
                :search-types="searchTypes"
                :sources="sources"
                :merged-fields="mergedFields"
                :trust-weights="trustWeights"
                :light="light"
            />
            <UseCasesSection :can-register="canRegister" />
            <SourcesSection :sources="sources" :light="light" />
            <ResultSection />
            <WhySection :sources="sources" />
            <FaqSection
                :can-register="canRegister"
                :search-types="searchTypes"
                :trust-weights="trustWeights"
            />
            <CtaSection :can-register="canRegister" :plans="plans" />
        </main>

        <LandingFooter :can-register="canRegister" />

        <CommandPalette v-model:open="searchOpen" :groups="groups" />
    </div>
</template>
