<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { Search } from '@lucide/vue';
import { ref } from 'vue';
import { toast } from 'vue-sonner';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import demo from '@/routes/demo';
import type { DemoSearchExample } from '@/types/demo';

const props = withDefaults(
    defineProps<{
        initialQuery?: string;
        examples?: DemoSearchExample[];
    }>(),
    {
        initialQuery: '',
        examples: () => [],
    },
);

const query = ref(props.initialQuery);

function search(value: string = query.value): void {
    const q = value.trim();

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
    <div
        v-if="props.examples.length > 0"
        class="mt-3 flex flex-wrap items-center gap-2 text-sm text-muted-foreground"
    >
        Sugestões:
        <Button
            v-for="example in props.examples"
            :key="example.type"
            variant="outline"
            size="sm"
            @click="search(example.value)"
        >
            <span class="text-muted-foreground">{{ example.type }}</span>
            {{ example.value }}
        </Button>
    </div>
</template>
