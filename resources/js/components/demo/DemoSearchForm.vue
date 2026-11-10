<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { Search } from '@lucide/vue';
import { computed, ref } from 'vue';
import { toast } from 'vue-sonner';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import demo from '@/routes/demo';

// O tipo é obrigatório: a busca vai às fontes que aceitam esse tipo.
// Os valores batem com DemoConfig::SEARCH_TYPE_LABELS no backend.
const searchTypes = [
    {
        value: 'name',
        label: 'Nome',
        placeholder: 'Nome completo ou parte dele',
        inputmode: 'text',
    },
    {
        value: 'cpf',
        label: 'CPF',
        placeholder: '000.000.000-00',
        inputmode: 'numeric',
    },
    {
        value: 'cnpj',
        label: 'CNPJ',
        placeholder: '00.000.000/0000-00',
        inputmode: 'numeric',
    },
    {
        value: 'cnh',
        label: 'CNH',
        placeholder: '00000000000',
        inputmode: 'numeric',
    },
    {
        value: 'email',
        label: 'E-mail',
        placeholder: 'nome@dominio.com',
        inputmode: 'email',
    },
    {
        value: 'phone',
        label: 'Telefone',
        placeholder: '(00) 00000-0000',
        inputmode: 'tel',
    },
] as const;

const props = withDefaults(
    defineProps<{
        initialQuery?: string;
        initialType?: string | null;
        initialBirthDate?: string | null;
    }>(),
    {
        initialQuery: '',
        initialType: null,
        initialBirthDate: null,
    },
);

const query = ref(props.initialQuery);
const type = ref<string>(
    searchTypes.find((option) => option.value === props.initialType)?.value ??
        'name',
);

const current = computed(
    () =>
        searchTypes.find((option) => option.value === type.value) ??
        searchTypes[0],
);

const birthDate = ref(props.initialBirthDate ?? '');
// A data de nascimento é o filtro da busca por nome (busca composta).
const acceptsBirthDate = computed(() => type.value === 'name');

// Data local de hoje (AAAA-MM-DD), limite do campo de nascimento.
const today = (() => {
    const now = new Date();

    return [
        now.getFullYear(),
        String(now.getMonth() + 1).padStart(2, '0'),
        String(now.getDate()).padStart(2, '0'),
    ].join('-');
})();

function search(): void {
    const q = query.value.trim();

    if (q.length < 2) {
        toast.error('Digite pelo menos 2 caracteres para buscar.');
        return;
    }

    const params: Record<string, string> = { q, tipo: type.value };

    if (acceptsBirthDate.value && birthDate.value) {
        params.nascimento = birthDate.value;
    }

    router.visit(demo.search({ query: params }));
}
</script>

<template>
    <form class="grid gap-2" @submit.prevent="search()">
        <Label for="demo-search-type">Buscar por</Label>
        <div class="flex flex-col gap-2 sm:flex-row">
            <Select v-model="type">
                <SelectTrigger id="demo-search-type" class="w-full sm:w-36">
                    <SelectValue />
                </SelectTrigger>
                <SelectContent>
                    <SelectItem
                        v-for="option in searchTypes"
                        :key="option.value"
                        :value="option.value"
                    >
                        {{ option.label }}
                    </SelectItem>
                </SelectContent>
            </Select>
            <Input
                v-model="query"
                type="search"
                aria-label="Termo de busca"
                :inputmode="current.inputmode"
                :placeholder="current.placeholder"
                autocomplete="off"
                class="flex-1"
            />
            <Input
                v-if="acceptsBirthDate"
                v-model="birthDate"
                type="date"
                :max="today"
                aria-label="Data de nascimento (opcional)"
                title="Data de nascimento (opcional)"
                class="w-full sm:w-44"
            />
            <Button type="submit">
                <Search />
                Buscar
            </Button>
        </div>
        <p v-if="acceptsBirthDate" class="text-xs text-muted-foreground">
            Data de nascimento é opcional: junto com o nome, reduz os
            resultados.
        </p>
    </form>
</template>
