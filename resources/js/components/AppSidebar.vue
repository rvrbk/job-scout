<script setup lang="ts">
import { Link, router } from '@inertiajs/vue3';
import { BookCheck, Briefcase, LayoutGrid } from '@lucide/vue';
import { onUnmounted } from 'vue';
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
    useSidebar,
} from '@/components/ui/sidebar';
import { dashboard } from '@/routes';
import { index as jobsIndex } from '@/routes/jobs';
import { index as ledgerIndex } from '@/routes/ledger';
import type { NavItem } from '@/types';

const mainNavItems: NavItem[] = [
    {
        title: 'Dashboard',
        href: dashboard(),
        icon: LayoutGrid,
    },
    {
        title: 'Facts ledger',
        href: ledgerIndex(),
        icon: BookCheck,
    },
    {
        title: 'Jobs',
        href: jobsIndex(),
        icon: Briefcase,
    },
];

const footerNavItems: NavItem[] = [];

// On mobile the sidebar is a sheet over the page; fold it away once a link has been followed.
const { setOpenMobile } = useSidebar();
const stopListening = router.on('navigate', () => setOpenMobile(false));

onUnmounted(stopListening);
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link :href="dashboard()">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <NavMain :items="mainNavItems" />
        </SidebarContent>

        <SidebarFooter>
            <NavFooter :items="footerNavItems" />
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
