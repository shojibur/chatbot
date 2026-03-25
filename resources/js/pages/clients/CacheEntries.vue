<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ArrowLeft } from 'lucide-vue-next';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader } from '@/components/ui/card';
import AppLayout from '@/layouts/AppLayout.vue';
import { dashboard } from '@/routes';
import type { BreadcrumbItem, ClientWorkspace, CacheEntryRecord } from '@/types';

type PaginatedEntries = {
    data: CacheEntryRecord[];
    current_page: number;
    per_page: number;
    next_page_url: string | null;
    prev_page_url: string | null;
};

type Props = {
    client: ClientWorkspace;
    entries: PaginatedEntries;
};

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: dashboard() },
    { title: 'Clients', href: '/clients' },
    { title: props.client.name, href: `/clients/${props.client.id}` },
    { title: 'Cache Entries', href: `/clients/${props.client.id}/cache-entries` },
];

function formatDate(value?: string | null): string {
    if (!value) {
        return '--';
    }

    const dateStr = value.endsWith('Z') ? value : value + 'Z';

    return new Date(dateStr).toLocaleDateString(undefined, {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    });
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
    <Head :title="`Cache Entries - ${client.name}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-1 flex-col gap-6 p-4 md:p-6">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h1 class="text-2xl font-semibold tracking-tight">Cache Entries</h1>
                    <p class="mt-1 text-sm text-muted-foreground">
                        Cached responses for repeated questions.
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
                    <h2 class="text-lg font-semibold">Answer cache</h2>
                </CardHeader>
                <CardContent class="p-0">
                    <div v-if="entries.data.length === 0" class="p-6 text-center text-sm text-muted-foreground">
                        No cache entries yet.
                    </div>
                    <div v-else class="divide-y divide-sidebar-border/70">
                        <div
                            v-for="entry in entries.data"
                            :key="entry.id"
                            class="px-4 py-3"
                        >
                            <p class="text-sm font-medium">{{ entry.question }}</p>
                            <p class="mt-1 line-clamp-2 text-xs text-muted-foreground">{{ entry.answer }}</p>
                            <div class="mt-2 flex flex-wrap items-center gap-3 text-xs text-muted-foreground">
                                <span>{{ entry.hit_count }} hits</span>
                                <span>{{ entry.total_tokens_saved.toLocaleString() }} tokens saved</span>
                                <span v-if="entry.expires_at">Expires {{ formatDate(entry.expires_at) }}</span>
                                <span>Cached on {{ formatDateTime(entry.created_at) }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Pagination -->
                    <div
                        v-if="entries.prev_page_url || entries.next_page_url"
                        class="flex items-center justify-between border-t border-sidebar-border/70 px-4 py-3"
                    >
                        <p class="text-sm text-muted-foreground">
                            Page {{ entries.current_page }}
                        </p>
                        <div class="flex gap-2">
                            <Button
                                v-if="entries.prev_page_url"
                                variant="outline"
                                size="sm"
                                as-child
                            >
                                <Link :href="entries.prev_page_url" preserve-scroll>Previous</Link>
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
                                v-if="entries.next_page_url"
                                variant="outline"
                                size="sm"
                                as-child
                            >
                                <Link :href="entries.next_page_url" preserve-scroll>Next</Link>
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
