<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import {
    Bot,
    ChevronDown,
    ChevronUp,
    Clock,
    DatabaseZap,
    Eye,
    FileText,
    Globe,
    Link2,
    Loader2,
    MessageSquare,
    Pencil,
    RefreshCw,
    Trash2,
    Upload,
} from 'lucide-vue-next';
import { computed, ref } from 'vue';
import ConfirmDeleteDialog from '@/components/ConfirmDeleteDialog.vue';
import InputError from '@/components/InputError.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import { dashboard } from '@/routes';
import type {
    BreadcrumbItem,
    ClientWorkspace,
    KnowledgeSourceRecord,
    MemorySummary,
    UsageSummary,
} from '@/types';

type KnowledgeSourceForm = {
    title: string;
    source_type: string;
    content: string;
    source_url: string;
    source_file: File | null;
};

type Props = {
    client: ClientWorkspace;
    knowledge_sources: KnowledgeSourceRecord[];
    usage_summary: UsageSummary;
    memory_summary: MemorySummary;
    knowledge_source_types: string[];
    widget_script_url: string;
    status?: string;
};

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: dashboard() },
    { title: 'Clients', href: '/clients' },
    { title: props.client.name, href: `/clients/${props.client.id}` },
];

const form = useForm<KnowledgeSourceForm>({
    title: '',
    source_type: props.knowledge_source_types[0] ?? 'manual',
    content: '',
    source_url: '',
    source_file: null,
});

function submitKnowledgeSource(): void {
    form.post(`/clients/${props.client.id}/knowledge-sources`, {
        preserveScroll: true,
        onSuccess: () => form.reset(),
    });
}

function handleFileChange(event: Event): void {
    const input = event.target as HTMLInputElement;
    form.source_file = input.files?.[0] ?? null;
}

// Knowledge source actions
const deleteSourceTarget = ref<KnowledgeSourceRecord | null>(null);
const deletingSource = ref(false);

function confirmDeleteSource(source: KnowledgeSourceRecord) {
    deleteSourceTarget.value = source;
}

function executeDeleteSource() {
    if (!deleteSourceTarget.value) return;
    deletingSource.value = true;
    router.delete(
        `/clients/${props.client.id}/knowledge-sources/${deleteSourceTarget.value.id}`,
        {
            preserveScroll: true,
            onFinish: () => {
                deletingSource.value = false;
                deleteSourceTarget.value = null;
            },
        },
    );
}

function retrySource(source: KnowledgeSourceRecord) {
    router.post(
        `/clients/${props.client.id}/knowledge-sources/${source.id}/retry`,
        {},
        {
            preserveScroll: true,
        },
    );
}

// Edit manual source
const editingSource = ref<KnowledgeSourceRecord | null>(null);
const editForm = useForm({
    title: '',
    content: '',
});

function startEditSource(source: KnowledgeSourceRecord) {
    editingSource.value = source;
    editForm.title = source.title;
    editForm.content = source.content ?? '';
}

function cancelEditSource() {
    editingSource.value = null;
    editForm.reset();
}

function submitEditSource() {
    if (!editingSource.value) return;
    editForm.patch(
        `/clients/${props.client.id}/knowledge-sources/${editingSource.value.id}`,
        {
            preserveScroll: true,
            onSuccess: () => {
                editingSource.value = null;
                editForm.reset();
            },
        },
    );
}

// View chunks
interface ChunkRecord {
    id: number;
    chunk_index: number;
    content: string;
    token_estimate: number;
    character_count: number;
    has_embedding: boolean;
    embedding_model: string | null;
}

const viewingChunksSourceId = ref<number | null>(null);
const chunksData = ref<ChunkRecord[]>([]);
const chunksLoading = ref(false);
const chunksPage = ref(0);
const chunksPerPage = 10;

const visibleChunks = computed(() =>
    chunksData.value.slice(0, (chunksPage.value + 1) * chunksPerPage),
);
const hasMoreChunks = computed(
    () => visibleChunks.value.length < chunksData.value.length,
);

async function toggleViewChunks(source: KnowledgeSourceRecord) {
    if (viewingChunksSourceId.value === source.id) {
        viewingChunksSourceId.value = null;
        chunksData.value = [];
        chunksPage.value = 0;
        return;
    }

    viewingChunksSourceId.value = source.id;
    chunksLoading.value = true;
    chunksPage.value = 0;

    try {
        const res = await fetch(
            `/clients/${props.client.id}/knowledge-sources/${source.id}/chunks`,
            {
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                credentials: 'same-origin',
            },
        );
        const data = await res.json();
        chunksData.value = data.chunks ?? [];
    } catch {
        chunksData.value = [];
    } finally {
        chunksLoading.value = false;
    }
}

// Client delete
const showDeleteClient = ref(false);
const deletingClient = ref(false);

function executeDeleteClient() {
    deletingClient.value = true;
    router.delete(`/clients/${props.client.id}`, {
        onFinish: () => {
            deletingClient.value = false;
            showDeleteClient.value = false;
        },
    });
}

const embedSnippet = computed(
    () =>
        `<script src="${props.widget_script_url}" data-client-code="${props.client.unique_code}" async><\/script>`,
);

function formatCurrency(value: number): string {
    return `$${value.toFixed(4)}`;
}

const statusLabels: Record<string, string> = {
    draft: 'Draft',
    active: 'Active',
    paused: 'Paused',
    ready: 'Ready',
    queued: 'Queued',
    processing: 'Processing',
    failed: 'Failed',
};

const sourceTypeLabels: Record<string, string> = {
    manual: 'Manual',
    url: 'URL',
    file: 'File',
};

const statusMessage = computed(() => {
    if (props.status === 'client-created')
        return 'Client created and ready for knowledge base setup.';
    if (props.status === 'client-updated')
        return 'Client configuration updated.';
    if (props.status === 'knowledge-source-created')
        return 'Knowledge source saved for processing.';
    if (props.status === 'knowledge-source-deleted')
        return 'Knowledge source deleted.';
    if (props.status === 'knowledge-source-updated')
        return 'Knowledge source updated.';
    if (props.status === 'knowledge-source-retrying')
        return 'Knowledge source queued for reprocessing.';
    if (props.status === 'knowledge-source-duplicate')
        return 'This knowledge source already exists.';
    return null;
});

function badgeVariant(status: string): 'default' | 'secondary' | 'outline' {
    if (status === 'active' || status === 'ready') return 'default';
    if (status === 'paused' || status === 'processing') return 'secondary';
    return 'outline';
}
</script>

<template>
    <Head :title="client.name" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-1 flex-col gap-6 p-4 md:p-6">
            <!-- Header -->
            <div
                class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between"
            >
                <div class="min-w-0">
                    <div class="flex flex-wrap items-center gap-2">
                        <h1 class="text-2xl font-semibold tracking-tight">
                            {{ client.name }}
                        </h1>
                        <Badge :variant="badgeVariant(client.status)">
                            {{ statusLabels[client.status] ?? client.status }}
                        </Badge>
                        <Badge v-if="client.plan" variant="outline">
                            {{ client.plan.name }}
                        </Badge>
                    </div>
                    <p class="mt-1 text-sm text-muted-foreground">
                        {{
                            client.business_description ||
                            'No business description yet.'
                        }}
                    </p>
                </div>
                <div class="flex shrink-0 flex-wrap gap-2">
                    <Button
                        as-child
                        size="sm"
                        :style="{ background: '#6366f1' }"
                    >
                        <Link :href="`/clients/${client.id}/playground`">
                            <MessageSquare class="mr-1 size-3.5" />
                            Chat Playground
                        </Link>
                    </Button>
                    <Button as-child size="sm" variant="outline">
                        <Link :href="`/clients/${client.id}/chat-history`">
                            <Clock class="mr-1 size-3.5" />
                            Chat History
                        </Link>
                    </Button>
                    <Button as-child size="sm" variant="outline">
                        <Link :href="`/clients/${client.id}/usage-logs`">
                            <FileText class="mr-1 size-3.5" />
                            Usage Logs
                        </Link>
                    </Button>
                    <Button as-child size="sm" variant="outline">
                        <Link :href="`/clients/${client.id}/cache-entries`">
                            <DatabaseZap class="mr-1 size-3.5" />
                            Cache
                        </Link>
                    </Button>
                    <Button as-child size="sm">
                        <Link :href="`/clients/${client.id}/edit`"
                            >Edit client</Link
                        >
                    </Button>
                    <Button variant="outline" size="sm" as-child>
                        <Link href="/clients">Back to clients</Link>
                    </Button>
                    <Button
                        variant="outline"
                        size="sm"
                        class="text-red-600 hover:bg-red-50"
                        @click="showDeleteClient = true"
                    >
                        <Trash2 class="size-3.5" />
                    </Button>
                </div>
            </div>

            <!-- Status message -->
            <div
                v-if="statusMessage"
                class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700 dark:border-emerald-300/10 dark:bg-emerald-500/10 dark:text-emerald-200"
            >
                {{ statusMessage }}
            </div>

            <!-- Stat cards -->
            <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <Card class="gap-0 border-sidebar-border/70">
                    <CardHeader class="pb-2">
                        <p class="text-xs font-medium text-muted-foreground">
                            Tokens this period
                        </p>
                    </CardHeader>
                    <CardContent>
                        <p class="text-2xl font-semibold tracking-tight">
                            {{
                                usage_summary.current_period_tokens.toLocaleString()
                            }}
                        </p>
                        <p class="mt-0.5 text-xs text-muted-foreground">
                            {{
                                usage_summary.current_period_cached_tokens.toLocaleString()
                            }}
                            cached
                        </p>
                    </CardContent>
                </Card>
                <Card class="gap-0 border-sidebar-border/70">
                    <CardHeader class="pb-2">
                        <p class="text-xs font-medium text-muted-foreground">
                            Estimated cost
                        </p>
                    </CardHeader>
                    <CardContent>
                        <p class="text-2xl font-semibold tracking-tight">
                            {{
                                formatCurrency(
                                    usage_summary.current_period_cost,
                                )
                            }}
                        </p>
                        <p class="mt-0.5 text-xs text-muted-foreground">
                            {{ usage_summary.current_period_requests }} requests
                        </p>
                    </CardContent>
                </Card>
                <Card class="gap-0 border-sidebar-border/70">
                    <CardHeader class="pb-2">
                        <p class="text-xs font-medium text-muted-foreground">
                            Knowledge base
                        </p>
                    </CardHeader>
                    <CardContent>
                        <p class="text-2xl font-semibold tracking-tight">
                            {{ memory_summary.chunk_count.toLocaleString() }}
                            chunks
                        </p>
                        <p class="mt-0.5 text-xs text-muted-foreground">
                            {{ memory_summary.ready_sources }} ready /
                            {{ memory_summary.queued_sources }} queued
                        </p>
                    </CardContent>
                </Card>
                <Card class="gap-0 border-sidebar-border/70">
                    <CardHeader class="pb-2">
                        <p class="text-xs font-medium text-muted-foreground">
                            Answer cache
                        </p>
                    </CardHeader>
                    <CardContent>
                        <p class="text-2xl font-semibold tracking-tight">
                            {{ memory_summary.cache_entries.toLocaleString() }}
                            entries
                        </p>
                        <p class="mt-0.5 text-xs text-muted-foreground">
                            {{ memory_summary.cache_hits }} hits &middot;
                            {{ memory_summary.saved_tokens.toLocaleString() }}
                            tokens saved
                        </p>
                    </CardContent>
                </Card>
            </section>

            <!-- Knowledge base manager -->
            <Card class="gap-0 border-sidebar-border/70">
                <CardHeader class="border-b border-sidebar-border/70">
                    <h2 class="text-lg font-semibold">Knowledge base</h2>
                    <p class="text-sm text-muted-foreground">
                        Add manual content, website pages, or uploaded
                        documents.
                    </p>
                </CardHeader>
                <CardContent class="space-y-6 pt-6">
                    <form
                        class="grid gap-4"
                        @submit.prevent="submitKnowledgeSource"
                    >
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div class="grid gap-2">
                                <Label for="title">Source title</Label>
                                <Input
                                    id="title"
                                    v-model="form.title"
                                    placeholder="Pricing FAQ"
                                />
                                <InputError :message="form.errors.title" />
                            </div>
                            <div class="grid gap-2">
                                <Label for="source_type">Source type</Label>
                                <select
                                    id="source_type"
                                    v-model="form.source_type"
                                    class="h-10 rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs transition-[color,box-shadow] outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                                >
                                    <option
                                        v-for="sourceType in knowledge_source_types"
                                        :key="sourceType"
                                        :value="sourceType"
                                    >
                                        {{
                                            sourceTypeLabels[sourceType] ??
                                            sourceType
                                        }}
                                    </option>
                                </select>
                                <InputError
                                    :message="form.errors.source_type"
                                />
                            </div>
                        </div>

                        <div
                            v-if="form.source_type === 'manual'"
                            class="grid gap-2"
                        >
                            <Label for="content">Manual content</Label>
                            <textarea
                                id="content"
                                v-model="form.content"
                                class="min-h-32 rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs transition-[color,box-shadow] outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                                placeholder="Paste FAQs, support scripts, policy text, or any client-approved knowledge."
                            />
                            <InputError :message="form.errors.content" />
                        </div>

                        <div
                            v-if="form.source_type === 'url'"
                            class="grid gap-2"
                        >
                            <Label for="source_url">Website URL</Label>
                            <Input
                                id="source_url"
                                v-model="form.source_url"
                                placeholder="https://client.test/faq"
                            />
                            <InputError :message="form.errors.source_url" />
                        </div>

                        <div
                            v-if="form.source_type === 'file'"
                            class="grid gap-2"
                        >
                            <Label for="source_file">Upload file</Label>
                            <Input
                                id="source_file"
                                type="file"
                                @input="handleFileChange"
                            />
                            <p class="text-xs text-muted-foreground">
                                Supported: PDF, TXT, DOC, DOCX. Limit:
                                {{ client.plan?.max_file_upload_mb ?? 10 }} MB.
                            </p>
                            <InputError :message="form.errors.source_file" />
                        </div>

                        <div>
                            <Button type="submit" :disabled="form.processing">
                                {{
                                    form.processing
                                        ? 'Saving...'
                                        : 'Add knowledge source'
                                }}
                            </Button>
                        </div>
                    </form>

                    <!-- Source list -->
                    <div
                        v-if="knowledge_sources.length > 0"
                        class="overflow-hidden rounded-lg border border-sidebar-border/70"
                    >
                        <div
                            class="hidden border-b border-sidebar-border/70 bg-muted/30 px-4 py-2.5 text-xs font-medium tracking-wider text-muted-foreground uppercase sm:grid sm:grid-cols-[2fr_1fr_1fr_1fr_auto]"
                        >
                            <span>Source</span>
                            <span>Type</span>
                            <span>Chunks</span>
                            <span>Status</span>
                            <span class="w-20 text-right">Actions</span>
                        </div>
                        <div class="divide-y divide-sidebar-border/70">
                            <div
                                v-for="source in knowledge_sources"
                                :key="source.id"
                                class="px-4 py-3"
                            >
                                <!-- Desktop row -->
                                <div
                                    class="hidden items-center sm:grid sm:grid-cols-[2fr_1fr_1fr_1fr_auto]"
                                >
                                    <div class="min-w-0">
                                        <p class="truncate text-sm font-medium">
                                            {{ source.title }}
                                        </p>
                                        <p
                                            v-if="source.source_url"
                                            class="truncate text-xs text-muted-foreground"
                                        >
                                            {{ source.source_url }}
                                        </p>
                                        <p
                                            v-if="source.file_name"
                                            class="truncate text-xs text-muted-foreground"
                                        >
                                            {{ source.file_name }}
                                        </p>
                                        <p
                                            v-if="source.processing_error"
                                            class="mt-0.5 truncate text-xs text-red-500"
                                        >
                                            {{ source.processing_error }}
                                        </p>
                                    </div>
                                    <Badge
                                        variant="outline"
                                        class="w-fit text-xs"
                                    >
                                        {{
                                            sourceTypeLabels[
                                                source.source_type
                                            ] ?? source.source_type
                                        }}
                                    </Badge>
                                    <p class="text-sm text-muted-foreground">
                                        {{ source.chunk_count }} /
                                        {{
                                            source.token_estimate.toLocaleString()
                                        }}
                                        tokens
                                    </p>
                                    <Badge
                                        :variant="badgeVariant(source.status)"
                                        class="w-fit text-xs"
                                    >
                                        {{
                                            statusLabels[source.status] ??
                                            source.status
                                        }}
                                    </Badge>
                                    <div class="flex w-24 justify-end gap-1">
                                        <button
                                            v-if="source.chunk_count > 0"
                                            @click="toggleViewChunks(source)"
                                            class="inline-flex h-7 w-7 items-center justify-center rounded-md text-muted-foreground transition hover:bg-accent hover:text-foreground"
                                            :title="
                                                viewingChunksSourceId ===
                                                source.id
                                                    ? 'Hide chunks'
                                                    : 'View chunks'
                                            "
                                        >
                                            <ChevronUp
                                                v-if="
                                                    viewingChunksSourceId ===
                                                    source.id
                                                "
                                                class="size-3.5"
                                            />
                                            <Eye v-else class="size-3.5" />
                                        </button>
                                        <button
                                            v-if="
                                                source.source_type === 'manual'
                                            "
                                            @click="startEditSource(source)"
                                            class="inline-flex h-7 w-7 items-center justify-center rounded-md text-muted-foreground transition hover:bg-accent hover:text-foreground"
                                            title="Edit content"
                                        >
                                            <Pencil class="size-3.5" />
                                        </button>
                                        <button
                                            v-if="source.status === 'failed'"
                                            @click="retrySource(source)"
                                            class="inline-flex h-7 w-7 items-center justify-center rounded-md text-muted-foreground transition hover:bg-accent hover:text-foreground"
                                            title="Retry processing"
                                        >
                                            <RefreshCw class="size-3.5" />
                                        </button>
                                        <button
                                            @click="confirmDeleteSource(source)"
                                            class="inline-flex h-7 w-7 items-center justify-center rounded-md text-muted-foreground transition hover:bg-red-50 hover:text-red-600"
                                            title="Delete source"
                                        >
                                            <Trash2 class="size-3.5" />
                                        </button>
                                    </div>
                                </div>
                                <!-- Inline edit form for manual sources -->
                                <div
                                    v-if="
                                        editingSource?.id === source.id &&
                                        source.source_type === 'manual'
                                    "
                                    class="mt-3 space-y-3 rounded-lg border border-sidebar-border/70 bg-muted/20 p-4"
                                >
                                    <div class="grid gap-2">
                                        <Label :for="`edit-title-${source.id}`"
                                            >Title</Label
                                        >
                                        <Input
                                            :id="`edit-title-${source.id}`"
                                            v-model="editForm.title"
                                        />
                                        <InputError
                                            :message="editForm.errors.title"
                                        />
                                    </div>
                                    <div class="grid gap-2">
                                        <Label
                                            :for="`edit-content-${source.id}`"
                                            >Content</Label
                                        >
                                        <textarea
                                            :id="`edit-content-${source.id}`"
                                            v-model="editForm.content"
                                            class="min-h-32 rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs transition-[color,box-shadow] outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                                            placeholder="Edit knowledge content..."
                                        />
                                        <InputError
                                            :message="editForm.errors.content"
                                        />
                                    </div>
                                    <div class="flex gap-2">
                                        <Button
                                            size="sm"
                                            :disabled="editForm.processing"
                                            @click="submitEditSource"
                                        >
                                            {{
                                                editForm.processing
                                                    ? 'Saving...'
                                                    : 'Save changes'
                                            }}
                                        </Button>
                                        <Button
                                            variant="outline"
                                            size="sm"
                                            @click="cancelEditSource"
                                            >Cancel</Button
                                        >
                                    </div>
                                </div>
                                <!-- Chunks viewer -->
                                <div
                                    v-if="viewingChunksSourceId === source.id"
                                    class="mt-3 rounded-lg border border-sidebar-border/70 bg-muted/10"
                                >
                                    <!-- Loading state -->
                                    <div
                                        v-if="chunksLoading"
                                        class="flex items-center justify-center gap-2 p-6 text-sm text-muted-foreground"
                                    >
                                        <Loader2 class="size-4 animate-spin" />
                                        Loading chunks...
                                    </div>
                                    <!-- Empty state -->
                                    <div
                                        v-else-if="chunksData.length === 0"
                                        class="p-6 text-center text-sm text-muted-foreground"
                                    >
                                        No chunks found.
                                    </div>
                                    <!-- Chunks list -->
                                    <template v-else>
                                        <div
                                            class="border-b border-sidebar-border/50 bg-muted/30 px-4 py-2 text-xs font-medium text-muted-foreground"
                                        >
                                            {{ chunksData.length }} chunks
                                            extracted &middot;
                                            {{
                                                chunksData
                                                    .reduce(
                                                        (sum, c) =>
                                                            sum +
                                                            c.token_estimate,
                                                        0,
                                                    )
                                                    .toLocaleString()
                                            }}
                                            tokens &middot;
                                            {{
                                                chunksData.filter(
                                                    (c) => c.has_embedding,
                                                ).length
                                            }}
                                            with embeddings
                                        </div>
                                        <div
                                            class="max-h-[500px] divide-y divide-sidebar-border/50 overflow-y-auto"
                                        >
                                            <div
                                                v-for="chunk in visibleChunks"
                                                :key="chunk.id"
                                                class="px-4 py-3"
                                            >
                                                <div
                                                    class="mb-1.5 flex items-center gap-2"
                                                >
                                                    <Badge
                                                        variant="outline"
                                                        class="h-5 text-[10px] tabular-nums"
                                                    >
                                                        #{{ chunk.chunk_index }}
                                                    </Badge>
                                                    <span
                                                        class="text-[10px] text-muted-foreground tabular-nums"
                                                    >
                                                        {{
                                                            chunk.character_count
                                                        }}
                                                        chars &middot; ~{{
                                                            chunk.token_estimate
                                                        }}
                                                        tokens
                                                    </span>
                                                    <Badge
                                                        :variant="
                                                            chunk.has_embedding
                                                                ? 'default'
                                                                : 'secondary'
                                                        "
                                                        class="h-5 text-[9px]"
                                                    >
                                                        {{
                                                            chunk.has_embedding
                                                                ? 'embedded'
                                                                : 'no vector'
                                                        }}
                                                    </Badge>
                                                </div>
                                                <p
                                                    class="text-xs leading-relaxed whitespace-pre-wrap text-muted-foreground"
                                                >
                                                    {{ chunk.content }}
                                                </p>
                                            </div>
                                        </div>
                                        <div
                                            v-if="hasMoreChunks"
                                            class="border-t border-sidebar-border/50 px-4 py-2.5 text-center"
                                        >
                                            <Button
                                                variant="ghost"
                                                size="sm"
                                                class="h-7 text-xs"
                                                @click="chunksPage++"
                                            >
                                                <ChevronDown
                                                    class="mr-1 size-3"
                                                />
                                                Show more ({{
                                                    chunksData.length -
                                                    visibleChunks.length
                                                }}
                                                remaining)
                                            </Button>
                                        </div>
                                    </template>
                                </div>
                                <!-- Mobile -->
                                <div class="space-y-1.5 sm:hidden">
                                    <div
                                        class="flex flex-wrap items-center gap-2"
                                    >
                                        <p class="text-sm font-medium">
                                            {{ source.title }}
                                        </p>
                                        <Badge
                                            :variant="
                                                badgeVariant(source.status)
                                            "
                                            class="text-[10px]"
                                        >
                                            {{
                                                statusLabels[source.status] ??
                                                source.status
                                            }}
                                        </Badge>
                                        <Badge
                                            variant="outline"
                                            class="text-[10px]"
                                        >
                                            {{
                                                sourceTypeLabels[
                                                    source.source_type
                                                ] ?? source.source_type
                                            }}
                                        </Badge>
                                    </div>
                                    <p class="text-xs text-muted-foreground">
                                        {{ source.chunk_count }} chunks &middot;
                                        {{
                                            source.token_estimate.toLocaleString()
                                        }}
                                        tokens
                                    </p>
                                    <p
                                        v-if="source.processing_error"
                                        class="text-xs text-red-500"
                                    >
                                        {{ source.processing_error }}
                                    </p>
                                    <div class="flex flex-wrap gap-2 pt-1">
                                        <Button
                                            v-if="source.chunk_count > 0"
                                            variant="outline"
                                            size="sm"
                                            class="h-7 text-xs"
                                            @click="toggleViewChunks(source)"
                                        >
                                            <Eye class="mr-1 size-3" />
                                            {{
                                                viewingChunksSourceId ===
                                                source.id
                                                    ? 'Hide'
                                                    : 'View'
                                            }}
                                            chunks
                                        </Button>
                                        <Button
                                            v-if="
                                                source.source_type === 'manual'
                                            "
                                            variant="outline"
                                            size="sm"
                                            class="h-7 text-xs"
                                            @click="startEditSource(source)"
                                        >
                                            <Pencil class="mr-1 size-3" />
                                            Edit
                                        </Button>
                                        <Button
                                            v-if="source.status === 'failed'"
                                            variant="outline"
                                            size="sm"
                                            class="h-7 text-xs"
                                            @click="retrySource(source)"
                                        >
                                            <RefreshCw class="mr-1 size-3" />
                                            Retry
                                        </Button>
                                        <Button
                                            variant="outline"
                                            size="sm"
                                            class="h-7 text-xs text-red-600 hover:bg-red-50"
                                            @click="confirmDeleteSource(source)"
                                        >
                                            <Trash2 class="mr-1 size-3" />
                                            Delete
                                        </Button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div
                        v-else
                        class="rounded-lg border border-dashed border-sidebar-border/70 px-6 py-8 text-center text-sm text-muted-foreground"
                    >
                        No knowledge sources yet. Add manual text, a URL, or an
                        upload above.
                    </div>
                </CardContent>
            </Card>

            <!-- Config section -->
            <section class="grid gap-6">
                <div class="grid gap-6">
                    <!-- Config summary -->
                    <Card class="gap-0 border-sidebar-border/70">
                        <CardHeader class="border-b border-sidebar-border/70">
                            <h2 class="text-lg font-semibold">Configuration</h2>
                        </CardHeader>
                        <CardContent class="space-y-4 pt-6 text-sm">
                            <!-- Install snippet -->
                            <div>
                                <div
                                    class="flex items-center gap-2 text-xs font-medium tracking-wider text-muted-foreground uppercase"
                                >
                                    <Bot class="size-3.5" />
                                    Install snippet
                                </div>
                                <pre
                                    class="mt-2 overflow-x-auto rounded-lg bg-slate-950 px-3 py-2.5 text-xs text-slate-100"
                                ><code>{{ embedSnippet }}</code></pre>
                            </div>

                            <!-- Package + AI config -->
                            <div class="grid gap-4 sm:grid-cols-2">
                                <div>
                                    <div
                                        class="flex items-center gap-2 text-xs font-medium tracking-wider text-muted-foreground uppercase"
                                    >
                                        <Globe class="size-3.5" />
                                        Package
                                    </div>
                                    <div
                                        class="mt-2 grid gap-1 text-muted-foreground"
                                    >
                                        <p>
                                            {{ client.plan?.name ?? 'None' }}
                                            <span class="text-foreground"
                                                >${{
                                                    client.plan?.price_monthly?.toFixed(
                                                        2,
                                                    ) ?? '0.00'
                                                }}/mo</span
                                            >
                                        </p>
                                        <p>
                                            Token cap:
                                            <span class="text-foreground">{{
                                                client.monthly_token_limit.toLocaleString()
                                            }}</span>
                                        </p>
                                        <p>
                                            Knowledge limit:
                                            <span class="text-foreground"
                                                >{{
                                                    client.plan
                                                        ?.max_knowledge_sources ??
                                                    0
                                                }}
                                                sources</span
                                            >
                                        </p>
                                    </div>
                                </div>
                                <div>
                                    <div
                                        class="flex items-center gap-2 text-xs font-medium tracking-wider text-muted-foreground uppercase"
                                    >
                                        <Link2 class="size-3.5" />
                                        AI settings
                                    </div>
                                    <div
                                        class="mt-2 grid gap-1 text-muted-foreground"
                                    >
                                        <p>
                                            Chat:
                                            <span class="text-foreground">{{
                                                client.chat_model
                                            }}</span>
                                        </p>
                                        <p>
                                            Embed:
                                            <span class="text-foreground">{{
                                                client.embedding_model
                                            }}</span>
                                        </p>
                                        <p>
                                            Chunks:
                                            <span class="text-foreground">{{
                                                client.retrieval_chunk_count
                                            }}</span>
                                        </p>
                                        <p>
                                            Prompt cache:
                                            <span class="text-foreground">{{
                                                client.prompt_caching_enabled
                                                    ? 'On'
                                                    : 'Off'
                                            }}</span>
                                        </p>
                                        <p>
                                            Semantic cache:
                                            <span class="text-foreground">{{
                                                client.semantic_cache_enabled
                                                    ? `${client.cache_ttl_hours}h TTL`
                                                    : 'Off'
                                            }}</span>
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <!-- System prompt -->
                            <div>
                                <div
                                    class="flex items-center gap-2 text-xs font-medium tracking-wider text-muted-foreground uppercase"
                                >
                                    <FileText class="size-3.5" />
                                    System prompt
                                </div>
                                <p
                                    class="mt-2 whitespace-pre-wrap text-muted-foreground"
                                >
                                    {{
                                        client.system_prompt ||
                                        'No system prompt configured.'
                                    }}
                                </p>
                            </div>

                            <!-- Allowed domains -->
                            <div v-if="client.allowed_domains.length > 0">
                                <div
                                    class="flex items-center gap-2 text-xs font-medium tracking-wider text-muted-foreground uppercase"
                                >
                                    <Upload class="size-3.5" />
                                    Allowed domains
                                </div>
                                <div class="mt-2 flex flex-wrap gap-1.5">
                                    <Badge
                                        v-for="domain in client.allowed_domains"
                                        :key="domain"
                                        variant="outline"
                                        class="text-xs"
                                    >
                                        {{ domain }}
                                    </Badge>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </section>
        </div>

        <!-- Delete source dialog -->
        <ConfirmDeleteDialog
            :open="!!deleteSourceTarget"
            :title="`Delete '${deleteSourceTarget?.title ?? 'source'}'?`"
            description="This will permanently delete the knowledge source and all its chunks. This action cannot be undone."
            :processing="deletingSource"
            @close="deleteSourceTarget = null"
            @confirm="executeDeleteSource"
        />

        <!-- Delete client dialog -->
        <ConfirmDeleteDialog
            :open="showDeleteClient"
            :title="`Delete ${client.name}?`"
            description="This will schedule the client for deletion in 7 days. Knowledge sources and API access will be deactivated immediately, and all data will be permanently wiped unconditionally after 7 days."
            :processing="deletingClient"
            @close="showDeleteClient = false"
            @confirm="executeDeleteClient"
        />
    </AppLayout>
</template>
