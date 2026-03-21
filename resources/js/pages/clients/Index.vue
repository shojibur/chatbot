<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import {
    ArrowUpRight,
    FolderOpen,
    Pencil,
    Plus,
    Zap,
} from 'lucide-vue-next';
import { computed } from 'vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import { dashboard } from '@/routes';
import type { BreadcrumbItem, ClientListItem } from '@/types';

type Props = {
    clients: ClientListItem[];
    summary: {
        total_clients: number;
        active_clients: number;
        paused_clients: number;
        monthly_token_capacity: number;
        current_month_usage: number;
        knowledge_sources: number;
    };
    status?: string;
};

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: dashboard(),
    },
    {
        title: 'Clients',
        href: '/clients',
    },
];

const statusLabels: Record<string, string> = {
    draft: 'Draft',
    active: 'Active',
    paused: 'Paused',
};

const statusMessage = computed(() => {
    if (props.status === 'client-created') {
        return 'Client created and ready for knowledge base setup.';
    }
    if (props.status === 'client-updated') {
        return 'Client configuration updated.';
    }
    if (props.status === 'knowledge-source-created') {
        return 'Knowledge source saved for processing.';
    }
    return null;
});

function badgeVariant(status: string): 'default' | 'secondary' | 'outline' {
    if (status === 'active') return 'default';
    if (status === 'paused') return 'secondary';
    return 'outline';
}

function usagePercent(used: number, limit: number): number {
    if (limit === 0) return 0;
    return Math.min(Math.round((used / limit) * 100), 100);
}

function usageColor(percent: number): string {
    if (percent >= 90) return 'bg-red-500';
    if (percent >= 70) return 'bg-amber-500';
    return 'bg-emerald-500';
}
</script>

<template>
    <Head title="Clients" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-1 flex-col gap-6 p-4 md:p-6">
            <!-- Page header -->
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h1 class="text-2xl font-semibold tracking-tight">Clients</h1>
                    <p class="text-sm text-muted-foreground">
                        {{ props.summary.total_clients }} total &middot;
                        {{ props.summary.active_clients }} active &middot;
                        {{ props.summary.paused_clients }} paused
                    </p>
                </div>
                <Button as-child>
                    <Link href="/clients/create">
                        <Plus class="mr-2 size-4" />
                        New client
                    </Link>
                </Button>
            </div>

            <!-- Status message -->
            <div
                v-if="statusMessage"
                class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700 dark:border-emerald-300/10 dark:bg-emerald-500/10 dark:text-emerald-200"
            >
                {{ statusMessage }}
            </div>

            <!-- Empty state -->
            <div
                v-if="clients.length === 0"
                class="flex flex-col items-center justify-center rounded-xl border border-dashed border-sidebar-border/70 py-16 text-center"
            >
                <div class="rounded-lg border border-sidebar-border/70 bg-accent/30 p-3">
                    <Plus class="size-6 text-muted-foreground" />
                </div>
                <h3 class="mt-4 text-sm font-medium">No clients yet</h3>
                <p class="mt-1 text-sm text-muted-foreground">Create your first client to get started.</p>
                <Button as-child class="mt-4" size="sm">
                    <Link href="/clients/create">
                        <Plus class="mr-2 size-4" />
                        New client
                    </Link>
                </Button>
            </div>

            <!-- Client table/list -->
            <div v-else class="overflow-hidden rounded-xl border border-sidebar-border/70">
                <!-- Table header -->
                <div class="hidden border-b border-sidebar-border/70 bg-muted/30 px-6 py-3 text-xs font-medium uppercase tracking-wider text-muted-foreground md:grid md:grid-cols-[2fr_1fr_1fr_1fr_auto]">
                    <span>Client</span>
                    <span>Plan</span>
                    <span>Sources</span>
                    <span>Usage</span>
                    <span class="w-20 text-right">Actions</span>
                </div>

                <!-- Client rows -->
                <div class="divide-y divide-sidebar-border/70">
                    <div
                        v-for="client in clients"
                        :key="client.id"
                        class="group transition hover:bg-accent/30"
                    >
                        <!-- Desktop row -->
                        <div class="hidden items-center gap-4 px-6 py-4 md:grid md:grid-cols-[2fr_1fr_1fr_1fr_auto]">
                            <!-- Client info -->
                            <div class="min-w-0">
                                <div class="flex items-center gap-2">
                                    <Link
                                        :href="`/clients/${client.id}`"
                                        class="truncate text-sm font-medium hover:underline"
                                    >
                                        {{ client.name }}
                                    </Link>
                                    <Badge :variant="badgeVariant(client.status)" class="shrink-0 text-[10px]">
                                        {{ statusLabels[client.status] ?? client.status }}
                                    </Badge>
                                </div>
                                <p class="mt-0.5 truncate text-xs text-muted-foreground">
                                    {{ client.contact_email ?? 'No email' }}
                                </p>
                            </div>

                            <!-- Plan -->
                            <div>
                                <Badge v-if="client.plan" variant="outline" class="text-xs">
                                    {{ client.plan.name }}
                                </Badge>
                                <span v-else class="text-xs text-muted-foreground">--</span>
                            </div>

                            <!-- Sources -->
                            <div class="flex items-center gap-1.5 text-sm">
                                <FolderOpen class="size-3.5 text-muted-foreground" />
                                {{ client.knowledge_sources_count }}
                            </div>

                            <!-- Usage bar -->
                            <div class="space-y-1">
                                <div class="flex items-center justify-between text-xs">
                                    <span>{{ client.current_month_tokens.toLocaleString() }}</span>
                                    <span class="text-muted-foreground">/ {{ client.monthly_token_limit.toLocaleString() }}</span>
                                </div>
                                <div class="h-1.5 w-full overflow-hidden rounded-full bg-muted">
                                    <div
                                        class="h-full rounded-full transition-all"
                                        :class="usageColor(usagePercent(client.current_month_tokens, client.monthly_token_limit))"
                                        :style="{ width: `${usagePercent(client.current_month_tokens, client.monthly_token_limit)}%` }"
                                    />
                                </div>
                            </div>

                            <!-- Actions -->
                            <div class="flex w-20 justify-end gap-1">
                                <Button variant="ghost" size="icon" as-child class="size-8">
                                    <Link :href="`/clients/${client.id}`">
                                        <ArrowUpRight class="size-4" />
                                    </Link>
                                </Button>
                                <Button variant="ghost" size="icon" as-child class="size-8">
                                    <Link :href="`/clients/${client.id}/edit`">
                                        <Pencil class="size-3.5" />
                                    </Link>
                                </Button>
                            </div>
                        </div>

                        <!-- Mobile card -->
                        <div class="space-y-3 px-4 py-4 md:hidden">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <Link
                                            :href="`/clients/${client.id}`"
                                            class="text-sm font-medium hover:underline"
                                        >
                                            {{ client.name }}
                                        </Link>
                                        <Badge :variant="badgeVariant(client.status)" class="text-[10px]">
                                            {{ statusLabels[client.status] ?? client.status }}
                                        </Badge>
                                        <Badge v-if="client.plan" variant="outline" class="text-[10px]">
                                            {{ client.plan.name }}
                                        </Badge>
                                    </div>
                                    <p class="mt-0.5 text-xs text-muted-foreground">
                                        {{ client.contact_email ?? 'No email' }}
                                    </p>
                                </div>
                            </div>

                            <div class="flex items-center gap-4 text-xs text-muted-foreground">
                                <span class="flex items-center gap-1">
                                    <FolderOpen class="size-3" />
                                    {{ client.knowledge_sources_count }} sources
                                </span>
                                <span class="flex items-center gap-1">
                                    <Zap class="size-3" />
                                    {{ client.current_month_tokens.toLocaleString() }} / {{ client.monthly_token_limit.toLocaleString() }}
                                </span>
                            </div>

                            <div class="h-1.5 w-full overflow-hidden rounded-full bg-muted">
                                <div
                                    class="h-full rounded-full transition-all"
                                    :class="usageColor(usagePercent(client.current_month_tokens, client.monthly_token_limit))"
                                    :style="{ width: `${usagePercent(client.current_month_tokens, client.monthly_token_limit)}%` }"
                                />
                            </div>

                            <div class="flex gap-2">
                                <Button size="sm" as-child class="flex-1">
                                    <Link :href="`/clients/${client.id}`">Open workspace</Link>
                                </Button>
                                <Button variant="outline" size="sm" as-child>
                                    <Link :href="`/clients/${client.id}/edit`">
                                        <Pencil class="size-3.5" />
                                    </Link>
                                </Button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
