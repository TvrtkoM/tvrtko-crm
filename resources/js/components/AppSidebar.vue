<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import {
    Building2,
    FileText,
    Handshake,
    LayoutGrid,
    Users,
} from '@lucide/vue';
import AppLogo from '@/components/AppLogo.vue';
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
import { dashboard } from '@/routes';
import { board as companiesBoard } from '@/routes/companies';
import { board as contactsBoard } from '@/routes/contacts';
import { board as dealsBoard } from '@/routes/deals';
import { board as offersBoard } from '@/routes/offers';
import type { NavItem } from '@/types';

const mainNavItems: NavItem[] = [
    {
        title: 'Dashboard',
        href: dashboard(),
        icon: LayoutGrid,
    },
    {
        title: 'Companies',
        href: companiesBoard(),
        icon: Building2,
    },
    {
        title: 'Contacts',
        href: contactsBoard(),
        icon: Users,
    },
    {
        title: 'Deals',
        href: dealsBoard(),
        icon: Handshake,
    },
    {
        title: 'Offers',
        href: offersBoard(),
        icon: FileText,
    },
];
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
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
