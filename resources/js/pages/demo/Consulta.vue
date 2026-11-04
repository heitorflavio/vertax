<script setup lang="ts">
import { Head, Link, router, setLayoutProps, usePage } from '@inertiajs/vue3';
import { Check, Copy, Eye, Lock, Search } from '@lucide/vue';
import { computed, ref } from 'vue';
import { toast } from 'vue-sonner';
import DemoPlanModal from '@/components/demo/DemoPlanModal.vue';
import TrustScore from '@/components/demo/TrustScore.vue';
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
import { Separator } from '@/components/ui/separator';
import { demoApi } from '@/lib/demoApi';
import { home } from '@/routes';
import demo from '@/routes/demo';
import type { BreadcrumbItem } from '@/types';
import type { DemoPlan, DemoRecordDetail } from '@/types/demo';

const props = defineProps<{
    record: DemoRecordDetail;
    plans: DemoPlan[];
}>();

setLayoutProps<{ breadcrumbs: BreadcrumbItem[] }>({
    breadcrumbs: [
        { title: 'Buscar', href: home() },
        {
            title: props.record.public_id,
            href: demo.show(props.record.public_id),
        },
    ],
});

const page = usePage();
const plan = computed(() => page.props.demo.plan);

const revealed = ref(false);
const modalOpen = ref(false);
const copied = ref(false);
const feedback = ref<'helpful' | 'not_helpful' | null>(null);
const sendingFeedback = ref(false);

const hiddenFields = computed(() => props.record.hidden ?? []);

const otherFields = computed(() => {
    const basic = props.record.basic_fields.map((field) => field.key);

    return props.record.fields.filter(
        (field) => !basic.includes(field.key) && field.value !== null,
    );
});

const copyText = computed(() => {
    const lines = [
        `ConsultaDEMO — ${props.record.public_id}`,
        '',
        ...props.record.fields
            .filter((field) => field.value !== null)
            .map((field) => `${field.label}: ${field.value}`),
    ];

    if (revealed.value && hiddenFields.value.length > 0) {
        lines.push('', 'Informações ocultas:');
        lines.push(
            ...hiddenFields.value.map(
                (field) => `${field.label}: ${field.value}`,
            ),
        );
    }

    lines.push(
        '',
        `Score de confiança: ${props.record.trust.score}/100 (${props.record.trust.label})`,
        `Fontes: ${props.record.sources_count}`,
        '',
        page.props.demo.copyNotice,
    );

    return lines.join('\n');
});

function reveal(): void {
    if (props.record.hidden_locked) {
        modalOpen.value = true;
        return;
    }

    revealed.value = true;
}

function onPlanActivated(): void {
    // "demo" é prop compartilhado: precisa entrar no reload para o status do
    // plano atualizar no layout e nesta página.
    router.reload({
        only: ['record', 'demo'],
        onSuccess: () => {
            revealed.value = true;
        },
    });
}

async function copyResult(): Promise<void> {
    try {
        await navigator.clipboard.writeText(copyText.value);
        copied.value = true;
        toast.success('Resultado copiado com o aviso de demonstração.');
        window.setTimeout(() => {
            copied.value = false;
        }, 2000);
    } catch {
        toast.error('Não foi possível copiar o resultado.');
    }
}

async function sendFeedback(helpful: boolean): Promise<void> {
    sendingFeedback.value = true;

    try {
        await demoApi.rate(props.record.public_id, helpful);
        feedback.value = helpful ? 'helpful' : 'not_helpful';
        toast.success('Avaliação registrada.');
    } catch (error) {
        toast.error(
            error instanceof Error
                ? error.message
                : 'Não foi possível registrar a avaliação.',
        );
    } finally {
        sendingFeedback.value = false;
    }
}
</script>

<template>
    <Head :title="props.record.public_id" />

    <div
        class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between"
    >
        <div class="space-y-0.5">
            <div class="flex flex-wrap items-center gap-2">
                <h1 class="text-xl font-semibold tracking-tight">
                    {{ props.record.full_name }}
                </h1>
                <Badge variant="outline" class="font-mono">
                    {{ props.record.public_id }}
                </Badge>
            </div>
            <p class="text-sm text-muted-foreground">
                Registro fictício mesclado de {{ props.record.sources_count }}
                fontes simuladas.
            </p>
        </div>
        <div class="flex gap-2">
            <Button variant="outline" @click="copyResult">
                <Check v-if="copied" />
                <Copy v-else />
                Copiar resultado
            </Button>
            <Button variant="outline" as-child>
                <Link :href="home()">
                    <Search />
                    Nova busca
                </Link>
            </Button>
        </div>
    </div>

    <div class="grid grid-cols-1 items-start gap-6 lg:grid-cols-3">
        <div class="grid gap-6 lg:col-span-2">
            <Card>
                <CardHeader>
                    <CardTitle>Dados básicos</CardTitle>
                    <CardDescription>
                        Nome, CPF e data de nascimento fictícios.
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <dl class="grid gap-4 sm:grid-cols-3">
                        <div
                            v-for="field in props.record.basic_fields"
                            :key="field.key"
                            class="grid gap-1"
                        >
                            <dt class="text-sm text-muted-foreground">
                                {{ field.label }}
                            </dt>
                            <dd class="text-sm font-medium">
                                {{ field.value ?? '—' }}
                            </dd>
                        </div>
                    </dl>
                    <template v-if="otherFields.length > 0">
                        <Separator class="my-6" />
                        <dl class="grid gap-4 sm:grid-cols-2">
                            <div
                                v-for="field in otherFields"
                                :key="field.key"
                                class="grid gap-1"
                            >
                                <dt class="text-sm text-muted-foreground">
                                    {{ field.label }}
                                </dt>
                                <dd class="text-sm break-words">
                                    {{ field.value }}
                                </dd>
                            </div>
                        </dl>
                    </template>
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle>Informações ocultas</CardTitle>
                    <CardDescription>
                        {{ props.record.hidden_fields_count }} campos
                        adicionais, liberados com um plano demo.
                    </CardDescription>
                    <CardAction>
                        <Badge
                            :variant="
                                props.record.hidden_locked
                                    ? 'secondary'
                                    : 'outline'
                            "
                        >
                            {{
                                props.record.hidden_locked
                                    ? 'Bloqueado'
                                    : 'Liberado'
                            }}
                        </Badge>
                    </CardAction>
                </CardHeader>
                <CardContent>
                    <dl v-if="revealed" class="grid gap-4 sm:grid-cols-2">
                        <div
                            v-for="field in hiddenFields"
                            :key="field.key"
                            class="grid gap-1"
                        >
                            <dt class="text-sm text-muted-foreground">
                                {{ field.label }}
                            </dt>
                            <dd class="text-sm break-words">
                                {{ field.value }}
                            </dd>
                        </div>
                    </dl>
                    <div
                        v-else
                        class="flex flex-col items-center gap-3 rounded-lg border border-dashed px-6 py-8 text-center"
                    >
                        <Lock class="size-5 text-muted-foreground" />
                        <p class="text-sm text-muted-foreground">
                            {{
                                props.record.hidden_locked
                                    ? 'Disponível com um plano demo ativo.'
                                    : 'Seu plano demo libera estes campos.'
                            }}
                        </p>
                        <Button @click="reveal">
                            <Eye />
                            Exibir informação
                        </Button>
                    </div>
                </CardContent>
            </Card>
        </div>

        <div class="grid gap-6">
            <Card>
                <CardHeader>
                    <CardTitle>Score de confiança</CardTitle>
                    <CardDescription>
                        Calculado após a mesclagem das fontes.
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <TrustScore :trust="props.record.trust" />
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle>Fontes</CardTitle>
                    <CardDescription>
                        Campos que cada fonte forneceu ao registro.
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <ul class="divide-y">
                        <li
                            v-for="source in props.record.sources"
                            :key="source.slug ?? source.name"
                            class="grid gap-1 py-3 first:pt-0 last:pb-0"
                        >
                            <div
                                class="flex items-center justify-between gap-2 text-sm"
                            >
                                <span class="font-medium">
                                    {{ source.name }}
                                </span>
                                <span
                                    class="text-muted-foreground tabular-nums"
                                >
                                    {{ source.reliability }}/100
                                </span>
                            </div>
                            <p class="text-sm text-muted-foreground">
                                {{
                                    source.contributed_fields.length > 0
                                        ? source.contributed_fields.join(', ')
                                        : 'Somente informações ocultas'
                                }}
                            </p>
                        </li>
                    </ul>
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle>Avaliar resultado</CardTitle>
                    <CardDescription>
                        O resultado pareceu confiável?
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <p v-if="feedback" class="text-sm text-muted-foreground">
                        Avaliado como
                        {{ feedback === 'helpful' ? 'útil' : 'não confiável' }}.
                    </p>
                    <div v-else class="grid grid-cols-2 gap-2">
                        <Button
                            variant="outline"
                            :disabled="sendingFeedback"
                            @click="sendFeedback(true)"
                        >
                            Útil
                        </Button>
                        <Button
                            variant="outline"
                            :disabled="sendingFeedback"
                            @click="sendFeedback(false)"
                        >
                            Não confiável
                        </Button>
                    </div>
                </CardContent>
            </Card>
        </div>
    </div>

    <DemoPlanModal
        v-model:open="modalOpen"
        :plans="props.plans"
        :default-plan-slug="page.props.demo.defaultPlanSlug"
        @activated="onPlanActivated"
    />
</template>
