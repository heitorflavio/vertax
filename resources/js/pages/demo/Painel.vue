<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { RefreshCw } from '@lucide/vue';
import { computed, ref } from 'vue';
import { toast } from 'vue-sonner';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Spinner } from '@/components/ui/spinner';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { demoApi } from '@/lib/demoApi';
import { formatDateTime, trustVariant } from '@/lib/demoFormat';
import demo from '@/routes/demo';
import type {
    DemoIngestionRunSummary,
    DemoPanelSource,
    DemoRecordSummary,
    DemoStats,
} from '@/types/demo';

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Painel', href: demo.panel() }],
    },
});

const props = defineProps<{
    records: DemoRecordSummary[];
    sources: DemoPanelSource[];
    stats: DemoStats;
    lastRun: DemoIngestionRunSummary | null;
}>();

const syncing = ref(false);

const statCards = computed(() => [
    { label: 'Registros', value: props.stats.records },
    { label: 'Consultas', value: props.stats.consultations },
    { label: 'Confiança média', value: props.stats.avg_trust },
    { label: 'Ativações de plano', value: props.stats.plan_activations ?? 0 },
    { label: 'Avaliações úteis', value: props.stats.helpful ?? 0 },
    { label: 'Avaliações negativas', value: props.stats.not_helpful ?? 0 },
]);

const runDetails = computed(() => {
    const run = props.lastRun;

    if (!run) {
        return [];
    }

    return [
        { label: 'Fontes', value: run.sources_synced },
        { label: 'Payloads', value: run.payloads_ingested },
        { label: 'Registros mesclados', value: run.records_merged },
        { label: 'Duração', value: `${run.duration_ms ?? 0} ms` },
        { label: 'Início', value: formatDateTime(run.started_at) },
        { label: 'Fim', value: formatDateTime(run.finished_at) },
    ];
});

async function sync(): Promise<void> {
    syncing.value = true;

    try {
        const response = await demoApi.sync();
        toast.success(response.message ?? 'Fontes sincronizadas.');
        router.reload();
    } catch (error) {
        toast.error(
            error instanceof Error
                ? error.message
                : 'Falha ao sincronizar as fontes.',
        );
    } finally {
        syncing.value = false;
    }
}
</script>

<template>
    <Head title="Painel" />

    <div
        class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between"
    >
        <div class="space-y-0.5">
            <h1 class="text-xl font-semibold tracking-tight">Painel</h1>
            <p class="text-sm text-muted-foreground">
                Registros do seed, fontes simuladas e última ingestão.
            </p>
        </div>
        <Button variant="outline" :disabled="syncing" @click="sync">
            <Spinner v-if="syncing" />
            <RefreshCw v-else />
            Sincronizar fontes
        </Button>
    </div>

    <div class="grid grid-cols-2 gap-4 md:grid-cols-3 xl:grid-cols-6">
        <Card v-for="stat in statCards" :key="stat.label" class="gap-2">
            <CardHeader>
                <CardDescription>{{ stat.label }}</CardDescription>
                <CardTitle class="text-2xl tabular-nums">
                    {{ stat.value }}
                </CardTitle>
            </CardHeader>
        </Card>
    </div>

    <Card>
        <CardHeader>
            <CardTitle>Registros</CardTitle>
            <CardDescription>
                Registros canônicos gerados pelo merge das fontes.
            </CardDescription>
        </CardHeader>
        <CardContent>
            <Table>
                <TableHeader>
                    <TableRow>
                        <TableHead>ID</TableHead>
                        <TableHead>Nome</TableHead>
                        <TableHead>CPF</TableHead>
                        <TableHead>Nascimento</TableHead>
                        <TableHead class="text-right">Fontes</TableHead>
                        <TableHead class="text-right">Consultas</TableHead>
                        <TableHead class="text-right">Confiança</TableHead>
                    </TableRow>
                </TableHeader>
                <TableBody>
                    <TableRow
                        v-for="record in props.records"
                        :key="record.public_id"
                    >
                        <TableCell class="font-mono">
                            <Link
                                :href="demo.show(record.public_id)"
                                class="underline-offset-4 hover:underline"
                            >
                                {{ record.public_id }}
                            </Link>
                        </TableCell>
                        <TableCell>{{ record.full_name }}</TableCell>
                        <TableCell class="font-mono text-muted-foreground">
                            {{ record.cpf_masked }}
                        </TableCell>
                        <TableCell class="text-muted-foreground">
                            {{ record.birth_date_formatted ?? '—' }}
                        </TableCell>
                        <TableCell class="text-right tabular-nums">
                            {{ record.sources_count }}
                        </TableCell>
                        <TableCell class="text-right tabular-nums">
                            {{ record.consultations_count }}
                        </TableCell>
                        <TableCell class="text-right">
                            <Badge
                                :variant="trustVariant(record.trust_score)"
                                class="tabular-nums"
                            >
                                {{ record.trust_score }} ·
                                {{ record.trust_label }}
                            </Badge>
                        </TableCell>
                    </TableRow>
                </TableBody>
            </Table>
        </CardContent>
    </Card>

    <div class="grid grid-cols-1 items-start gap-6 lg:grid-cols-3">
        <Card class="lg:col-span-2">
            <CardHeader>
                <CardTitle>Fontes</CardTitle>
                <CardDescription>
                    Provedores fictícios e o peso de cada um no merge.
                </CardDescription>
            </CardHeader>
            <CardContent>
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead>Fonte</TableHead>
                            <TableHead>Categoria</TableHead>
                            <TableHead class="text-right">
                                Confiabilidade
                            </TableHead>
                            <TableHead class="text-right">Payloads</TableHead>
                            <TableHead class="text-right">Status</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow
                            v-for="source in props.sources"
                            :key="source.slug"
                        >
                            <TableCell class="font-medium">
                                {{ source.name }}
                            </TableCell>
                            <TableCell class="text-muted-foreground">
                                {{ source.category }}
                            </TableCell>
                            <TableCell class="text-right tabular-nums">
                                {{ source.reliability }}
                            </TableCell>
                            <TableCell class="text-right tabular-nums">
                                {{ source.payloads }}
                            </TableCell>
                            <TableCell class="text-right">
                                <Badge
                                    :variant="
                                        source.is_active
                                            ? 'outline'
                                            : 'secondary'
                                    "
                                >
                                    {{ source.is_active ? 'Ativa' : 'Inativa' }}
                                </Badge>
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
            </CardContent>
        </Card>

        <Card>
            <CardHeader>
                <CardTitle>Última ingestão</CardTitle>
                <CardDescription>
                    {{
                        props.lastRun?.message ?? 'Nenhuma execução registrada.'
                    }}
                </CardDescription>
            </CardHeader>
            <CardContent v-if="props.lastRun">
                <dl class="grid grid-cols-2 gap-4">
                    <div
                        v-for="detail in runDetails"
                        :key="detail.label"
                        class="grid gap-1"
                    >
                        <dt class="text-sm text-muted-foreground">
                            {{ detail.label }}
                        </dt>
                        <dd class="text-sm font-medium tabular-nums">
                            {{ detail.value }}
                        </dd>
                    </div>
                </dl>
            </CardContent>
        </Card>
    </div>
</template>
