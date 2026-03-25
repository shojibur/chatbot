<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import {
    ArrowUpRight,
    FolderOpen,
    Pencil,
    Plus,
    Search,
    Trash2,
    Zap,
} from 'lucide-vue-next';
import { computed, ref } from 'vue';
import ConfirmDeleteDialog from '@/components/ConfirmDeleteDialog.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
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
    { title: 'Dashboard', href: dashboard() },
    { title: 'Clients', href: '/clients' },
];

// Search & filter
const search = ref('');
const statusFilter = ref('all');

const filteredClients = computed(() => {
    let result = props.clients;

    if (search.value) {
        const q = search.value.toLowerCase();
        result = result.filter(
            (c) =>
                c.name.toLowerCase().includes(q) ||
                (c.contact_email?.toLowerCase().includes(q) ?? false) ||
                (c.plan?.name.toLowerCase().includes(q) ?? false),
        );
    }

    if (statusFilter.value !== 'all') {
        result = result.filter((c) => c.status === statusFilter.value);
    }

    return result;
});

// Delete
const deleteTarget = ref<ClientListItem | null>(null);
const deleting = ref(false);

function confirmDelete(client: ClientListItem) {
    deleteTarget.value = client;
}

function executeDelete() {
    if (!deleteTarget.value) return;
    deleting.value = true;
    router.delete(`/clients/${deleteTarget.value.id}`, {
        onFinish: () => {
            deleting.value = false;
            deleteTarget.value = null;
        },
    });
}

const statusLabels: Record<string, string> = {
    draft: 'Draft',
    active: 'Active',
    paused: 'Paused',
};

const statusMessage = computed(() => {
    if (props.status === 'client-created')
        return 'Client created and ready for knowledge base setup.';
    if (props.status === 'client-updated')
        return 'Client configuration updated.';
    if (props.status === 'client-deleted')
        return 'Client deleted successfully.';
    if (props.status === 'knowledge-source-created')
        return 'Knowledge source saved for processing.';
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
            <div
                class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
            >
                <div>
                    <h1 class="text-2xl font-semibold tracking-tight">
                        Clients
                    </h1>
                    <p class="text-sm text-muted-foreground">
                        {{ summary.total_clients }} total &middot;
                        {{ summary.active_clients }} active &middot;
                        {{ summary.paused_clients }} paused
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

            <!-- Search & filter bar -->
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                <div class="relative flex-1">
                    <Search
                        class="absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground"
                    />
                    <Input
                        v-model="search"
                        placeholder="Search by name, email, or plan..."
                        class="pl-9"
                    />
                </div>
                <select
                    v-model="statusFilter"
                    class="h-10 rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs transition-[color,box-shadow] outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                >
                    <option value="all">All statuses</option>
                    <option value="active">Active</option>
                    <option value="draft">Draft</option>
                    <option value="paused">Paused</option>
                </select>
            </div>

            <!-- Empty state -->
            <div
                v-if="clients.length === 0"
                class="flex flex-col items-center justify-center rounded-xl border border-dashed border-sidebar-border/70 py-16 text-center"
            >
                <div
                    class="rounded-lg border border-sidebar-border/70 bg-accent/30 p-3"
                >
                    <Plus class="size-6 text-muted-foreground" />
                </div>
                <h3 class="mt-4 text-sm font-medium">No clients yet</h3>
                <p class="mt-1 text-sm text-muted-foreground">
                    Create your first client to get started.
                </p>
                <Button as-child class="mt-4" size="sm">
                    <Link href="/clients/create">
                        <Plus class="mr-2 size-4" />
                        New client
                    </Link>
                </Button>
            </div>

            <!-- No search results -->
            <div
                v-else-if="filteredClients.length === 0"
                class="rounded-xl border border-dashed border-sidebar-border/70 py-12 text-center text-sm text-muted-foreground"
            >
                No clients match your search.
            </div>

            <!-- Client table/list -->
            <div
                v-else
                class="overflow-hidden rounded-xl border border-sidebar-border/70"
            >
                <!-- Table header -->
                <div
                    class="hidden border-b border-sidebar-border/70 bg-muted/30 px-6 py-3 text-xs font-medium tracking-wider text-muted-foreground uppercase md:grid md:grid-cols-[2fr_1fr_1fr_1fr_auto]"
                >
                    <span>Client</span>
                    <span>Plan</span>
                    <span>Sources</span>
                    <span>Usage</span>
                    <span class="w-24 text-right">Actions</span>
                </div>

                <!-- Client rows -->
                <div class="divide-y divide-sidebar-border/70">
                    <div
                        v-for="client in filteredClients"
                        :key="client.id"
                        class="group transition hover:bg-accent/30"
                    >
                        <!-- Desktop row -->
                        <div
                            class="hidden items-center gap-4 px-6 py-4 md:grid md:grid-cols-[2fr_1fr_1fr_1fr_auto]"
                        >
                            <div class="min-w-0">
                                <div class="flex items-center gap-2">
                                    <Link
                                        :href="`/clients/${client.id}`"
                                        class="truncate text-sm font-medium hover:underline"
                                    >
                                        {{ client.name }}
                                    </Link>
                                    <Badge
                                        :variant="badgeVariant(client.status)"
                                        class="shrink-0 text-[10px]"
                                    >
                                        {{
                                            statusLabels[client.status] ??
                                            client.status
                                        }}
                                    </Badge>
                                </div>
                                <p
                                    class="mt-0.5 truncate text-xs text-muted-foreground"
                                >
                                    {{ client.contact_email ?? 'No email' }}
                                </p>
                            </div>

                            <div>
                                <Badge
                                    v-if="client.plan"
                                    variant="outline"
                                    class="text-xs"
                                    >{{ client.plan.name }}</Badge
                                >
                                <span
                                    v-else
                                    class="text-xs text-muted-foreground"
                                    >--</span
                                >
                            </div>

                            <div class="flex items-center gap-1.5 text-sm">
                                <FolderOpen
                                    class="size-3.5 text-muted-foreground"
                                />
                                {{ client.knowledge_sources_count }}
                            </div>

                            <div class="space-y-1">
                                <div
                                    class="flex items-center justify-between text-xs"
                                >
                                    <span>{{
                                        client.current_month_tokens.toLocaleString()
                                    }}</span>
                                    <span class="text-muted-foreground"
                                        >/
                                        {{
                                            client.monthly_token_limit.toLocaleString()
                                        }}</span
                                    >
                                </div>
                                <div
                                    class="h-1.5 w-full overflow-hidden rounded-full bg-muted"
                                >
                                    <div
                                        class="h-full rounded-full transition-all"
                                        :class="
                                            usageColor(
                                                usagePercent(
                                                    client.current_month_tokens,
                                                    client.monthly_token_limit,
                                                ),
                                            )
                                        "
                                        :style="{
                                            width: `${usagePercent(client.current_month_tokens, client.monthly_token_limit)}%`,
                                        }"
                                    />
                                </div>
                            </div>

                            <div class="flex w-24 justify-end gap-1">
                                <Button
                                    variant="ghost"
                                    size="icon"
                                    as-child
                                    class="size-8"
                                >
                                    <Link :href="`/clients/${client.id}`">
                                        <ArrowUpRight class="size-4" />
                                    </Link>
                                </Button>
                                <Button
                                    variant="ghost"
                                    size="icon"
                                    as-child
                                    class="size-8"
                                >
                                    <Link :href="`/clients/${client.id}/edit`">
                                        <Pencil class="size-3.5" />
                                    </Link>
                                </Button>
                                <Button
                                    variant="ghost"
                                    size="icon"
                                    class="size-8 text-muted-foreground hover:text-red-600"
                                    @click="confirmDelete(client)"
                                >
                                    <Trash2 class="size-3.5" />
                                </Button>
                            </div>
                        </div>

                        <!-- Mobile card -->
                        <div class="space-y-3 px-4 py-4 md:hidden">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <div
                                        class="flex flex-wrap items-center gap-2"
                                    >
                                        <Link
                                            :href="`/clients/${client.id}`"
                                            class="text-sm font-medium hover:underline"
                                        >
                                            {{ client.name }}
                                        </Link>
                                        <Badge
                                            :variant="
                                                badgeVariant(client.status)
                                            "
                                            class="text-[10px]"
                                        >
                                            {{
                                                statusLabels[client.status] ??
                                                client.status
                                            }}
                                        </Badge>
                                        <Badge
                                            v-if="client.plan"
                                            variant="outline"
                                            class="text-[10px]"
                                            >{{ client.plan.name }}</Badge
                                        >
                                    </div>
                                    <p
                                        class="mt-0.5 text-xs text-muted-foreground"
                                    >
                                        {{ client.contact_email ?? 'No email' }}
                                    </p>
                                </div>
                            </div>

                            <div
                                class="flex items-center gap-4 text-xs text-muted-foreground"
                            >
                                <span class="flex items-center gap-1">
                                    <FolderOpen class="size-3" />
                                    {{ client.knowledge_sources_count }} sources
                                </span>
                                <span class="flex items-center gap-1">
                                    <Zap class="size-3" />
                                    {{
                                        client.current_month_tokens.toLocaleString()
                                    }}
                                    /
                                    {{
                                        client.monthly_token_limit.toLocaleString()
                                    }}
                                </span>
                            </div>

                            <div
                                class="h-1.5 w-full overflow-hidden rounded-full bg-muted"
                            >
                                <div
                                    class="h-full rounded-full transition-all"
                                    :class="
                                        usageColor(
                                            usagePercent(
                                                client.current_month_tokens,
                                                client.monthly_token_limit,
                                            ),
                                        )
                                    "
                                    :style="{
                                        width: `${usagePercent(client.current_month_tokens, client.monthly_token_limit)}%`,
                                    }"
                                />
                            </div>

                            <div class="flex gap-2">
                                <Button size="sm" as-child class="flex-1">
                                    <Link :href="`/clients/${client.id}`"
                                        >Open workspace</Link
                                    >
                                </Button>
                                <Button variant="outline" size="sm" as-child>
                                    <Link :href="`/clients/${client.id}/edit`">
                                        <Pencil class="size-3.5" />
                                    </Link>
                                </Button>
                                <Button
                                    variant="outline"
                                    size="sm"
                                    class="text-red-600 hover:bg-red-50"
                                    @click="confirmDelete(client)"
                                >
                                    <Trash2 class="size-3.5" />
                                </Button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Delete confirm dialog -->
        <ConfirmDeleteDialog
            :open="!!deleteTarget"
            :title="`Delete ${deleteTarget?.name ?? 'client'}?`"
            description="This will permanently delete the client and all associated knowledge sources, usage logs, and cache entries. This action cannot be undone."
            :processing="deleting"
            @close="deleteTarget = null"
            @confirm="executeDelete"
        />
    </AppLayout>
</template>
