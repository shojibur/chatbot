<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ArrowLeft } from 'lucide-vue-next';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader } from '@/components/ui/card';
import AppLayout from '@/layouts/AppLayout.vue';
import { dashboard } from '@/routes';
import type { BreadcrumbItem, ClientWorkspace, UsageLogRecord } from '@/types';

type PaginatedLogs = {
    data: UsageLogRecord[];
    current_page: number;
    per_page: number;
    next_page_url: string | null;
    prev_page_url: string | null;
};

type Props = {
    client: ClientWorkspace;
    logs: PaginatedLogs;
};

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: dashboard() },
    { title: 'Clients', href: '/clients' },
    { title: props.client.name, href: `/clients/${props.client.id}` },
    { title: 'Usage Logs', href: `/clients/${props.client.id}/usage-logs` },
];

function formatCurrency(value: number): string {
    return `$${value.toFixed(4)}`;
}

function formatDateTime(value?: string | null): string {
    if (!value) {
        return '--';
    }

    const dateStr = value.endsWith('Z') ? value : value + 'Z';

    return new Date(dateStr).toLocaleString(undefined, {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
}
</script>

<template>
    <Head :title="`Usage Logs - ${client.name}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-1 flex-col gap-6 p-4 md:p-6">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h1 class="text-2xl font-semibold tracking-tight">Usage Logs</h1>
                    <p class="mt-1 text-sm text-muted-foreground">
                        Detailed API activity for {{ client.name }}.
                    </p>
                </div>
                <Button variant="outline" size="sm" as-child>
                    <Link :href="`/clients/${client.id}`">
                        <ArrowLeft class="mr-1 size-3.5" />
                        Back to client
                    </Link>
                </Button>
            </div>

            <Card class="gap-0 border-sidebar-border/70">
                <CardHeader class="border-b border-sidebar-border/70">
                    <h2 class="text-lg font-semibold">Usage history</h2>
                </CardHeader>
                <CardContent class="p-0">
                    <div v-if="logs.data.length === 0" class="p-6 text-center text-sm text-muted-foreground">
                        No usage logs yet.
                    </div>
                    <div v-else class="divide-y divide-sidebar-border/70">
                        <div
                            v-for="log in logs.data"
                            :key="log.id"
                            class="flex items-center justify-between gap-4 px-4 py-3"
                        >
                            <div class="min-w-0">
                                <p class="truncate text-sm">{{ log.request_excerpt ?? 'No excerpt' }}</p>
                                <div class="mt-0.5 flex flex-wrap items-center gap-2 text-xs text-muted-foreground">
                                    <Badge :variant="log.interaction_type === 'cache_hit' ? 'secondary' : 'outline'" class="text-[10px]">
                                        {{ log.interaction_type }}
                                    </Badge>
                                    <span>{{ log.model ?? 'n/a' }}</span>
                                    <span>{{ formatDateTime(log.created_at) }}</span>
                                </div>
                            </div>
                            <div class="shrink-0 text-right">
                                <p class="text-sm tabular-nums">{{ log.total_tokens.toLocaleString() }} tokens</p>
                                <p class="text-xs text-muted-foreground">{{ formatCurrency(log.estimated_cost) }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Pagination -->
                    <div
                        v-if="logs.prev_page_url || logs.next_page_url"
                        class="flex items-center justify-between border-t border-sidebar-border/70 px-4 py-3"
                    >
                        <p class="text-sm text-muted-foreground">
                            Page {{ logs.current_page }}
                        </p>
                        <div class="flex gap-2">
                            <Button
                                v-if="logs.prev_page_url"
                                variant="outline"
                                size="sm"
                                as-child
                            >
                                <Link :href="logs.prev_page_url" preserve-scroll>Previous</Link>
                            </Button>
                            <Button
                                v-else
                                variant="outline"
                                size="sm"
                                disabled
                            >
                                Previous
                            </Button>
                            <Button
                                v-if="logs.next_page_url"
                                variant="outline"
                                size="sm"
                                as-child
                            >
                                <Link :href="logs.next_page_url" preserve-scroll>Next</Link>
                            </Button>
                            <Button
                                v-else
                                variant="outline"
                                size="sm"
                                disabled
                            >
                                Next
                            </Button>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
