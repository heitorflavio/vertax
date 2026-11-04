<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { Info } from '@lucide/vue';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';

const page = usePage();
</script>

<template>
    <div class="flex h-full flex-1 flex-col gap-6 p-4 md:p-6">
        <!-- <Alert>
            <Info />
            <AlertTitle>Ambiente de demonstração</AlertTitle>
            <AlertDescription>
                {{ page.props.demo.disclaimer }}
            </AlertDescription>
        </Alert> -->

        <slot />
    </div>
</template>
