<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ArrowRight, Moon, Search, Sun } from '@lucide/vue';
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import VertaxLogo from '@/components/landing/VertaxLogo.vue';
import type { LandingTheme } from '@/composables/useLandingTheme';
import { isAnchor, navigation } from '@/lib/landing/navigation';
import { login, register } from '@/routes';
import type { LandingPlan } from '@/types/landing';

const props = defineProps<{
    canRegister: boolean;
    plans: LandingPlan[];
    theme: LandingTheme;
}>();

const emit = defineEmits<{
    search: [];
    toggleTheme: [];
}>();

const groups = computed(() => navigation(props.plans));
const open = ref<number | null>(null);
const mobileOpen = ref(false);
const scrolled = ref(false);
const shortcut = ref('⌘');
const nav = ref<HTMLElement | null>(null);
const triggers = ref<HTMLButtonElement[]>([]);
let closeTimer: number | undefined;

const solid = computed(() => scrolled.value || mobileOpen.value);

function show(index: number): void {
    window.clearTimeout(closeTimer);
    open.value = index;
}

function hideSoon(): void {
    window.clearTimeout(closeTimer);
    closeTimer = window.setTimeout(() => (open.value = null), 140);
}

function toggle(index: number): void {
    open.value = open.value === index ? null : index;
}

function closeMenus(): void {
    open.value = null;
    mobileOpen.value = false;
}

function setTrigger(el: unknown, index: number): void {
    if (el instanceof HTMLButtonElement) triggers.value[index] = el;
}

function onEscape(): void {
    const index = open.value;
    open.value = null;

    if (index !== null) triggers.value[index]?.focus();
}

function onScroll(): void {
    scrolled.value = window.scrollY > 8;
}

function onPointerDown(event: PointerEvent): void {
    if (nav.value && !nav.value.contains(event.target as Node)) {
        open.value = null;
    }
}

onMounted(() => {
    shortcut.value = /Mac|iPhone|iPad/.test(navigator.userAgent) ? '⌘' : 'Ctrl';
    onScroll();
    window.addEventListener('scroll', onScroll, { passive: true });
    document.addEventListener('pointerdown', onPointerDown);
});

onBeforeUnmount(() => {
    window.clearTimeout(closeTimer);
    window.removeEventListener('scroll', onScroll);
    document.removeEventListener('pointerdown', onPointerDown);
});
</script>

<template>
    <header
        class="fixed inset-x-0 top-0 z-60 border-b border-(--header-border) transition-colors duration-300"
        :class="
            solid
                ? 'bg-(--header-bg) backdrop-blur-md'
                : 'bg-(--header-bg) backdrop-blur-md md:bg-transparent md:backdrop-blur-none'
        "
    >
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="flex h-16 items-center">
                <a
                    href="#topo"
                    class="mr-auto flex shrink-0 items-center"
                    aria-label="Vertax, voltar ao início"
                    @click="closeMenus"
                >
                    <VertaxLogo />
                </a>

                <nav
                    ref="nav"
                    class="hidden items-center gap-0.5 md:flex"
                    aria-label="Navegação principal"
                    @keydown.esc="onEscape"
                >
                    <div
                        v-for="(group, index) in groups"
                        :key="group.label"
                        class="relative"
                        @mouseenter="show(index)"
                        @mouseleave="hideSoon"
                    >
                        <button
                            :ref="(el) => setTrigger(el, index)"
                            type="button"
                            class="flex cursor-pointer items-center gap-1.25 rounded-md px-3 py-2 text-[13px] font-medium transition-colors"
                            :class="
                                open === index
                                    ? 'text-foreground'
                                    : 'text-foreground/55 hover:text-foreground/90'
                            "
                            :aria-expanded="open === index"
                            :aria-controls="`menu-${index}`"
                            @click="toggle(index)"
                        >
                            {{ group.label }}
                            <span
                                class="relative flex size-3 items-center justify-center"
                                aria-hidden="true"
                            >
                                <span
                                    class="absolute left-[2%] block h-[1.5px] w-[55%] origin-right rounded-full bg-current transition-transform duration-200"
                                    :class="
                                        open === index
                                            ? '-translate-y-px -rotate-40'
                                            : 'translate-y-[1.5px] rotate-40'
                                    "
                                />
                                <span
                                    class="absolute right-[2%] block h-[1.5px] w-[55%] origin-left rounded-full bg-current transition-transform duration-200"
                                    :class="
                                        open === index
                                            ? '-translate-y-px rotate-40'
                                            : 'translate-y-[1.5px] -rotate-40'
                                    "
                                />
                            </span>
                        </button>

                        <Transition
                            enter-active-class="transition duration-150 ease-out"
                            enter-from-class="translate-y-1 opacity-0"
                            leave-active-class="transition duration-100 ease-in"
                            leave-to-class="translate-y-1 opacity-0"
                        >
                            <div
                                v-if="open === index"
                                :id="`menu-${index}`"
                                class="absolute top-full left-0 z-10 mt-2 w-85 rounded-xl border border-border bg-popover p-1.5 shadow-2xl shadow-black/30"
                            >
                                <component
                                    :is="isAnchor(item.href) ? 'a' : Link"
                                    v-for="item in group.items"
                                    :key="item.label"
                                    :href="item.href"
                                    class="flex gap-3 rounded-lg p-2.5 transition-colors outline-none hover:bg-foreground/5 focus-visible:bg-foreground/5"
                                    @click="closeMenus"
                                >
                                    <span
                                        class="flex size-9 shrink-0 items-center justify-center rounded-lg border border-border bg-surface-2 text-brand-blue"
                                    >
                                        <component
                                            :is="item.icon"
                                            class="size-4"
                                        />
                                    </span>
                                    <span class="min-w-0">
                                        <span
                                            class="block text-[13px] font-medium text-foreground"
                                        >
                                            {{ item.label }}
                                        </span>
                                        <span
                                            class="block text-xs leading-snug text-muted-foreground"
                                        >
                                            {{ item.description }}
                                        </span>
                                    </span>
                                </component>
                                <div
                                    v-if="group.footer"
                                    class="mt-1 border-t border-border px-2.5 pt-2.5 pb-1"
                                >
                                    <Link
                                        :href="group.footer.href"
                                        class="inline-flex items-center gap-1.5 text-xs font-medium text-brand-blue hover:underline"
                                        @click="closeMenus"
                                    >
                                        {{ group.footer.label }}
                                        <ArrowRight class="size-3" />
                                    </Link>
                                </div>
                            </div>
                        </Transition>
                    </div>

                    <button
                        type="button"
                        class="ml-1 flex items-center gap-1.5 rounded-md px-2.5 py-2 text-[13px] font-medium text-foreground/55 transition-colors hover:text-foreground/90"
                        aria-label="Buscar na página"
                        @click="emit('search')"
                    >
                        <kbd
                            class="flex h-5 min-w-5 items-center justify-center rounded-md border border-foreground/12 px-1 font-geist text-[11px] font-medium"
                        >
                            {{ shortcut }}
                        </kbd>
                        <kbd
                            class="flex size-5 items-center justify-center rounded-md border border-foreground/12 font-geist text-[11px] font-medium"
                        >
                            K
                        </kbd>
                        <Search class="size-4" />
                    </button>
                    <button
                        type="button"
                        class="rounded-md p-2 text-foreground/55 transition-colors hover:text-foreground/90"
                        :aria-label="
                            theme === 'dark'
                                ? 'Usar tema claro'
                                : 'Usar tema escuro'
                        "
                        @click="emit('toggleTheme')"
                    >
                        <Sun v-if="theme === 'dark'" class="size-4" />
                        <Moon v-else class="size-4" />
                    </button>
                    <span
                        class="mx-2 h-4.5 w-px bg-foreground/12"
                        aria-hidden="true"
                    />
                    <Link
                        :href="login()"
                        class="rounded-md px-3 py-2 text-[13px] font-medium text-foreground/55 transition-colors hover:text-foreground/90"
                    >
                        Entrar
                    </Link>
                    <Link
                        v-if="canRegister"
                        :href="register()"
                        class="ml-1 flex items-center rounded-md bg-primary px-3.5 py-1.5 text-[13px] font-medium text-primary-foreground transition-opacity select-none hover:opacity-90"
                    >
                        Criar conta grátis
                    </Link>
                </nav>

                <Link
                    :href="login()"
                    class="mr-1 px-2 py-1.5 text-[13px] font-medium text-foreground/80 md:hidden"
                >
                    Entrar
                </Link>
                <Link
                    v-if="canRegister"
                    :href="register()"
                    class="mr-2 flex items-center rounded-[6px] bg-primary px-3 py-1.5 text-[12px] font-medium text-primary-foreground md:hidden"
                >
                    Começar
                </Link>
                <button
                    type="button"
                    class="rounded-md p-2 text-foreground md:hidden"
                    :aria-expanded="mobileOpen"
                    aria-controls="menu-mobile"
                    :aria-label="mobileOpen ? 'Fechar menu' : 'Abrir menu'"
                    @click="mobileOpen = !mobileOpen"
                >
                    <span class="relative block size-6" aria-hidden="true">
                        <span
                            class="absolute inset-x-1 h-0.5 rounded-full bg-current transition-transform duration-300"
                            :class="
                                mobileOpen ? 'top-2.75 rotate-45' : 'top-1.75'
                            "
                        />
                        <span
                            class="absolute inset-x-1 h-0.5 rounded-full bg-current transition-transform duration-300"
                            :class="
                                mobileOpen ? 'top-2.75 -rotate-45' : 'top-3.75'
                            "
                        />
                    </span>
                </button>
            </div>
        </div>

        <div
            v-if="mobileOpen"
            id="menu-mobile"
            class="absolute inset-x-0 top-full max-h-[calc(100svh-4rem)] overflow-y-auto border-b border-border bg-background px-4 pt-2 pb-6 sm:px-6 md:hidden"
        >
            <div
                v-for="group in groups"
                :key="group.label"
                class="border-b border-border py-3"
            >
                <p
                    class="px-2 pb-1 text-xs font-semibold tracking-wide text-muted-foreground uppercase"
                >
                    {{ group.label }}
                </p>
                <component
                    :is="isAnchor(item.href) ? 'a' : Link"
                    v-for="item in group.items"
                    :key="item.label"
                    :href="item.href"
                    class="flex items-center gap-3 rounded-lg px-2 py-2 text-sm text-foreground hover:bg-foreground/5"
                    @click="closeMenus"
                >
                    <component :is="item.icon" class="size-4 text-brand-blue" />
                    {{ item.label }}
                </component>
            </div>
            <div class="flex items-center justify-between pt-4">
                <button
                    type="button"
                    class="flex items-center gap-2 rounded-md px-2 py-2 text-sm text-muted-foreground"
                    @click="emit('toggleTheme')"
                >
                    <Sun v-if="theme === 'dark'" class="size-4" />
                    <Moon v-else class="size-4" />
                    {{ theme === 'dark' ? 'Tema claro' : 'Tema escuro' }}
                </button>
                <button
                    type="button"
                    class="flex items-center gap-2 rounded-md px-2 py-2 text-sm text-muted-foreground"
                    @click="
                        closeMenus();
                        emit('search');
                    "
                >
                    <Search class="size-4" />
                    Buscar
                </button>
            </div>
        </div>
    </header>
</template>
