<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { SearchX } from '@lucide/vue';
import { computed } from 'vue';
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
import type { DemoSearchedSource, DemoSearchResult } from '@/types/demo';

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
    type: string;
    typeLabel: string;
    birthDate: string | null;
    birthDateFormatted: string | null;
    results: DemoSearchResult[];
    searchedSources: DemoSearchedSource[];
}>();

const subtitle = computed(() => {
    const count = props.results.length;
    let text = `${count} ${count === 1 ? 'registro' : 'registros'} para “${props.query}” em ${props.typeLabel}`;

    if (props.birthDateFormatted) {
        text += `, com nascimento em ${props.birthDateFormatted}`;
    }

    return `${text}.`;
});

const sourcesSummary = computed(() => {
    const total = props.searchedSources.length;
    const withResults = props.searchedSources.filter(
        (source) => source.hits > 0,
    ).length;
    const unavailable = props.searchedSources.filter(
        (source) => source.status === 'error',
    ).length;

    const parts = [
        `${total} ${total === 1 ? 'base aceita' : 'bases aceitam'} esta busca`,
        `${withResults} com resultado`,
    ];

    if (unavailable > 0) {
        parts.push(
            `${unavailable} ${unavailable === 1 ? 'indisponível' : 'indisponíveis'}`,
        );
    }

    return `${parts.join(', ')}.`;
});
</script>

<template>
    <Head :title="`Busca: ${props.query}`" />

    <div class="space-y-0.5">
        <h1 class="text-xl font-semibold tracking-tight">
            Resultados da busca
        </h1>
        <p class="text-sm text-muted-foreground">{{ subtitle }}</p>
    </div>

    <Card class="max-w-3xl">
        <CardContent>
            <DemoSearchForm
                :initial-query="props.query"
                :initial-type="props.type"
                :initial-birth-date="props.birthDate"
            />
        </CardContent>
    </Card>

    <Card>
        <CardHeader>
            <CardTitle>Bases consultadas</CardTitle>
            <CardDescription>{{ sourcesSummary }}</CardDescription>
        </CardHeader>
        <CardContent>
            <ul
                v-if="props.searchedSources.length > 0"
                class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4"
            >
                <li
                    v-for="source in props.searchedSources"
                    :key="source.slug"
                    class="flex flex-col gap-1 rounded-lg border p-3"
                >
                    <div class="flex items-center justify-between gap-2">
                        <span class="text-sm font-medium">
                            {{ source.name }}
                        </span>
                        <Badge
                            v-if="source.status === 'error'"
                            variant="destructive"
                        >
                            Indisponível
                        </Badge>
                        <Badge
                            v-else
                            :variant="source.hits > 0 ? 'secondary' : 'outline'"
                            class="tabular-nums"
                        >
                            {{ source.hits }}
                            {{ source.hits === 1 ? 'registro' : 'registros' }}
                        </Badge>
                    </div>
                    <p class="text-xs text-muted-foreground">
                        {{ source.duration_ms }} ms
                    </p>
                </li>
            </ul>
            <p v-else class="text-sm text-muted-foreground">
                Nenhuma base ativa aceita esse tipo de busca.
            </p>
        </CardContent>
    </Card>

    <Card>
        <CardHeader>
            <CardTitle>Registros encontrados</CardTitle>
            <CardDescription>
                Em quais bases cada registro foi encontrado.
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
                        <TableHead>Bases</TableHead>
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
                            <div class="flex flex-wrap gap-1">
                                <Badge
                                    v-for="source in result.found_in"
                                    :key="source.slug"
                                    variant="secondary"
                                >
                                    {{ source.name }}
                                </Badge>
                            </div>
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
                <p
                    v-if="props.birthDateFormatted"
                    class="max-w-md text-sm text-muted-foreground"
                >
                    Ninguém com esse termo nasceu em
                    {{ props.birthDateFormatted }}. Confira a data ou busque sem
                    ela.
                </p>
                <p v-else class="max-w-md text-sm text-muted-foreground">
                    Confira o tipo escolhido. Nome aceita parte do nome; CPF (11
                    dígitos), CNPJ (14 dígitos), CNH, e-mail e telefone precisam
                    estar completos.
                </p>
            </div>
        </CardContent>
    </Card>
</template>
