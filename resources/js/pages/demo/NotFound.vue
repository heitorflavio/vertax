<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardFooter,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { home } from '@/routes';
import demo from '@/routes/demo';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Buscar', href: home() },
            { title: 'Não encontrado', href: home() },
        ],
    },
});

defineProps<{
    publicId: string;
    availableIds: string[];
}>();
</script>

<template>
    <Head title="Registro não encontrado" />

    <Card class="w-full max-w-md self-center">
        <CardHeader>
            <CardTitle>Registro não encontrado</CardTitle>
            <CardDescription>
                O ID <span class="font-mono">{{ publicId }}</span> não existe no
                seed de demonstração (HTTP 404).
            </CardDescription>
        </CardHeader>
        <CardContent class="grid gap-2">
            <p class="text-sm text-muted-foreground">IDs disponíveis:</p>
            <div class="flex flex-wrap gap-2">
                <Button
                    v-for="id in availableIds"
                    :key="id"
                    variant="outline"
                    size="sm"
                    class="font-mono"
                    as-child
                >
                    <Link :href="demo.show(id)">{{ id }}</Link>
                </Button>
            </div>
        </CardContent>
        <CardFooter>
            <Button variant="outline" class="w-full" as-child>
                <Link :href="home()">Voltar para a busca</Link>
            </Button>
        </CardFooter>
    </Card>
</template>
