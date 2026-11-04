<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { SearchX } from '@lucide/vue';
import DemoSearchForm from '@/components/demo/DemoSearchForm.vue';
import { Badge } from '@/components/ui/badge';
import {
    Card,
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
import { trustVariant } from '@/lib/demoFormat';
import { home } from '@/routes';
import demo from '@/routes/demo';
import type { DemoSearchResult } from '@/types/demo';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Buscar', href: home() },
            { title: 'Resultados', href: demo.search() },
        ],
    },
});

const props = defineProps<{
    query: string;
    type: string | null;
    typeLabel: string | null;
    results: DemoSearchResult[];
}>();
</script>

<template>
    <Head :title="`Busca: ${props.query}`" />

    <div class="space-y-0.5">
        <h1 class="text-xl font-semibold tracking-tight">
            Resultados da busca
        </h1>
        <p class="text-sm text-muted-foreground">
            {{ props.results.length }}
            {{ props.results.length === 1 ? 'registro' : 'registros' }} para “{{
                props.query
            }}”<template v-if="props.typeLabel">
                em {{ props.typeLabel }}</template
            >.
        </p>
    </div>

    <Card class="max-w-3xl">
        <CardContent>
            <DemoSearchForm
                :initial-query="props.query"
                :initial-type="props.type"
            />
        </CardContent>
    </Card>

    <Card>
        <CardHeader>
            <CardTitle>Registros encontrados</CardTitle>
            <CardDescription>
                A coluna "Encontrado por" mostra qual dado casou com a busca.
            </CardDescription>
        </CardHeader>
        <CardContent>
            <Table v-if="props.results.length > 0">
                <TableHeader>
                    <TableRow>
                        <TableHead>ID</TableHead>
                        <TableHead>Nome</TableHead>
                        <TableHead>CPF</TableHead>
                        <TableHead>Nascimento</TableHead>
                        <TableHead>Encontrado por</TableHead>
                        <TableHead class="text-right">Confiança</TableHead>
                    </TableRow>
                </TableHeader>
                <TableBody>
                    <TableRow
                        v-for="result in props.results"
                        :key="result.public_id"
                    >
                        <TableCell class="font-mono">
                            <Link
                                :href="demo.show(result.public_id)"
                                class="underline-offset-4 hover:underline"
                            >
                                {{ result.public_id }}
                            </Link>
                        </TableCell>
                        <TableCell>
                            <Link
                                :href="demo.show(result.public_id)"
                                class="underline-offset-4 hover:underline"
                            >
                                {{ result.full_name }}
                            </Link>
                        </TableCell>
                        <TableCell class="font-mono text-muted-foreground">
                            {{ result.cpf_masked }}
                        </TableCell>
                        <TableCell class="text-muted-foreground">
                            {{ result.birth_date_formatted ?? '—' }}
                        </TableCell>
                        <TableCell>
                            <Badge variant="outline">
                                {{ result.matched_by_label }}
                            </Badge>
                        </TableCell>
                        <TableCell class="text-right">
                            <Badge
                                :variant="trustVariant(result.trust_score)"
                                class="tabular-nums"
                            >
                                {{ result.trust_score }}
                            </Badge>
                        </TableCell>
                    </TableRow>
                </TableBody>
            </Table>
            <div
                v-else
                class="flex flex-col items-center gap-1 rounded-lg border border-dashed px-6 py-10 text-center"
            >
                <SearchX class="mb-2 size-5 text-muted-foreground" />
                <p class="text-sm font-medium">Nenhum registro encontrado</p>
                <p class="max-w-md text-sm text-muted-foreground">
                    Tente parte do nome, ou um CPF (11 dígitos), CNPJ (14
                    dígitos), CNH, e-mail ou telefone completos.
                </p>
            </div>
        </CardContent>
    </Card>
</template>
