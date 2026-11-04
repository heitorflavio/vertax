<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { Search } from '@lucide/vue';
import { ref } from 'vue';
import { toast } from 'vue-sonner';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import demo from '@/routes/demo';

const props = withDefaults(
    defineProps<{
        initialQuery?: string;
    }>(),
    {
        initialQuery: '',
    },
);

const query = ref(props.initialQuery);

function search(): void {
    const q = query.value.trim();

    if (q.length < 2) {
        toast.error('Digite pelo menos 2 caracteres para buscar.');
        return;
    }

    router.visit(demo.search({ query: { q } }));
}
</script>

<template>
    <form class="grid gap-2" @submit.prevent="search()">
        <Label for="demo-search"
            >Nome, CPF, CNPJ, CNH, e-mail ou telefone</Label
        >
        <div class="flex gap-2">
            <Input
                id="demo-search"
                v-model="query"
                type="search"
                placeholder="Aurora, 301.482.115-10 ou REG-0001"
                autocomplete="off"
            />
            <Button type="submit">
                <Search />
                Buscar
            </Button>
        </div>
    </form>
</template>
