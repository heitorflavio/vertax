<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { History } from '@lucide/vue';
import DemoSearchForm from '@/components/demo/DemoSearchForm.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardAction,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { demoIcon } from '@/lib/demoIcons';
import { formatRelative, trustVariant } from '@/lib/demoFormat';
import { home } from '@/routes';
import demo from '@/routes/demo';
import type {
    DemoModule,
    DemoRecentSearch,
    DemoSearchExample,
} from '@/types/demo';

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Buscar', href: home() }],
    },
});

const props = defineProps<{
    modules: DemoModule[];
    recentSearches: DemoRecentSearch[];
    searchExamples: DemoSearchExample[];
    initialQuery: string;
}>();
</script>

<template>
    <Head title="Buscar" />

    <div class="space-y-0.5">
        <h1 class="text-xl font-semibold tracking-tight">Buscar registro</h1>
        <p class="text-sm text-muted-foreground">
            Pesquise por nome, CPF, CNPJ, CNH, e-mail, telefone ou ID do
            registro.
        </p>
    </div>

    <Card class="max-w-3xl">
        <CardContent>
            <DemoSearchForm
                :initial-query="props.initialQuery"
                :examples="props.searchExamples"
            />
        </CardContent>
    </Card>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-5">
        <Card class="lg:col-span-3">
            <CardHeader>
                <CardTitle>Últimas buscas</CardTitle>
                <CardDescription>
                    Registros consultados nesta sessão.
                </CardDescription>
                <CardAction>
                    <Button variant="link" size="sm" as-child>
                        <Link :href="demo.panel()">Ver painel</Link>
                    </Button>
                </CardAction>
            </CardHeader>
            <CardContent>
                <Table v-if="props.recentSearches.length > 0">
                    <TableHeader>
                        <TableRow>
                            <TableHead>ID</TableHead>
                            <TableHead>Nome</TableHead>
                            <TableHead>Quando</TableHead>
                            <TableHead class="text-right">Confiança</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow
                            v-for="item in props.recentSearches"
                            :key="item.public_id"
                        >
                            <TableCell class="font-mono">
                                <Link
                                    :href="demo.show(item.public_id)"
                                    class="underline-offset-4 hover:underline"
                                >
                                    {{ item.public_id }}
                                </Link>
                            </TableCell>
                            <TableCell>{{ item.full_name }}</TableCell>
                            <TableCell class="text-muted-foreground">
                                {{ formatRelative(item.searched_at) }}
                            </TableCell>
                            <TableCell class="text-right">
                                <Badge
                                    :variant="trustVariant(item.trust_score)"
                                    class="tabular-nums"
                                >
                                    {{ item.trust_score }}
                                </Badge>
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
                <div
                    v-else
                    class="flex flex-col items-center gap-1 rounded-lg border border-dashed px-6 py-10 text-center"
                >
                    <History class="mb-2 size-5 text-muted-foreground" />
                    <p class="text-sm font-medium">Nenhuma busca ainda</p>
                    <p class="text-sm text-muted-foreground">
                        Os registros que você consultar aparecem aqui.
                    </p>
                </div>
            </CardContent>
        </Card>

        <Card class="lg:col-span-2">
            <CardHeader>
                <CardTitle>Módulos</CardTitle>
                <CardDescription
                    >Catálogo de módulos de exemplo.</CardDescription
                >
                <CardAction>
                    <Button variant="link" size="sm" as-child>
                        <Link :href="demo.modules()">Ver todos</Link>
                    </Button>
                </CardAction>
            </CardHeader>
            <CardContent>
                <ul class="divide-y">
                    <li
                        v-for="module in props.modules"
                        :key="module.slug"
                        class="flex items-center gap-3 py-2.5 first:pt-0 last:pb-0"
                    >
                        <component
                            :is="demoIcon(module.icon)"
                            class="size-4 shrink-0 text-muted-foreground"
                        />
                        <span class="flex-1 truncate text-sm">
                            {{ module.name }}
                        </span>
                        <Badge variant="outline">{{ module.badge }}</Badge>
                    </li>
                </ul>
            </CardContent>
        </Card>
    </div>
</template>
