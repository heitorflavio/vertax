<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ref } from 'vue';
import { toast } from 'vue-sonner';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import { RadioGroup, RadioGroupItem } from '@/components/ui/radio-group';
import { Spinner } from '@/components/ui/spinner';
import { demoApi } from '@/lib/demoApi';
import demo from '@/routes/demo';
import type { DemoPlan } from '@/types/demo';

const props = defineProps<{
    plans: DemoPlan[];
    defaultPlanSlug: string;
}>();

const open = defineModel<boolean>('open', { required: true });

const emit = defineEmits<{
    activated: [slug: string];
}>();

const selected = ref(props.defaultPlanSlug || props.plans[0]?.slug || '');
const loading = ref(false);

async function activate(): Promise<void> {
    loading.value = true;

    try {
        const response = await demoApi.activatePlan(selected.value);
        toast.success(response.message ?? 'Plano ativado.');
        open.value = false;
        emit('activated', selected.value);
    } catch (error) {
        toast.error(
            error instanceof Error
                ? error.message
                : 'Não foi possível ativar o plano.',
        );
    } finally {
        loading.value = false;
    }
}
</script>

<template>
    <Dialog v-model:open="open">
        <DialogContent class="sm:max-w-md">
            <DialogHeader>
                <DialogTitle>Ativar plano</DialogTitle>
                <DialogDescription>
                    As informações ocultas são liberadas com um plano ativo. A
                    ativação vale apenas para esta sessão.
                </DialogDescription>
            </DialogHeader>

            <RadioGroup v-model="selected">
                <Label
                    v-for="plan in props.plans"
                    :key="plan.slug"
                    :for="`plan-${plan.slug}`"
                    class="flex cursor-pointer items-start gap-3 rounded-md border p-4 font-normal has-[[data-state=checked]]:border-primary"
                >
                    <RadioGroupItem
                        :id="`plan-${plan.slug}`"
                        :value="plan.slug"
                        class="mt-0.5"
                    />
                    <div class="grid flex-1 gap-1">
                        <div
                            class="flex items-center justify-between gap-2 text-sm font-medium"
                        >
                            {{ plan.name }}
                            <span class="font-normal text-muted-foreground">
                                {{ plan.price_label }}
                            </span>
                        </div>
                        <p class="text-sm text-muted-foreground">
                            {{ plan.description }}
                        </p>
                    </div>
                </Label>
            </RadioGroup>

            <DialogFooter class="gap-2">
                <Button variant="outline" as-child>
                    <Link :href="demo.plans()">Comparar planos</Link>
                </Button>
                <Button :disabled="loading || !selected" @click="activate">
                    <Spinner v-if="loading" />
                    Ativar plano
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
