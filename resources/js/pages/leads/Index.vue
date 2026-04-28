<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { Eye, Search, Trash2 } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';
import ConfirmDeleteDialog from '@/components/ConfirmDeleteDialog.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';

type ClientMini = {
    id: number;
    name: string;
};

type LeadRow = {
    id: number;
    client_id: number;
    name: string;
    contact: string;
    user_request: string | null;
    trigger: string;
    status: string;
    created_at: string;
    client: ClientMini;
};

type PaginatedLeads = {
    data: LeadRow[];
    current_page: number;
    per_page: number;
    next_page_url: string | null;
    prev_page_url: string | null;
    total: number;
};

type Props = {
    leads: PaginatedLeads;
    clients: ClientMini[];
    filters: {
        client_id?: string;
        status?: string;
    };
    status?: string;
};

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: dashboard() },
    { title: 'Captured Leads', href: '/leads' },
];

const filterClient = ref(props.filters.client_id || 'all');
const filterStatus = ref(props.filters.status || 'all');
const deleteTarget = ref<LeadRow | null>(null);
const deleting = ref(false);

watch([filterClient, filterStatus], () => {
    const params: Record<string, string> = {};

    if (filterClient.value !== 'all') {
        params.client_id = filterClient.value;
    }

    if (filterStatus.value !== 'all') {
        params.status = filterStatus.value;
    }

    router.get('/leads', params, { preserveState: true, replace: true });
});

function formatDate(value: string): string {
    const dateStr = value.endsWith('Z') ? value : value + 'Z';

    return new Date(dateStr).toLocaleDateString(undefined, {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    });
}

function badgeVariant(status: string): 'default' | 'secondary' | 'outline' {
    if (status === 'new') {
        return 'default';
    }

    if (status === 'contacted') {
        return 'secondary';
    }

    return 'outline';
}

function badgeColor(status: string) {
    if (status === 'new') {
        return 'bg-amber-500 hover:bg-amber-600';
    }

    if (status === 'contacted') {
        return 'bg-blue-500 hover:bg-blue-600 text-white';
    }

    return '';
}

const statusMessage = computed(() => {
    if (props.status === 'lead-deleted') {
        return 'Lead removed successfully.';
    }

    return null;
});

function confirmDelete(lead: LeadRow) {
    deleteTarget.value = lead;
}

function executeDelete() {
    if (!deleteTarget.value) {
        return;
    }

    deleting.value = true;
    router.delete(`/leads/${deleteTarget.value.id}`, {
        preserveScroll: true,
        onFinish: () => {
            deleting.value = false;
            deleteTarget.value = null;
        },
    });
}
</script>

<template>
    <Head title="Captured Leads" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-1 flex-col gap-6 p-4 md:p-6">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h1 class="text-2xl font-semibold tracking-tight">Captured Leads</h1>
                    <p class="mt-1 text-sm text-muted-foreground">
                        {{ leads.total }} total contacts collected from your chatbots.
                    </p>
                </div>
            </div>

            <div v-if="statusMessage" class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700 dark:border-emerald-900/30 dark:bg-emerald-900/10 dark:text-emerald-400">
                {{ statusMessage }}
            </div>

            <!-- Filters -->
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                <select
                    v-model="filterClient"
                    class="h-10 w-full sm:w-64 rounded-md border border-input bg-transparent px-3 py-2 text-sm focus-visible:border-ring focus-visible:ring-2 focus-visible:ring-ring/50 outline-none"
                >
                    <option value="all">All Clients</option>
                    <option v-for="c in clients" :key="c.id" :value="String(c.id)">
                        {{ c.name }}
                    </option>
                </select>

                <select
                    v-model="filterStatus"
                    class="h-10 w-full sm:w-48 rounded-md border border-input bg-transparent px-3 py-2 text-sm focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 outline-none"
                >
                    <option value="all">All Statuses</option>
                    <option value="new">New</option>
                    <option value="contacted">Contacted</option>
                    <option value="closed">Closed</option>
                </select>
            </div>

            <!-- Empty state -->
            <div
                v-if="leads.data.length === 0"
                class="flex flex-col items-center justify-center rounded-xl border border-dashed border-sidebar-border/70 py-16 text-center"
            >
                <div class="rounded-lg border border-sidebar-border/70 bg-accent/30 p-3">
                    <Search class="size-6 text-muted-foreground" />
                </div>
                <h3 class="mt-4 text-sm font-medium">No leads found</h3>
                <p class="mt-1 text-sm text-muted-foreground">
                    Try adjusting your filters or wait for chatbots to capture some leads.
                </p>
            </div>

            <!-- Table -->
            <div v-else class="overflow-hidden rounded-xl border border-sidebar-border/70 bg-card text-card-foreground shadow-sm">
                <div class="hidden border-b border-sidebar-border/70 bg-muted/30 px-6 py-3 text-xs font-medium tracking-wider text-muted-foreground uppercase md:grid md:grid-cols-[1fr_1fr_2fr_auto_auto_auto]">
                    <span>Lead Data</span>
                    <span>Client</span>
                    <span>Trigger Request</span>
                    <span>Status</span>
                    <span class="w-16 text-right">View</span>
                    <span class="w-16 text-right">Delete</span>
                </div>

                <div class="divide-y divide-sidebar-border/70">
                    <div v-for="lead in leads.data" :key="lead.id" class="group transition hover:bg-accent/30">
                        <div class="hidden items-center gap-4 px-6 py-4 md:grid md:grid-cols-[1fr_1fr_2fr_auto_auto_auto]">
                            <div class="min-w-0">
                                <p class="truncate text-sm font-medium">
                                    {{ lead.name }}
                                </p>
                                <p class="mt-0.5 truncate text-xs text-muted-foreground">
                                    {{ lead.contact }}
                                </p>
                            </div>

                            <div class="min-w-0">
                                <Link :href="`/clients/${lead.client_id}`" class="truncate text-sm font-medium hover:underline">
                                    {{ lead.client.name }}
                                </Link>
                                <p class="mt-0.5 truncate text-xs text-muted-foreground">
                                    {{ formatDate(lead.created_at) }}
                                </p>
                            </div>

                            <div class="min-w-0 pr-4">
                                <p class="line-clamp-2 text-sm text-muted-foreground">
                                    "{{ lead.user_request || (lead.trigger === 'no_answer' ? '(Bot couldn\'t answer previous question)' : '(No request recorded)') }}"
                                </p>
                                <Badge variant="outline" class="mt-1 text-[10px] uppercase font-mono tracking-widest text-muted-foreground">
                                    {{ lead.trigger }}
                                </Badge>
                            </div>

                            <div>
                                <Badge :variant="badgeVariant(lead.status)" :class="badgeColor(lead.status)">
                                    {{ lead.status.charAt(0).toUpperCase() + lead.status.slice(1) }}
                                </Badge>
                            </div>

                            <div class="flex w-16 justify-end">
                                <Button variant="ghost" size="icon" as-child class="size-8">
                                    <Link :href="`/leads/${lead.id}`">
                                        <Eye class="size-4" />
                                    </Link>
                                </Button>
                            </div>

                            <div class="flex w-16 justify-end">
                                <Button
                                    variant="ghost"
                                    size="icon"
                                    class="size-8 text-muted-foreground hover:bg-red-50 hover:text-red-600 dark:hover:bg-red-900/20"
                                    @click="confirmDelete(lead)"
                                >
                                    <Trash2 class="size-4" />
                                </Button>
                            </div>
                        </div>

                        <!-- Mobile view -->
                        <div class="flex flex-col gap-3 px-4 py-4 md:hidden">
                            <div class="flex items-start justify-between">
                                <div>
                                    <p class="text-sm font-medium">{{ lead.name }}</p>
                                    <p class="text-xs text-muted-foreground">{{ lead.contact }}</p>
                                </div>
                                <Badge :variant="badgeVariant(lead.status)" :class="badgeColor(lead.status)">
                                    {{ lead.status.charAt(0).toUpperCase() + lead.status.slice(1) }}
                                </Badge>
                            </div>
                            
                            <div class="rounded-md bg-muted/40 p-2 text-xs">
                                <p class="font-medium text-muted-foreground">Request:</p>
                                <p class="mt-0.5 line-clamp-2">"{{ lead.user_request || '(No request)' }}"</p>
                            </div>

                            <div class="flex items-center justify-between text-xs mt-1">
                                <span class="text-muted-foreground">
                                    Client: <span class="font-medium">{{ lead.client.name }}</span>
                                </span>
                                <div class="flex items-center gap-2">
                                    <Button
                                        size="sm"
                                        variant="ghost"
                                        class="text-red-600 hover:bg-red-50 hover:text-red-700 dark:hover:bg-red-900/20"
                                        @click="confirmDelete(lead)"
                                    >
                                        Delete
                                    </Button>
                                    <Button size="sm" variant="outline" as-child>
                                        <Link :href="`/leads/${lead.id}`">Details &rarr;</Link>
                                    </Button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Pagination -->
                <div v-if="leads.prev_page_url || leads.next_page_url" class="flex items-center justify-between border-t border-sidebar-border/70 px-4 py-3">
                    <p class="text-sm text-muted-foreground">
                        Page {{ leads.current_page }}
                    </p>
                    <div class="flex gap-2">
                        <Button v-if="leads.prev_page_url" variant="outline" size="sm" as-child>
                            <Link :href="leads.prev_page_url" preserve-scroll>Previous</Link>
                        </Button>
                        <Button v-else variant="outline" size="sm" disabled>Previous</Button>
                        
                        <Button v-if="leads.next_page_url" variant="outline" size="sm" as-child>
                            <Link :href="leads.next_page_url" preserve-scroll>Next</Link>
                        </Button>
                        <Button v-else variant="outline" size="sm" disabled>Next</Button>
                    </div>
                </div>
            </div>

            <ConfirmDeleteDialog
                :open="!!deleteTarget"
                title="Remove Lead"
                :description="`Are you sure you want to permanently delete the lead for '${deleteTarget?.name}'? This cannot be undone.`"
                :processing="deleting"
                @close="deleteTarget = null"
                @confirm="executeDelete"
            />
        </div>
    </AppLayout>
</template>
