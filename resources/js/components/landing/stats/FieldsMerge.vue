<script setup lang="ts">
import { Check } from '@lucide/vue';

const props = defineProps<{
    fields: string[];
}>();

// Quem costuma fornecer cada campo, pela descrição das fontes da demo.
const origin: Record<string, string> = {
    'Nome completo': 'Cadastro Civil',
    CPF: 'Cadastro Civil',
    'Data de nascimento': 'Cadastro Civil',
    CNH: 'Cadastro Civil',
    'Nome da mãe': 'Cadastro Civil',
    'CNPJ vinculado': 'Crédito',
    'E-mail': 'Contatos',
    Telefone: 'Contatos',
    Endereço: 'Localização',
    Cidade: 'Contatos',
    UF: 'Contatos',
};

const step = 0.6;
const cycle = `${props.fields.length * step}s`;
</script>

<template>
    <div
        class="flex h-full items-center gap-10 px-8 lg:px-12"
        aria-hidden="true"
    >
        <div class="grid flex-1 grid-cols-3 gap-2 lg:grid-cols-4">
            <div
                v-for="(field, index) in fields"
                :key="field"
                class="lp-light-up rounded-lg border border-border px-3 py-2"
                :style="{
                    animationDelay: `${index * step}s`,
                    '--duration': cycle,
                }"
            >
                <span class="block truncate text-[13px] text-foreground/85">
                    {{ field }}
                </span>
                <span class="block truncate text-[11px] text-muted-foreground">
                    {{ origin[field] ?? 'Fonte' }}
                </span>
            </div>
        </div>
        <div
            class="hidden w-64 shrink-0 rounded-2xl border border-border bg-popover p-5 shadow-xl shadow-black/20 lg:block"
        >
            <p class="text-sm font-semibold text-foreground">Registro único</p>
            <p class="lp-mono mt-0.5 text-xs text-muted-foreground">REG-0001</p>
            <ul class="mt-4 space-y-2 text-xs text-muted-foreground">
                <li
                    v-for="line in [
                        `${fields.length} campos canônicos`,
                        'Valores normalizados',
                        'Sem duplicidade entre fontes',
                    ]"
                    :key="line"
                    class="flex items-center gap-2"
                >
                    <Check class="size-3.5 text-emerald-500" />
                    {{ line }}
                </li>
            </ul>
        </div>
    </div>
</template>
