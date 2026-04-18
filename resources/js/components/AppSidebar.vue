<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { Bot, Building2, CreditCard, FileText, History, LayoutDashboard, Settings, User as UserIcon } from 'lucide-vue-next';
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
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import { dashboard } from '@/routes';
import type { NavItem } from '@/types';

const page = usePage();
const user = computed(() => page.props.auth.user);
const isAdmin = computed(() => user.value?.user_type === 'admin');

const mainNavItems = computed((): NavItem[] => {
    if (isAdmin.value) {
        return [
            {
                title: 'Dashboard',
                href: dashboard(),
                icon: LayoutDashboard,
            },
            {
                title: 'Users',
                href: '/users',
                icon: UserIcon,
            },
            {
                title: 'Clients',
                href: '/clients',
                icon: Building2,
            },
            {
                title: 'Plans',
                href: '/plans',
                icon: CreditCard,
            },
        ];
    }

    // Client portal links
    return [
        {
            title: 'Dashboard',
            href: '/portal/dashboard',
            icon: LayoutDashboard,
        },
        {
            title: 'Playground',
            href: '/portal/playground',
            icon: Bot,
        },
        {
            title: 'Chat History',
            href: '/portal/chat-history',
            icon: History,
        },
        {
            title: 'Leads',
            href: '/portal/leads',
            icon: FileText,
        },
        {
            title: 'Subscription',
            href: '/portal/subscription',
            icon: CreditCard,
        },
    ];
});

const footerNavItems: NavItem[] = [];
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link :href="isAdmin ? dashboard() : '/portal/dashboard'">
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
