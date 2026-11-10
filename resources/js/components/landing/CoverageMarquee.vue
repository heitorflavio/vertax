<script setup lang="ts">
import {
    AtSign,
    Calendar,
    CircleDot,
    Hash,
    IdCard,
    MapPin,
    Phone,
    User,
    type LucideIcon,
} from '@lucide/vue';
import { computed } from 'vue';
import { demoIcon } from '@/lib/demoIcons';
import type { LandingModule } from '@/types/landing';

const props = defineProps<{
    mergedFields: string[];
    modules: LandingModule[];
}>();

const fieldIcons: Record<string, LucideIcon> = {
    'Nome completo': User,
    'Nome da mãe': User,
    CPF: IdCard,
    CNH: IdCard,
    'CNPJ vinculado': Hash,
    'Data de nascimento': Calendar,
    'E-mail': AtSign,
    Telefone: Phone,
    Endereço: MapPin,
    Cidade: MapPin,
};

const items = computed(() => [
    ...props.mergedFields
        .filter((field) => field !== 'UF')
        .map((label) => ({ label, icon: fieldIcons[label] ?? CircleDot })),
    ...props.modules.map((module) => ({
        label: module.name,
        icon: demoIcon(module.icon),
    })),
]);
</script>

<template>
    <section
        class="overflow-hidden border-y border-border"
        aria-label="O que uma consulta reúne"
    >
        <div
            class="mx-auto flex max-w-7xl flex-col gap-4 px-4 py-8 sm:flex-row sm:items-center sm:gap-6 sm:px-6 lg:px-8"
        >
            <p
                class="shrink-0 text-sm font-medium text-muted-foreground sm:max-w-44"
            >
                Tudo isso em
                <span class="text-foreground">uma só consulta</span>:
            </p>
            <div class="relative min-w-0 flex-1">
                <ul class="sr-only">
                    <li v-for="item in items" :key="item.label">
                        {{ item.label }}
                    </li>
                </ul>
                <div
                    class="lp-fade-x w-full overflow-hidden"
                    aria-hidden="true"
                >
                    <div class="lp-marquee flex w-max" style="--duration: 56s">
                        <div
                            v-for="copy in 2"
                            :key="copy"
                            class="flex shrink-0 gap-3 pr-3"
                        >
                            <div
                                v-for="item in items"
                                :key="item.label"
                                class="flex h-10 shrink-0 items-center gap-2.5 px-6 text-lg font-semibold tracking-tight whitespace-nowrap text-muted-foreground select-none"
                            >
                                <component
                                    :is="item.icon"
                                    class="size-4.5 text-foreground/35"
                                />
                                {{ item.label }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</template>
