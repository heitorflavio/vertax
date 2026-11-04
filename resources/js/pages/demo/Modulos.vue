<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardAction,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { demoIcon } from '@/lib/demoIcons';
import demo from '@/routes/demo';
import type { DemoModule } from '@/types/demo';

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Módulos', href: demo.modules() }],
    },
});

const props = defineProps<{
    modules: DemoModule[];
}>();

const ALL = 'Todos';

const categories = computed(() => [
    ALL,
    ...new Set(props.modules.map((module) => module.category)),
]);

const activeCategory = ref(ALL);

const filteredModules = computed(() =>
    activeCategory.value === ALL
        ? props.modules
        : props.modules.filter(
              (module) => module.category === activeCategory.value,
          ),
);
</script>

<template>
    <Head title="Módulos" />

    <div class="space-y-0.5">
        <h1 class="text-xl font-semibold tracking-tight">Módulos</h1>
        <p class="text-sm text-muted-foreground">
            Módulos disponíveis, por categoria.
        </p>
    </div>

    <div class="flex flex-wrap gap-2">
        <Button
            v-for="category in categories"
            :key="category"
            size="sm"
            :variant="activeCategory === category ? 'secondary' : 'ghost'"
            @click="activeCategory = category"
        >
            {{ category }}
        </Button>
    </div>

    <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
        <Card v-for="module in filteredModules" :key="module.slug">
            <CardHeader>
                <component
                    :is="demoIcon(module.icon)"
                    class="mb-2 size-5 text-muted-foreground"
                />
                <CardTitle>{{ module.name }}</CardTitle>
                <CardDescription>{{ module.description }}</CardDescription>
                <CardAction>
                    <Badge variant="outline">{{ module.category }}</Badge>
                </CardAction>
            </CardHeader>
        </Card>
    </div>
</template>
