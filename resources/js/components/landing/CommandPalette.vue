<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { CornerDownLeft, Search } from '@lucide/vue';
import {
    computed,
    nextTick,
    onBeforeUnmount,
    onMounted,
    ref,
    watch,
} from 'vue';
import { isAnchor, type NavGroup } from '@/lib/landing/navigation';

const props = defineProps<{
    groups: NavGroup[];
}>();

const open = defineModel<boolean>('open', { required: true });

const query = ref('');
const active = ref(0);
const input = ref<HTMLInputElement | null>(null);
let returnFocus: HTMLElement | null = null;

function normalize(value: string): string {
    return value
        .normalize('NFD')
        .replace(/[̀-ͯ]/g, '')
        .toLowerCase();
}

const results = computed(() => {
    const needle = normalize(query.value.trim());
    const seen = new Set<string>();

    return props.groups.flatMap((group) =>
        group.items
            .filter((item) => {
                const key = `${item.label}|${item.href}`;

                if (seen.has(key)) return false;

                seen.add(key);

                return (
                    !needle ||
                    normalize(
                        `${item.label} ${item.description} ${group.label}`,
                    ).includes(needle)
                );
            })
            .map((item) => ({ ...item, group: group.label })),
    );
});

watch(query, () => (active.value = 0));

watch(open, async (isOpen) => {
    if (isOpen) {
        returnFocus = document.activeElement as HTMLElement | null;
        query.value = '';
        active.value = 0;
        await nextTick();
        input.value?.focus();
    } else {
        returnFocus?.focus();
    }
});

function go(index: number): void {
    const item = results.value[index];

    if (!item) return;

    open.value = false;

    if (isAnchor(item.href)) {
        window.location.hash = item.href;
    } else {
        router.visit(item.href);
    }
}

function onKeydown(event: KeyboardEvent): void {
    const total = results.value.length;

    if (event.key === 'ArrowDown' && total) {
        event.preventDefault();
        active.value = (active.value + 1) % total;
    } else if (event.key === 'ArrowUp' && total) {
        event.preventDefault();
        active.value = (active.value - 1 + total) % total;
    } else if (event.key === 'Enter') {
        event.preventDefault();
        go(active.value);
    } else if (event.key === 'Escape') {
        event.preventDefault();
        open.value = false;
    } else if (event.key === 'Tab') {
        event.preventDefault();
    }
}

function onShortcut(event: KeyboardEvent): void {
    if ((event.metaKey || event.ctrlKey) && event.key.toLowerCase() === 'k') {
        event.preventDefault();
        open.value = !open.value;
    }
}

onMounted(() => window.addEventListener('keydown', onShortcut));
onBeforeUnmount(() => window.removeEventListener('keydown', onShortcut));
</script>

<template>
    <Transition
        enter-active-class="transition duration-150 ease-out"
        enter-from-class="opacity-0"
        leave-active-class="transition duration-100 ease-in"
        leave-to-class="opacity-0"
    >
        <div
            v-if="open"
            class="fixed inset-0 z-[70] flex items-start justify-center bg-black/55 px-4 pt-[14vh] backdrop-blur-sm"
            @mousedown.self="open = false"
        >
            <div
                role="dialog"
                aria-modal="true"
                aria-label="Ir para"
                class="w-full max-w-lg overflow-hidden rounded-xl border border-border bg-popover shadow-2xl shadow-black/40"
            >
                <div
                    class="flex items-center gap-3 border-b border-border px-4"
                >
                    <Search class="size-4 shrink-0 text-muted-foreground" />
                    <input
                        ref="input"
                        v-model="query"
                        type="text"
                        role="combobox"
                        aria-expanded="true"
                        aria-controls="paleta-resultados"
                        :aria-activedescendant="
                            results.length ? `paleta-${active}` : undefined
                        "
                        placeholder="Ir para uma seção ou página..."
                        class="h-12 w-full bg-transparent text-sm text-foreground outline-none placeholder:text-muted-foreground"
                        @keydown="onKeydown"
                    />
                    <kbd
                        class="rounded-md border border-border px-1.5 py-0.5 font-geist text-[10px] text-muted-foreground"
                    >
                        Esc
                    </kbd>
                </div>
                <ul
                    id="paleta-resultados"
                    role="listbox"
                    class="max-h-80 overflow-y-auto p-1.5"
                >
                    <li
                        v-for="(item, index) in results"
                        :id="`paleta-${index}`"
                        :key="`${item.group}-${item.label}`"
                        role="option"
                        :aria-selected="index === active"
                        class="flex cursor-pointer items-center gap-3 rounded-lg px-2.5 py-2"
                        :class="index === active ? 'bg-foreground/[0.06]' : ''"
                        @mousemove="active = index"
                        @click="go(index)"
                    >
                        <span
                            class="flex size-8 shrink-0 items-center justify-center rounded-md border border-border bg-surface-2 text-brand-blue"
                        >
                            <component :is="item.icon" class="size-4" />
                        </span>
                        <span class="min-w-0 flex-1">
                            <span class="block text-sm text-foreground">
                                {{ item.label }}
                            </span>
                            <span
                                class="block truncate text-xs text-muted-foreground"
                            >
                                {{ item.group }} · {{ item.description }}
                            </span>
                        </span>
                        <CornerDownLeft
                            v-if="index === active"
                            class="size-3.5 text-muted-foreground"
                        />
                    </li>
                    <li
                        v-if="!results.length"
                        class="px-3 py-8 text-center text-sm text-muted-foreground"
                    >
                        Nada encontrado para “{{ query }}”.
                    </li>
                </ul>
                <div
                    class="flex items-center gap-4 border-t border-border px-4 py-2 text-[11px] text-muted-foreground"
                >
                    <span>↑ ↓ navegar</span>
                    <span>↵ abrir</span>
                    <span>esc fechar</span>
                </div>
            </div>
        </div>
    </Transition>
</template>
