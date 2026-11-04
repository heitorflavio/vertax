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

// "all" detecta o tipo pelo termo; os demais restringem a busca a um campo.
// Os valores batem com DemoConfig::SEARCH_TYPE_LABELS no backend.
const searchTypes = [
    {
        value: 'all',
        label: 'Todos',
        placeholder: 'Nome, CPF, CNPJ, CNH, e-mail, telefone ou ID',
        inputmode: 'text',
    },
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
    { value: 'id', label: 'ID', placeholder: 'REG-0000', inputmode: 'text' },
] as const;

const props = withDefaults(
    defineProps<{
        initialQuery?: string;
        initialType?: string | null;
    }>(),
    {
        initialQuery: '',
        initialType: null,
    },
);

const query = ref(props.initialQuery);
const type = ref<string>(
    searchTypes.some((option) => option.value === props.initialType)
        ? (props.initialType ?? 'all')
        : 'all',
);

const current = computed(
    () =>
        searchTypes.find((option) => option.value === type.value) ??
        searchTypes[0],
);

function search(): void {
    const q = query.value.trim();

    if (q.length < 2) {
        toast.error('Digite pelo menos 2 caracteres para buscar.');
        return;
    }

    router.visit(
        demo.search({
            query: type.value === 'all' ? { q } : { q, tipo: type.value },
        }),
    );
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
            <Button type="submit">
                <Search />
                Buscar
            </Button>
        </div>
    </form>
</template>
