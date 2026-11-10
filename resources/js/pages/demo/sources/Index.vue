<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { Pencil, Plus, Radio, Trash2 } from '@lucide/vue';
import { ref } from 'vue';
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
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import type { DemoSourceListItem } from '@/types/demo';

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Fontes', href: '/fontes' }],
    },
});

defineProps<{
    sources: DemoSourceListItem[];
}>();

const deleteSlug = ref<string | null>(null);
const deleteName = ref('');

function confirmDelete(source: DemoSourceListItem): void {
    deleteSlug.value = source.slug;
    deleteName.value = source.name;
}

function destroy(): void {
    if (!deleteSlug.value) return;

    router.delete(`/fontes/${deleteSlug.value}`, {
        onSuccess: () => {
            toast.success(`Fonte "${deleteName.value}" removida.`);
            deleteSlug.value = null;
        },
    });
}
</script>

<template>
    <Head title="Fontes" />

    <div
        class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between"
    >
        <div class="space-y-0.5">
            <h1 class="text-xl font-semibold tracking-tight">Fontes</h1>
            <p class="text-sm text-muted-foreground">
                Gerencie as fontes de dados e configure integrações com APIs
                externas.
            </p>
        </div>
        <Button as-child>
            <Link href="/fontes/criar">
                <Plus />
                Nova fonte
            </Link>
        </Button>
    </div>

    <Card>
        <CardHeader>
            <CardTitle>Fontes configuradas</CardTitle>
            <CardDescription>
                {{ sources.length }} fonte(s) registrada(s).
            </CardDescription>
        </CardHeader>
        <CardContent>
            <Table>
                <TableHeader>
                    <TableRow>
                        <TableHead>Fonte</TableHead>
                        <TableHead>Categoria</TableHead>
                        <TableHead>Busca por</TableHead>
                        <TableHead class="text-right">Confiabilidade</TableHead>
                        <TableHead class="text-right">Payloads</TableHead>
                        <TableHead class="text-center">Tipo</TableHead>
                        <TableHead class="text-center">Status</TableHead>
                        <TableHead class="text-right">Ações</TableHead>
                    </TableRow>
                </TableHeader>
                <TableBody>
                    <TableRow v-for="source in sources" :key="source.slug">
                        <TableCell>
                            <div>
                                <span class="font-medium">{{
                                    source.name
                                }}</span>
                                <p
                                    v-if="source.description"
                                    class="text-xs text-muted-foreground"
                                >
                                    {{ source.description }}
                                </p>
                            </div>
                        </TableCell>
                        <TableCell class="text-muted-foreground">
                            {{ source.category }}
                        </TableCell>
                        <TableCell>
                            <div class="flex flex-wrap gap-1">
                                <Badge
                                    v-for="type in source.search_types"
                                    :key="type"
                                    variant="outline"
                                >
                                    {{ type }}
                                </Badge>
                            </div>
                        </TableCell>
                        <TableCell class="text-right tabular-nums">
                            {{ source.reliability }}
                        </TableCell>
                        <TableCell class="text-right tabular-nums">
                            {{ source.payloads }}
                        </TableCell>
                        <TableCell class="text-center">
                            <Badge
                                :variant="
                                    source.has_api ? 'default' : 'secondary'
                                "
                            >
                                <Radio
                                    v-if="source.has_api"
                                    class="mr-1 size-3"
                                />
                                {{ source.has_api ? 'API' : 'Seed' }}
                            </Badge>
                        </TableCell>
                        <TableCell class="text-center">
                            <Badge
                                :variant="
                                    source.is_active ? 'outline' : 'secondary'
                                "
                            >
                                {{ source.is_active ? 'Ativa' : 'Inativa' }}
                            </Badge>
                        </TableCell>
                        <TableCell class="text-right">
                            <div class="flex justify-end gap-1">
                                <Button
                                    variant="ghost"
                                    size="icon"
                                    as-child
                                >
                                    <Link
                                        :href="`/fontes/${source.slug}/editar`"
                                    >
                                        <Pencil class="size-4" />
                                    </Link>
                                </Button>
                                <Button
                                    variant="ghost"
                                    size="icon"
                                    @click="confirmDelete(source)"
                                >
                                    <Trash2 class="size-4 text-destructive" />
                                </Button>
                            </div>
                        </TableCell>
                    </TableRow>
                    <TableRow v-if="sources.length === 0">
                        <TableCell colspan="8" class="text-center py-8 text-muted-foreground">
                            Nenhuma fonte cadastrada.
                        </TableCell>
                    </TableRow>
                </TableBody>
            </Table>
        </CardContent>
    </Card>

    <Dialog
        :open="deleteSlug !== null"
        @update:open="(open: boolean) => !open && (deleteSlug = null)"
    >
        <DialogContent>
            <DialogHeader>
                <DialogTitle>Remover fonte</DialogTitle>
                <DialogDescription>
                    Tem certeza que deseja remover "{{ deleteName }}"? Todos os
                    payloads associados serão excluídos.
                </DialogDescription>
            </DialogHeader>
            <DialogFooter>
                <Button variant="outline" @click="deleteSlug = null">
                    Cancelar
                </Button>
                <Button variant="destructive" @click="destroy">
                    Remover
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
