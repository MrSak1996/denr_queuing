<script setup lang="ts">
import NavFooter from '@/components/NavFooter.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import { Sidebar, SidebarContent, SidebarFooter, SidebarHeader, SidebarMenu, SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';
import { type NavItem } from '@/types';
import { Link, usePage } from '@inertiajs/vue3';
import { BookOpen, Folder, LayoutGrid, Monitor, GalleryHorizontal } from 'lucide-vue-next';
import AppLogo from './AppLogo.vue';

// Get logged-in user role from Inertia page props
const page = usePage();
const userRole = page.props.auth.user.user_role as string;  // assuming role is 'admin' or 'user'

// Initialize main nav items
const mainNavItems: NavItem[] = [];

if (userRole === 'admin') {
    mainNavItems.push({
        title: 'Dashboard',
        href: '/dashboard',
        icon: LayoutGrid,
    });
    mainNavItems.push({
        title: 'Client',
        href: '/client/index',
        icon: Monitor,
    });
} else if (userRole === 'user') {
    mainNavItems.push({
        title: 'Transaction Type',
        href: '/transaction/index',
        icon: GalleryHorizontal,
    });
}

const footerNavItems: NavItem[] = [
    // {
    //     title: 'Github Repo',
    //     href: 'https://github.com/laravel/vue-starter-kit',
    //     icon: Folder,
    // },
    // {
    //     title: 'Documentation',
    //     href: 'https://laravel.com/docs/starter-kits',
    //     icon: BookOpen,
    // },
];
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link :href="route('dashboard')">
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