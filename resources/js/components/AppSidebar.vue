<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import {
    BookOpen,
    Boxes,
    CreditCard,
    Database,
    FolderGit2,
    LayoutGrid,
    LogIn,
    Radio,
    Search,
} from '@lucide/vue';
import { computed } from 'vue';
import AppLogo from '@/components/AppLogo.vue';
import NavFooter from '@/components/NavFooter.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { usePermissions } from '@/composables/usePermissions';
import { dashboard, home, login } from '@/routes';
import demo from '@/routes/demo';
import type { NavItem } from '@/types';

const page = usePage();
const user = computed(() => page.props.auth.user);
const { can } = usePermissions();

const mainNavItems: NavItem[] = [
    {
        title: 'Dashboard',
        href: dashboard(),
        icon: LayoutGrid,
    },
];

const demoNavItems = computed<NavItem[]>(() => [
    {
        title: 'Buscar',
        href: home(),
        icon: Search,
    },
    {
        title: 'Módulos',
        href: demo.modules(),
        icon: Boxes,
    },
    {
        title: 'Planos',
        href: demo.plans(),
        icon: CreditCard,
        badge: page.props.demo.plan.active ? 'Ativo' : undefined,
    },
    ...(can('painel.visualizar')
        ? [
              {
                  title: 'Painel',
                  href: demo.panel(),
                  icon: Database,
              },
          ]
        : []),
    ...(can('fontes.gerenciar')
        ? [
              {
                  title: 'Fontes',
                  href: '/fontes',
                  icon: Radio,
              },
          ]
        : []),
]);

const footerNavItems: NavItem[] = [
    {
        title: 'Repository',
        href: 'https://github.com/laravel/vue-starter-kit',
        icon: FolderGit2,
    },
    {
        title: 'Documentation',
        href: 'https://laravel.com/docs/starter-kits#vue',
        icon: BookOpen,
    },
];
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link :href="home()">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <NavMain v-if="user" :items="mainNavItems" />
            <NavMain label="Consulta" :items="demoNavItems" />
        </SidebarContent>

        <SidebarFooter>
            <p
                class="px-2 text-xs text-muted-foreground group-data-[collapsible=icon]:hidden"
            >
                {{ page.props.demo.shortDisclaimer }}
            </p>
            <NavFooter :items="footerNavItems" />
            <NavUser v-if="user" />
            <SidebarMenu v-else>
                <SidebarMenuItem>
                    <SidebarMenuButton as-child tooltip="Entrar">
                        <Link :href="login()">
                            <LogIn />
                            <span>Entrar</span>
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
