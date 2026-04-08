<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ArrowLeft, Trash2 } from 'lucide-vue-next';
import { ref } from 'vue';
import ConfirmDeleteDialog from '@/components/ConfirmDeleteDialog.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader } from '@/components/ui/card';
import AppLayout from '@/layouts/AppLayout.vue';
import { dashboard } from '@/routes';
import type {
    BreadcrumbItem,
    ClientWorkspace,
    CacheEntryRecord,
} from '@/types';

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
    {
        title: 'Cache Entries',
        href: `/clients/${props.client.id}/cache-entries`,
    },
];

// Delete single entry
const deleteTarget = ref<CacheEntryRecord | null>(null);
const deleting = ref(false);

function confirmDelete(entry: CacheEntryRecord) {
    deleteTarget.value = entry;
}

function executeDelete() {
    if (!deleteTarget.value) return;
    deleting.value = true;
    router.delete(
        `/clients/${props.client.id}/cache-entries/${deleteTarget.value.id}`,
        {
            preserveScroll: true,
            onFinish: () => {
                deleting.value = false;
                deleteTarget.value = null;
            },
        },
    );
}

// Clear all cache
const showClearAll = ref(false);
const clearing = ref(false);

function executeClearAll() {
    clearing.value = true;
    router.delete(`/clients/${props.client.id}/cache-entries`, {
        preserveScroll: true,
        onFinish: () => {
            clearing.value = false;
            showClearAll.value = false;
        },
    });
}

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
            <div
                class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
            >
                <div>
                    <h1 class="text-2xl font-semibold tracking-tight">
                        Cache Entries
                    </h1>
                    <p class="mt-1 text-sm text-muted-foreground">
                        Cached responses for repeated questions. Remove entries
                        when answers are incorrect or content has been updated.
                    </p>
                </div>
                <div class="flex items-center gap-2">
                    <Button
                        v-if="entries.data.length > 0"
                        variant="destructive"
                        size="sm"
                        @click="showClearAll = true"
                    >
                        <Trash2 class="mr-1 size-3.5" />
                        Clear all cache
                    </Button>
                    <Button variant="outline" size="sm" as-child>
                        <Link :href="`/clients/${client.id}`">
                            <ArrowLeft class="mr-1 size-3.5" />
                            Back to client
                        </Link>
                    </Button>
                </div>
            </div>

            <Card class="gap-0 border-sidebar-border/70">
                <CardHeader class="border-b border-sidebar-border/70">
                    <h2 class="text-lg font-semibold">Answer cache</h2>
                </CardHeader>
                <CardContent class="p-0">
                    <div
                        v-if="entries.data.length === 0"
                        class="p-6 text-center text-sm text-muted-foreground"
                    >
                        No cache entries yet.
                    </div>
                    <div v-else class="divide-y divide-sidebar-border/70">
                        <div
                            v-for="entry in entries.data"
                            :key="entry.id"
                            class="group px-4 py-3"
                        >
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0 flex-1">
                                    <p class="text-sm font-medium">
                                        {{ entry.question }}
                                    </p>
                                    <p
                                        class="mt-1 line-clamp-2 text-xs text-muted-foreground"
                                    >
                                        {{ entry.answer }}
                                    </p>
                                </div>
                                <Button
                                    variant="ghost"
                                    size="sm"
                                    class="h-7 w-7 shrink-0 p-0 text-muted-foreground opacity-0 transition-opacity hover:text-red-600 group-hover:opacity-100"
                                    @click="confirmDelete(entry)"
                                    title="Remove this cached answer"
                                >
                                    <Trash2 class="size-3.5" />
                                </Button>
                            </div>
                            <div
                                class="mt-2 flex flex-wrap items-center gap-3 text-xs text-muted-foreground"
                            >
                                <span>{{ entry.hit_count }} hits</span>
                                <span
                                    >{{
                                        entry.total_tokens_saved.toLocaleString()
                                    }}
                                    tokens saved</span
                                >
                                <span v-if="entry.expires_at"
                                    >Expires
                                    {{ formatDate(entry.expires_at) }}</span
                                >
                                <span
                                    >Cached on
                                    {{ formatDateTime(entry.created_at) }}</span
                                >
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
                                <Link
                                    :href="entries.prev_page_url"
                                    preserve-scroll
                                    >Previous</Link
                                >
                            </Button>
                            <Button v-else variant="outline" size="sm" disabled>
                                Previous
                            </Button>
                            <Button
                                v-if="entries.next_page_url"
                                variant="outline"
                                size="sm"
                                as-child
                            >
                                <Link
                                    :href="entries.next_page_url"
                                    preserve-scroll
                                    >Next</Link
                                >
                            </Button>
                            <Button v-else variant="outline" size="sm" disabled>
                                Next
                            </Button>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>

        <!-- Delete single entry dialog -->
        <ConfirmDeleteDialog
            :open="!!deleteTarget"
            title="Remove cached answer"
            description="This cached response will be removed. The next time this question is asked, a fresh answer will be generated from your knowledge base."
            :processing="deleting"
            @close="deleteTarget = null"
            @confirm="executeDelete"
        />

        <!-- Clear all cache dialog -->
        <ConfirmDeleteDialog
            :open="showClearAll"
            title="Clear all cached answers"
            description="All cached responses for this client will be permanently removed. New answers will be generated fresh from the knowledge base. This is useful after updating your content."
            :processing="clearing"
            @close="showClearAll = false"
            @confirm="executeClearAll"
        />
    </AppLayout>
</template>
