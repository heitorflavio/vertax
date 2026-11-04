<script setup lang="ts">
import { Head, router, usePage } from '@inertiajs/vue3';
import { Check, CircleCheck } from '@lucide/vue';
import { computed, ref } from 'vue';
import { toast } from 'vue-sonner';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardAction,
    CardContent,
    CardDescription,
    CardFooter,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Spinner } from '@/components/ui/spinner';
import { demoApi } from '@/lib/demoApi';
import demo from '@/routes/demo';
import type { DemoPlan } from '@/types/demo';

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Planos', href: demo.plans() }],
    },
});

const props = defineProps<{
    plans: DemoPlan[];
}>();

const page = usePage();
const plan = computed(() => page.props.demo.plan);

const loadingSlug = ref<string | null>(null);

const expiresAt = computed(() =>
    plan.value.expires_at
        ? new Date(plan.value.expires_at).toLocaleTimeString('pt-BR', {
              hour: '2-digit',
              minute: '2-digit',
          })
        : null,
);

async function activate(slug: string): Promise<void> {
    loadingSlug.value = slug;

    try {
        const response = await demoApi.activatePlan(slug);
        toast.success(response.message ?? 'Plano ativado.');
        router.reload();
    } catch (error) {
        toast.error(
            error instanceof Error
                ? error.message
                : 'Não foi possível ativar o plano.',
        );
    } finally {
        loadingSlug.value = null;
    }
}
</script>

<template>
    <Head title="Planos" />

    <div class="space-y-0.5">
        <h1 class="text-xl font-semibold tracking-tight">Planos</h1>
        <p class="text-sm text-muted-foreground">
            Planos que liberam as informações ocultas. A ativação não exige
            pagamento.
        </p>
    </div>

    <Alert v-if="plan.active">
        <CircleCheck />
        <AlertTitle>{{ plan.name }} ativo</AlertTitle>
        <AlertDescription>
            Válido nesta sessão até {{ expiresAt }}.
        </AlertDescription>
    </Alert>

    <div class="grid gap-6 md:grid-cols-3">
        <Card v-for="item in props.plans" :key="item.slug">
            <CardHeader>
                <CardTitle>{{ item.name }}</CardTitle>
                <CardDescription>{{ item.description }}</CardDescription>
                <CardAction v-if="item.recommended">
                    <Badge variant="secondary">Recomendado</Badge>
                </CardAction>
            </CardHeader>
            <CardContent class="flex-1 space-y-4">
                <div>
                    <p class="text-2xl font-semibold tracking-tight">
                        {{ item.price_label }}
                    </p>
                    <p class="text-sm text-muted-foreground">
                        Sessão de {{ item.duration_minutes }} minutos
                    </p>
                </div>
                <ul class="space-y-2 text-sm">
                    <li
                        v-for="feature in item.features"
                        :key="feature"
                        class="flex gap-2"
                    >
                        <Check
                            class="mt-0.5 size-4 shrink-0 text-muted-foreground"
                        />
                        {{ feature }}
                    </li>
                </ul>
            </CardContent>
            <CardFooter>
                <Button
                    class="w-full"
                    :variant="item.recommended ? 'default' : 'outline'"
                    :disabled="
                        loadingSlug !== null ||
                        (plan.active && plan.slug === item.slug)
                    "
                    @click="activate(item.slug)"
                >
                    <Spinner v-if="loadingSlug === item.slug" />
                    {{
                        plan.active && plan.slug === item.slug
                            ? 'Plano atual'
                            : 'Ativar plano'
                    }}
                </Button>
            </CardFooter>
        </Card>
    </div>
</template>
