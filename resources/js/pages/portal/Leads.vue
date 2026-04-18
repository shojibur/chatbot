<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ChevronDown, Eye, Phone, Search, Trash2 } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';
import ConfirmDeleteDialog from '@/components/ConfirmDeleteDialog.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';

type LeadRow = {
    id: number;
    name: string;
    contact: string;
    user_request: string | null;
    trigger: string;
    status: string;
    notes: string | null;
    created_at: string;
};

type PaginatedLeads = {
    data: LeadRow[];
    current_page: number;
    per_page: number;
    next_page_url: string | null;
    prev_page_url: string | null;
    total: number;
};

const props = defineProps<{
    leads: PaginatedLeads;
    filters: { status?: string };
    status?: string;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/portal/dashboard' },
    { title: 'Leads', href: '/portal/leads' },
];

const filterStatus = ref(props.filters.status || 'all');

watch(filterStatus, () => {
    const params: Record<string, string> = {};
    if (filterStatus.value !== 'all') params.status = filterStatus.value;
    router.get('/portal/leads', params, { preserveState: true, replace: true });
});

// ── Status update ─────────────────────────────────────────────────────────────
const updatingId = ref<number | null>(null);

function updateStatus(lead: LeadRow, status: string) {
    if (lead.status === status) return;
    updatingId.value = lead.id;
    router.patch(`/portal/leads/${lead.id}/status`, { status }, {
        preserveScroll: true,
        onFinish: () => (updatingId.value = null),
    });
}

// ── Delete ────────────────────────────────────────────────────────────────────
const deleteTarget = ref<LeadRow | null>(null);
const deleting = ref(false);

function confirmDelete(lead: LeadRow) {
    deleteTarget.value = lead;
}

function executeDelete() {
    if (!deleteTarget.value) return;
    deleting.value = true;
    router.delete(`/portal/leads/${deleteTarget.value.id}`, {
        preserveScroll: true,
        onFinish: () => {
            deleting.value = false;
            deleteTarget.value = null;
        },
    });
}

// ── Helpers ───────────────────────────────────────────────────────────────────
const statusMessage = computed(() => {
    if (props.status === 'lead-deleted') return 'Lead removed successfully.';
    return null;
});

function fmt(v: string) {
    const d = new Date(v.endsWith('Z') ? v : v + 'Z');
    return d.toLocaleDateString(undefined, { year: 'numeric', month: 'short', day: 'numeric' });
}

const statusOptions = ['new', 'contacted', 'closed'] as const;

function statusBadgeClass(s: string) {
    if (s === 'new') return 'border-amber-300 bg-amber-50 text-amber-700 dark:border-amber-700 dark:bg-amber-900/20 dark:text-amber-400';
    if (s === 'contacted') return 'border-blue-300 bg-blue-50 text-blue-700 dark:border-blue-700 dark:bg-blue-900/20 dark:text-blue-400';
    return 'border-sidebar-border/60 bg-muted/50 text-muted-foreground';
}
</script>

<template>
    <Head title="Leads" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-1 flex-col gap-6 p-4 md:p-6">

            <!-- Header -->
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h1 class="text-2xl font-bold tracking-tight">Leads</h1>
                    <p class="mt-1 text-sm text-muted-foreground">
                        {{ leads.total }} contact{{ leads.total !== 1 ? 's' : '' }} captured from your chatbot.
                    </p>
                </div>
                <!-- Status filter -->
                <div class="relative w-full sm:w-40">
                    <select
                        v-model="filterStatus"
                        class="h-10 w-full appearance-none rounded-md border border-input bg-transparent pl-3 pr-8 py-2 text-sm outline-none focus:ring-1 focus:ring-ring transition-colors"
                    >
                        <option value="all">All Statuses</option>
                        <option value="new">New</option>
                        <option value="contacted">Contacted</option>
                        <option value="closed">Closed</option>
                    </select>
                    <ChevronDown class="pointer-events-none absolute right-2.5 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground/60" />
                </div>
            </div>

            <!-- Status banner -->
            <div v-if="statusMessage" class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700 dark:border-emerald-900/30 dark:bg-emerald-900/10 dark:text-emerald-400">
                {{ statusMessage }}
            </div>

            <!-- Empty state -->
            <div
                v-if="leads.data.length === 0"
                class="flex flex-col items-center justify-center rounded-xl border border-dashed border-sidebar-border/60 py-20 text-center"
            >
                <div class="mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-muted">
                    <Search class="h-6 w-6 text-muted-foreground/40" />
                </div>
                <p class="text-sm font-medium text-muted-foreground">No leads found</p>
                <p class="mt-1 text-xs text-muted-foreground/70">When visitors submit their contact details through your chatbot they'll appear here.</p>
            </div>

            <!-- Table -->
            <div v-else class="overflow-hidden rounded-xl border border-sidebar-border/60 bg-card shadow-sm">
                <!-- Column headers -->
                <div class="hidden border-b border-sidebar-border/60 bg-muted/20 px-6 py-2.5 text-[10px] font-bold tracking-widest text-muted-foreground uppercase md:grid md:grid-cols-[1.5fr_1fr_2fr_0.8fr_1.2fr_auto]">
                    <span>Contact</span>
                    <span>Type</span>
                    <span>Enquiry</span>
                    <span>Date</span>
                    <span>Status</span>
                    <span class="w-20 text-center">Actions</span>
                </div>

                <!-- Rows -->
                <div class="divide-y divide-sidebar-border/40">
                    <div
                        v-for="lead in leads.data"
                        :key="lead.id"
                        class="group grid grid-cols-1 items-center gap-4 px-6 py-4 transition-colors hover:bg-muted/10 md:grid-cols-[1.5fr_1fr_2fr_0.8fr_1.2fr_auto]"
                    >
                        <!-- Name + contact -->
                        <div class="min-w-0">
                            <p class="truncate text-sm font-semibold">{{ lead.name }}</p>
                            <p class="mt-0.5 flex items-center gap-1 truncate text-xs text-muted-foreground">
                                <Phone class="h-2.5 w-2.5 shrink-0" />
                                {{ lead.contact }}
                            </p>
                        </div>

                        <!-- Trigger badge -->
                        <div>
                            <Badge variant="outline" class="text-[9px] h-5 px-1.5 font-mono uppercase tracking-widest text-muted-foreground">
                                {{ lead.trigger }}
                            </Badge>
                        </div>

                        <!-- Enquiry snippet -->
                        <div class="min-w-0 pr-4">
                            <p v-if="lead.user_request" class="line-clamp-2 text-sm text-muted-foreground">
                                "{{ lead.user_request }}"
                            </p>
                            <p v-else class="text-xs italic text-muted-foreground/50">No request recorded</p>
                        </div>

                        <!-- Date -->
                        <div class="text-xs text-muted-foreground tabular-nums">
                            {{ fmt(lead.created_at) }}
                        </div>

                        <!-- Status dropdown -->
                        <div class="relative">
                            <select
                                :value="lead.status"
                                :disabled="updatingId === lead.id"
                                class="h-8 w-full appearance-none rounded-md border pl-2.5 pr-6 text-[11px] font-bold uppercase tracking-wider outline-none transition-colors focus:ring-1 focus:ring-ring disabled:opacity-50 cursor-pointer"
                                :class="statusBadgeClass(lead.status)"
                                @change="updateStatus(lead, ($event.target as HTMLSelectElement).value)"
                            >
                                <option v-for="s in statusOptions" :key="s" :value="s" class="bg-background text-foreground font-normal normal-case tracking-normal">
                                    {{ s.charAt(0).toUpperCase() + s.slice(1) }}
                                </option>
                            </select>
                            <ChevronDown class="pointer-events-none absolute right-1.5 top-1/2 h-3 w-3 -translate-y-1/2 text-current opacity-60" />
                        </div>

                        <!-- Actions: View + Delete -->
                        <div class="flex items-center gap-1">
                            <Button
                                variant="ghost"
                                size="icon"
                                as-child
                                class="h-8 w-8"
                            >
                                <Link :href="`/portal/leads/${lead.id}`">
                                    <Eye class="h-3.5 w-3.5" />
                                </Link>
                            </Button>
                            <Button
                                variant="ghost"
                                size="icon"
                                class="h-8 w-8 opacity-0 transition-opacity group-hover:opacity-100 text-muted-foreground hover:bg-red-50 hover:text-red-600 dark:hover:bg-red-900/20"
                                @click="confirmDelete(lead)"
                            >
                                <Trash2 class="h-3.5 w-3.5" />
                            </Button>
                        </div>
                    </div>
                </div>

                <!-- Pagination -->
                <div v-if="leads.prev_page_url || leads.next_page_url" class="flex items-center justify-between border-t border-sidebar-border/60 px-4 py-3">
                    <p class="text-xs text-muted-foreground">Page {{ leads.current_page }}</p>
                    <div class="flex gap-2">
                        <Button :disabled="!leads.prev_page_url" variant="outline" size="sm" as-child>
                            <Link v-if="leads.prev_page_url" :href="leads.prev_page_url" preserve-scroll>← Previous</Link>
                            <span v-else>← Previous</span>
                        </Button>
                        <Button :disabled="!leads.next_page_url" variant="outline" size="sm" as-child>
                            <Link v-if="leads.next_page_url" :href="leads.next_page_url" preserve-scroll>Next →</Link>
                            <span v-else>Next →</span>
                        </Button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Delete confirm dialog -->
        <ConfirmDeleteDialog
            :open="!!deleteTarget"
            title="Remove Lead"
            :description="`Are you sure you want to permanently delete the lead for '${deleteTarget?.name}'? This cannot be undone.`"
            :processing="deleting"
            @close="deleteTarget = null"
            @confirm="executeDelete"
        />
    </AppLayout>
</template>
