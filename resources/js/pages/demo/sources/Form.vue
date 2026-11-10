<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { Plus, Trash2 } from '@lucide/vue';
import { computed, reactive, ref } from 'vue';
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
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import type { DemoSourceForm } from '@/types/demo';

const props = defineProps<{
    source: DemoSourceForm | null;
    searchTypeOptions: Record<string, string>;
    fieldOptions: Record<string, string>;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Fontes', href: '/fontes' },
            { title: 'Formulário' },
        ],
    },
});

const isEdit = computed(() => props.source !== null);
const title = computed(() =>
    isEdit.value ? `Editar: ${props.source!.name}` : 'Nova fonte',
);

const form = reactive<DemoSourceForm>({
    slug: props.source?.slug ?? '',
    name: props.source?.name ?? '',
    category: props.source?.category ?? '',
    description: props.source?.description ?? '',
    reliability: props.source?.reliability ?? 70,
    search_types: props.source?.search_types ?? [],
    is_active: props.source?.is_active ?? true,
    notes: props.source?.notes ?? '',
    api_config: props.source?.api_config ?? null,
});

const enableApi = ref(form.api_config !== null);
const errors = ref<Record<string, string>>({});
const processing = ref(false);

const newFieldMapKey = ref('');
const newFieldMapValue = ref('');

function toggleSearchType(type: string): void {
    const index = form.search_types.indexOf(type);

    if (index >= 0) {
        form.search_types.splice(index, 1);
    } else {
        form.search_types.push(type);
    }
}

function toggleApi(checked: boolean): void {
    enableApi.value = checked;

    if (checked && !form.api_config) {
        form.api_config = {
            base_url: '',
            timeout: 30,
            id_prefix: 'EXT',
            auth: { type: 'none' },
            endpoints: {
                fetch: {
                    method: 'GET',
                    path: '/',
                    payload_root: 'data',
                    id_field: 'id',
                },
                search: {
                    method: 'GET',
                    path: '/search',
                    query_param: 'q',
                    type_param: 'type',
                    payload_root: 'data',
                    id_field: 'id',
                },
            },
            field_map: {},
        };
    } else if (!checked) {
        form.api_config = null;
    }
}

function addFieldMapping(): void {
    if (
        !newFieldMapKey.value ||
        !newFieldMapValue.value ||
        !form.api_config
    )
        return;

    if (!form.api_config.field_map) {
        form.api_config.field_map = {};
    }

    form.api_config.field_map[newFieldMapKey.value] = newFieldMapValue.value;
    newFieldMapKey.value = '';
    newFieldMapValue.value = '';
}

function removeFieldMapping(key: string): void {
    if (!form.api_config?.field_map) return;

    delete form.api_config.field_map[key];
}

function submit(): void {
    processing.value = true;
    errors.value = {};

    const url = isEdit.value
        ? `/fontes/${props.source!.slug}`
        : '/fontes';

    const method = isEdit.value ? 'put' : 'post';

    router[method](url, form as Record<string, unknown>, {
        onError: (errs) => {
            errors.value = errs;
            processing.value = false;
        },
        onSuccess: () => {
            toast.success(
                isEdit.value ? 'Fonte atualizada.' : 'Fonte criada.',
            );
            processing.value = false;
        },
    });
}
</script>

<template>
    <Head :title="title" />

    <div class="space-y-0.5">
        <h1 class="text-xl font-semibold tracking-tight">{{ title }}</h1>
        <p class="text-sm text-muted-foreground">
            {{
                isEdit
                    ? 'Edite os dados da fonte e sua integração de API.'
                    : 'Configure uma nova fonte de dados.'
            }}
        </p>
    </div>

    <form class="space-y-6" @submit.prevent="submit">
        <!-- Dados básicos -->
        <Card>
            <CardHeader>
                <CardTitle>Dados da fonte</CardTitle>
                <CardDescription>
                    Informações gerais e peso no merge multi-fonte.
                </CardDescription>
            </CardHeader>
            <CardContent class="grid gap-4 sm:grid-cols-2">
                <div class="space-y-2">
                    <Label for="slug">Slug</Label>
                    <Input
                        id="slug"
                        v-model="form.slug"
                        placeholder="minha-api-externa"
                        :disabled="isEdit"
                    />
                    <p
                        v-if="errors['slug']"
                        class="text-sm text-destructive"
                    >
                        {{ errors['slug'] }}
                    </p>
                </div>

                <div class="space-y-2">
                    <Label for="name">Nome</Label>
                    <Input
                        id="name"
                        v-model="form.name"
                        placeholder="API de Crédito"
                    />
                    <p
                        v-if="errors['name']"
                        class="text-sm text-destructive"
                    >
                        {{ errors['name'] }}
                    </p>
                </div>

                <div class="space-y-2">
                    <Label for="category">Categoria</Label>
                    <Input
                        id="category"
                        v-model="form.category"
                        placeholder="API"
                    />
                    <p
                        v-if="errors['category']"
                        class="text-sm text-destructive"
                    >
                        {{ errors['category'] }}
                    </p>
                </div>

                <div class="space-y-2">
                    <Label for="reliability">Confiabilidade (0–100)</Label>
                    <Input
                        id="reliability"
                        v-model.number="form.reliability"
                        type="number"
                        min="0"
                        max="100"
                    />
                    <p
                        v-if="errors['reliability']"
                        class="text-sm text-destructive"
                    >
                        {{ errors['reliability'] }}
                    </p>
                </div>

                <div class="space-y-2 sm:col-span-2">
                    <Label for="description">Descrição</Label>
                    <Input
                        id="description"
                        v-model="form.description"
                        placeholder="Descrição curta da fonte"
                    />
                </div>

                <div class="space-y-2 sm:col-span-2">
                    <Label>Tipos de busca aceitos</Label>
                    <div class="flex flex-wrap gap-2">
                        <button
                            v-for="(label, key) in searchTypeOptions"
                            :key="key"
                            type="button"
                            @click="toggleSearchType(key)"
                        >
                            <Badge
                                :variant="
                                    form.search_types.includes(key)
                                        ? 'default'
                                        : 'outline'
                                "
                                class="cursor-pointer"
                            >
                                {{ label }}
                            </Badge>
                        </button>
                    </div>
                    <p
                        v-if="errors['search_types']"
                        class="text-sm text-destructive"
                    >
                        {{ errors['search_types'] }}
                    </p>
                </div>

                <div class="flex items-center gap-2 sm:col-span-2">
                    <Checkbox
                        id="is_active"
                        :model-value="form.is_active"
                        @update:model-value="form.is_active = !!$event"
                    />
                    <Label for="is_active">Fonte ativa</Label>
                </div>

                <div class="space-y-2 sm:col-span-2">
                    <Label for="notes">Notas internas</Label>
                    <Input
                        id="notes"
                        v-model="form.notes"
                        placeholder="Observações (visível só no admin)"
                    />
                </div>
            </CardContent>
        </Card>

        <!-- API Config -->
        <Card>
            <CardHeader>
                <div class="flex items-center justify-between">
                    <div>
                        <CardTitle>Integração com API externa</CardTitle>
                        <CardDescription>
                            Configure a conexão para buscar dados de um serviço
                            externo.
                        </CardDescription>
                    </div>
                    <div class="flex items-center gap-2">
                        <Checkbox
                            id="enable_api"
                            :model-value="enableApi"
                            @update:model-value="toggleApi(!!$event)"
                        />
                        <Label for="enable_api">Habilitar</Label>
                    </div>
                </div>
            </CardHeader>
            <CardContent v-if="enableApi && form.api_config" class="space-y-6">
                <!-- Conexão -->
                <div class="grid gap-4 sm:grid-cols-3">
                    <div class="space-y-2 sm:col-span-2">
                        <Label for="base_url">URL base</Label>
                        <Input
                            id="base_url"
                            v-model="form.api_config.base_url"
                            placeholder="https://api.servico.com"
                        />
                        <p
                            v-if="errors['api_config.base_url']"
                            class="text-sm text-destructive"
                        >
                            {{ errors['api_config.base_url'] }}
                        </p>
                    </div>
                    <div class="space-y-2">
                        <Label for="timeout">Timeout (s)</Label>
                        <Input
                            id="timeout"
                            v-model.number="form.api_config.timeout"
                            type="number"
                            min="1"
                            max="120"
                        />
                    </div>
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="space-y-2">
                        <Label for="id_prefix">Prefixo de ID</Label>
                        <Input
                            id="id_prefix"
                            v-model="form.api_config.id_prefix"
                            placeholder="EXT"
                        />
                    </div>
                </div>

                <!-- Auth -->
                <div class="space-y-4">
                    <h3 class="text-sm font-medium">Autenticação</h3>
                    <div class="grid gap-4 sm:grid-cols-3">
                        <div class="space-y-2">
                            <Label for="auth_type">Tipo</Label>
                            <Select
                                :model-value="form.api_config.auth?.type ?? 'none'"
                                @update:model-value="
                                    (v: string) => {
                                        if (!form.api_config!.auth)
                                            form.api_config!.auth = {
                                                type: 'none',
                                            };
                                        form.api_config!.auth.type = v as 'none' | 'bearer' | 'api_key' | 'basic';
                                    }
                                "
                            >
                                <SelectTrigger>
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="none">
                                        Nenhuma
                                    </SelectItem>
                                    <SelectItem value="bearer">
                                        Bearer Token
                                    </SelectItem>
                                    <SelectItem value="api_key">
                                        API Key
                                    </SelectItem>
                                    <SelectItem value="basic">
                                        Basic Auth
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                        </div>

                        <template
                            v-if="form.api_config.auth?.type === 'bearer'"
                        >
                            <div class="space-y-2 sm:col-span-2">
                                <Label for="auth_token">Token</Label>
                                <Input
                                    id="auth_token"
                                    v-model="form.api_config.auth.token"
                                    placeholder="env:API_TOKEN ou valor direto"
                                />
                                <p class="text-xs text-muted-foreground">
                                    Use "env:NOME_VAR" para referenciar
                                    variáveis de ambiente.
                                </p>
                            </div>
                        </template>

                        <template
                            v-if="form.api_config.auth?.type === 'api_key'"
                        >
                            <div class="space-y-2">
                                <Label for="auth_header">Header</Label>
                                <Input
                                    id="auth_header"
                                    v-model="form.api_config.auth.header"
                                    placeholder="X-Api-Key"
                                />
                            </div>
                            <div class="space-y-2">
                                <Label for="auth_key">Chave</Label>
                                <Input
                                    id="auth_key"
                                    v-model="form.api_config.auth.key"
                                    placeholder="env:API_KEY"
                                />
                            </div>
                        </template>

                        <template
                            v-if="form.api_config.auth?.type === 'basic'"
                        >
                            <div class="space-y-2">
                                <Label for="auth_username">Usuário</Label>
                                <Input
                                    id="auth_username"
                                    v-model="form.api_config.auth.username"
                                />
                            </div>
                            <div class="space-y-2">
                                <Label for="auth_password">Senha</Label>
                                <Input
                                    id="auth_password"
                                    v-model="form.api_config.auth.password"
                                    type="password"
                                    placeholder="env:API_PASSWORD"
                                />
                            </div>
                        </template>
                    </div>
                </div>

                <!-- Endpoints -->
                <div class="space-y-4">
                    <h3 class="text-sm font-medium">Endpoint de ingestão (fetch)</h3>
                    <div class="grid gap-4 sm:grid-cols-4">
                        <div class="space-y-2">
                            <Label>Método</Label>
                            <Select
                                :model-value="form.api_config.endpoints?.fetch?.method ?? 'GET'"
                                @update:model-value="
                                    (v: string) => {
                                        if (!form.api_config!.endpoints)
                                            form.api_config!.endpoints = {};
                                        if (!form.api_config!.endpoints.fetch)
                                            form.api_config!.endpoints.fetch = {};
                                        form.api_config!.endpoints.fetch.method = v as 'GET' | 'POST';
                                    }
                                "
                            >
                                <SelectTrigger>
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="GET">GET</SelectItem>
                                    <SelectItem value="POST">POST</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                        <div class="space-y-2">
                            <Label>Path</Label>
                            <Input
                                :model-value="form.api_config.endpoints?.fetch?.path ?? ''"
                                placeholder="/records"
                                @update:model-value="
                                    (v: string) => {
                                        if (!form.api_config!.endpoints)
                                            form.api_config!.endpoints = {};
                                        if (!form.api_config!.endpoints.fetch)
                                            form.api_config!.endpoints.fetch = {};
                                        form.api_config!.endpoints.fetch.path = v;
                                    }
                                "
                            />
                        </div>
                        <div class="space-y-2">
                            <Label>Payload root</Label>
                            <Input
                                :model-value="form.api_config.endpoints?.fetch?.payload_root ?? ''"
                                placeholder="data"
                                @update:model-value="
                                    (v: string) => {
                                        if (!form.api_config!.endpoints)
                                            form.api_config!.endpoints = {};
                                        if (!form.api_config!.endpoints.fetch)
                                            form.api_config!.endpoints.fetch = {};
                                        form.api_config!.endpoints.fetch.payload_root = v;
                                    }
                                "
                            />
                        </div>
                        <div class="space-y-2">
                            <Label>Campo ID</Label>
                            <Input
                                :model-value="form.api_config.endpoints?.fetch?.id_field ?? ''"
                                placeholder="id"
                                @update:model-value="
                                    (v: string) => {
                                        if (!form.api_config!.endpoints)
                                            form.api_config!.endpoints = {};
                                        if (!form.api_config!.endpoints.fetch)
                                            form.api_config!.endpoints.fetch = {};
                                        form.api_config!.endpoints.fetch.id_field = v;
                                    }
                                "
                            />
                        </div>
                    </div>
                </div>

                <div class="space-y-4">
                    <h3 class="text-sm font-medium">Endpoint de busca (search)</h3>
                    <div class="grid gap-4 sm:grid-cols-3">
                        <div class="space-y-2">
                            <Label>Método</Label>
                            <Select
                                :model-value="form.api_config.endpoints?.search?.method ?? 'GET'"
                                @update:model-value="
                                    (v: string) => {
                                        if (!form.api_config!.endpoints)
                                            form.api_config!.endpoints = {};
                                        if (!form.api_config!.endpoints.search)
                                            form.api_config!.endpoints.search = {};
                                        form.api_config!.endpoints.search.method = v as 'GET' | 'POST';
                                    }
                                "
                            >
                                <SelectTrigger>
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="GET">GET</SelectItem>
                                    <SelectItem value="POST">POST</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                        <div class="space-y-2">
                            <Label>Path</Label>
                            <Input
                                :model-value="form.api_config.endpoints?.search?.path ?? ''"
                                placeholder="/search"
                                @update:model-value="
                                    (v: string) => {
                                        if (!form.api_config!.endpoints)
                                            form.api_config!.endpoints = {};
                                        if (!form.api_config!.endpoints.search)
                                            form.api_config!.endpoints.search = {};
                                        form.api_config!.endpoints.search.path = v;
                                    }
                                "
                            />
                        </div>
                        <div class="space-y-2">
                            <Label>Payload root</Label>
                            <Input
                                :model-value="form.api_config.endpoints?.search?.payload_root ?? ''"
                                placeholder="data"
                                @update:model-value="
                                    (v: string) => {
                                        if (!form.api_config!.endpoints)
                                            form.api_config!.endpoints = {};
                                        if (!form.api_config!.endpoints.search)
                                            form.api_config!.endpoints.search = {};
                                        form.api_config!.endpoints.search.payload_root = v;
                                    }
                                "
                            />
                        </div>
                        <div class="space-y-2">
                            <Label>Param de busca</Label>
                            <Input
                                :model-value="form.api_config.endpoints?.search?.query_param ?? ''"
                                placeholder="q"
                                @update:model-value="
                                    (v: string) => {
                                        if (!form.api_config!.endpoints)
                                            form.api_config!.endpoints = {};
                                        if (!form.api_config!.endpoints.search)
                                            form.api_config!.endpoints.search = {};
                                        form.api_config!.endpoints.search.query_param = v;
                                    }
                                "
                            />
                        </div>
                        <div class="space-y-2">
                            <Label>Param de tipo</Label>
                            <Input
                                :model-value="form.api_config.endpoints?.search?.type_param ?? ''"
                                placeholder="type"
                                @update:model-value="
                                    (v: string) => {
                                        if (!form.api_config!.endpoints)
                                            form.api_config!.endpoints = {};
                                        if (!form.api_config!.endpoints.search)
                                            form.api_config!.endpoints.search = {};
                                        form.api_config!.endpoints.search.type_param = v;
                                    }
                                "
                            />
                        </div>
                        <div class="space-y-2">
                            <Label>Campo ID</Label>
                            <Input
                                :model-value="form.api_config.endpoints?.search?.id_field ?? ''"
                                placeholder="id"
                                @update:model-value="
                                    (v: string) => {
                                        if (!form.api_config!.endpoints)
                                            form.api_config!.endpoints = {};
                                        if (!form.api_config!.endpoints.search)
                                            form.api_config!.endpoints.search = {};
                                        form.api_config!.endpoints.search.id_field = v;
                                    }
                                "
                            />
                        </div>
                    </div>
                </div>

                <!-- Field Map -->
                <div class="space-y-4">
                    <h3 class="text-sm font-medium">
                        Mapeamento de campos
                    </h3>
                    <p class="text-xs text-muted-foreground">
                        Traduza os nomes dos campos da API externa para os
                        campos internos do sistema.
                    </p>

                    <div
                        v-if="
                            form.api_config.field_map &&
                            Object.keys(form.api_config.field_map).length > 0
                        "
                        class="space-y-2"
                    >
                        <div
                            v-for="(value, key) in form.api_config.field_map"
                            :key="key"
                            class="flex items-center gap-2"
                        >
                            <Badge variant="outline" class="font-mono">
                                {{ key }}
                            </Badge>
                            <span class="text-muted-foreground">&rarr;</span>
                            <Badge class="font-mono">
                                {{ fieldOptions[value] ?? value }}
                            </Badge>
                            <Button
                                type="button"
                                variant="ghost"
                                size="icon"
                                class="ml-auto size-7"
                                @click="removeFieldMapping(String(key))"
                            >
                                <Trash2 class="size-3" />
                            </Button>
                        </div>
                    </div>

                    <div class="flex items-end gap-2">
                        <div class="space-y-2">
                            <Label>Campo externo</Label>
                            <Input
                                v-model="newFieldMapKey"
                                placeholder="nome_completo"
                                class="w-40"
                            />
                        </div>
                        <div class="space-y-2">
                            <Label>Campo interno</Label>
                            <Select v-model="newFieldMapValue">
                                <SelectTrigger class="w-44">
                                    <SelectValue placeholder="Selecionar" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem
                                        v-for="(label, key) in fieldOptions"
                                        :key="key"
                                        :value="key"
                                    >
                                        {{ label }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                        <Button
                            type="button"
                            variant="outline"
                            size="icon"
                            :disabled="!newFieldMapKey || !newFieldMapValue"
                            @click="addFieldMapping"
                        >
                            <Plus class="size-4" />
                        </Button>
                    </div>
                </div>
            </CardContent>
        </Card>

        <!-- Submit -->
        <div class="flex gap-2">
            <Button type="submit" :disabled="processing">
                {{ isEdit ? 'Salvar alterações' : 'Criar fonte' }}
            </Button>
            <Button
                type="button"
                variant="outline"
                @click="router.visit('/fontes')"
            >
                Cancelar
            </Button>
        </div>
    </form>
</template>
