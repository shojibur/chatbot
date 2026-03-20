<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import {
    Bot,
    DatabaseZap,
    FileText,
    Globe,
    Link2,
    Plus,
    Upload,
} from 'lucide-vue-next';
import { computed } from 'vue';
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
    CacheEntryRecord,
    ClientWorkspace,
    KnowledgeSourceRecord,
    MemorySummary,
    UsageLogRecord,
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
    usage_logs: UsageLogRecord[];
    cache_entries: CacheEntryRecord[];
    knowledge_source_types: string[];
    widget_script_url: string;
    status?: string;
};

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Clients',
        href: dashboard(),
    },
    {
        title: props.client.name,
        href: `/clients/${props.client.id}`,
    },
];

const form = useForm<KnowledgeSourceForm>({
    title: '',
    source_type: props.knowledge_source_types[0] ?? 'manual',
    content: '',
    source_url: '',
    source_file: null,
});

const sourceTypeLabels: Record<string, string> = {
    manual: 'Manual text',
    url: 'Website URL',
    file: 'PDF / doc upload',
};

const statusLabels: Record<string, string> = {
    draft: 'Draft',
    queued: 'Queued',
    processing: 'Processing',
    ready: 'Ready',
    failed: 'Failed',
    active: 'Active',
    paused: 'Paused',
};

const statusMessage = computed(() => {
    if (props.status === 'client-created') {
        return 'Client created. Next step is filling the knowledge base.';
    }

    if (props.status === 'client-updated') {
        return 'Client configuration updated.';
    }

    if (props.status === 'knowledge-source-created') {
        return 'Knowledge source saved. Supported sources were chunked immediately; unsupported ones remain queued for parsing.';
    }

    if (props.status === 'knowledge-source-duplicate') {
        return 'This knowledge source already exists for the client. No duplicate copy was stored.';
    }

    return null;
});

const embedSnippet = computed(
    () =>
        `<script src="${props.widget_script_url}" data-client-id="${props.client.unique_code}"><\/script>`,
);

function submitKnowledgeSource(): void {
    form.post(`/clients/${props.client.id}/knowledge-sources`, {
        preserveScroll: true,
        forceFormData: true,
        onSuccess: () => form.reset(),
    });
}

function handleFileChange(event: Event): void {
    const target = event.target as HTMLInputElement;
    form.source_file = target.files?.[0] ?? null;
}

function formatDate(value: string | null): string {
    if (!value) {
        return 'Not available';
    }

    return new Intl.DateTimeFormat(undefined, {
        dateStyle: 'medium',
        timeStyle: 'short',
    }).format(new Date(value));
}

function formatCurrency(value: number): string {
    return new Intl.NumberFormat(undefined, {
        style: 'currency',
        currency: 'USD',
        minimumFractionDigits: 4,
        maximumFractionDigits: 4,
    }).format(value);
}

function badgeVariant(
    status: string,
): 'default' | 'secondary' | 'outline' | 'destructive' {
    if (status === 'ready' || status === 'active') {
        return 'default';
    }

    if (status === 'queued' || status === 'processing') {
        return 'secondary';
    }

    if (status === 'failed') {
        return 'destructive';
    }

    return 'outline';
}
</script>

<template>
    <Head :title="client.name" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-1 flex-col gap-6 p-4 md:p-6">
            <section
                class="overflow-hidden rounded-3xl border border-sidebar-border/70 bg-[radial-gradient(circle_at_top_right,_rgba(15,118,110,0.18),_transparent_36%),linear-gradient(135deg,_rgba(15,23,42,0.96),_rgba(15,118,110,0.92))] p-6 text-white shadow-sm"
            >
                <div
                    class="flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between"
                >
                    <div class="max-w-3xl space-y-3">
                        <div class="flex flex-wrap items-center gap-2">
                            <Badge
                                variant="secondary"
                                class="border-white/10 bg-white/10 text-white"
                            >
                                {{ client.plan?.name ?? 'No plan assigned' }}
                            </Badge>
                            <Badge
                                variant="outline"
                                class="border-white/20 text-white"
                            >
                                {{
                                    statusLabels[client.status] ?? client.status
                                }}
                            </Badge>
                        </div>
                        <div class="space-y-2">
                            <h1 class="text-3xl font-semibold tracking-tight">
                                {{ client.name }}
                            </h1>
                            <p class="text-sm text-white/75">
                                {{
                                    client.business_description ||
                                    'No business description yet.'
                                }}
                            </p>
                        </div>
                    </div>

                    <div class="flex flex-wrap gap-3">
                        <Button
                            variant="secondary"
                            as-child
                            class="bg-white text-slate-950 hover:bg-white/90"
                        >
                            <Link :href="`/clients/${client.id}/edit`"
                                >Edit client</Link
                            >
                        </Button>
                        <Button
                            variant="outline"
                            as-child
                            class="border-white/20 text-white hover:bg-white/10"
                        >
                            <Link href="/dashboard">Back to clients</Link>
                        </Button>
                    </div>
                </div>
            </section>

            <div
                v-if="statusMessage"
                class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700 dark:border-emerald-300/10 dark:bg-emerald-500/10 dark:text-emerald-200"
            >
                {{ statusMessage }}
            </div>

            <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                <Card class="gap-0 border-sidebar-border/70">
                    <CardHeader class="pb-3">
                        <p class="text-sm text-muted-foreground">
                            Current period tokens
                        </p>
                        <p class="text-2xl font-semibold tracking-tight">
                            {{
                                usage_summary.current_period_tokens.toLocaleString()
                            }}
                        </p>
                    </CardHeader>
                    <CardContent class="pt-0 text-sm text-muted-foreground">
                        Logged token usage for this billing window.
                    </CardContent>
                </Card>

                <Card class="gap-0 border-sidebar-border/70">
                    <CardHeader class="pb-3">
                        <p class="text-sm text-muted-foreground">
                            Cached tokens
                        </p>
                        <p class="text-2xl font-semibold tracking-tight">
                            {{
                                usage_summary.current_period_cached_tokens.toLocaleString()
                            }}
                        </p>
                    </CardHeader>
                    <CardContent class="pt-0 text-sm text-muted-foreground">
                        Prompt and semantic cache savings tracked so far.
                    </CardContent>
                </Card>

                <Card class="gap-0 border-sidebar-border/70">
                    <CardHeader class="pb-3">
                        <p class="text-sm text-muted-foreground">
                            Estimated cost
                        </p>
                        <p class="text-2xl font-semibold tracking-tight">
                            {{
                                formatCurrency(
                                    usage_summary.current_period_cost,
                                )
                            }}
                        </p>
                    </CardHeader>
                    <CardContent class="pt-0 text-sm text-muted-foreground">
                        Estimated OpenAI spend based on logged activity.
                    </CardContent>
                </Card>

                <Card class="gap-0 border-sidebar-border/70">
                    <CardHeader class="pb-3">
                        <p class="text-sm text-muted-foreground">Requests</p>
                        <p class="text-2xl font-semibold tracking-tight">
                            {{
                                usage_summary.current_period_requests.toLocaleString()
                            }}
                        </p>
                    </CardHeader>
                    <CardContent class="pt-0 text-sm text-muted-foreground">
                        Total chat, embedding, or cache events recorded this
                        period.
                    </CardContent>
                </Card>
            </section>

            <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                <Card class="gap-0 border-sidebar-border/70">
                    <CardHeader class="pb-3">
                        <p class="text-sm text-muted-foreground">
                            Indexed chunks
                        </p>
                        <p class="text-2xl font-semibold tracking-tight">
                            {{ memory_summary.chunk_count.toLocaleString() }}
                        </p>
                    </CardHeader>
                    <CardContent class="pt-0 text-sm text-muted-foreground">
                        Retrieval-ready chunks stored in Postgres for RAG.
                    </CardContent>
                </Card>

                <Card class="gap-0 border-sidebar-border/70">
                    <CardHeader class="pb-3">
                        <p class="text-sm text-muted-foreground">
                            Ready sources
                        </p>
                        <p class="text-2xl font-semibold tracking-tight">
                            {{ memory_summary.ready_sources.toLocaleString() }}
                        </p>
                    </CardHeader>
                    <CardContent class="pt-0 text-sm text-muted-foreground">
                        Knowledge sources already normalized and chunked.
                    </CardContent>
                </Card>

                <Card class="gap-0 border-sidebar-border/70">
                    <CardHeader class="pb-3">
                        <p class="text-sm text-muted-foreground">
                            Cache entries
                        </p>
                        <p class="text-2xl font-semibold tracking-tight">
                            {{ memory_summary.cache_entries.toLocaleString() }}
                        </p>
                    </CardHeader>
                    <CardContent class="pt-0 text-sm text-muted-foreground">
                        Persistent answers available before another model call.
                    </CardContent>
                </Card>

                <Card class="gap-0 border-sidebar-border/70">
                    <CardHeader class="pb-3">
                        <p class="text-sm text-muted-foreground">
                            Saved tokens
                        </p>
                        <p class="text-2xl font-semibold tracking-tight">
                            {{ memory_summary.saved_tokens.toLocaleString() }}
                        </p>
                    </CardHeader>
                    <CardContent class="pt-0 text-sm text-muted-foreground">
                        Tokens avoided through persistent cache re-use.
                    </CardContent>
                </Card>
            </section>

            <section class="grid gap-4 xl:grid-cols-[minmax(0,2fr)_380px]">
                <div class="grid gap-4">
                    <Card class="gap-0 border-sidebar-border/70">
                        <CardHeader class="border-b border-sidebar-border/70">
                            <div class="flex items-start gap-3">
                                <div
                                    class="rounded-2xl border border-sidebar-border/70 p-3"
                                >
                                    <Plus class="size-5" />
                                </div>
                                <div>
                                    <h2 class="text-xl font-semibold">
                                        Knowledge base manager
                                    </h2>
                                    <p class="text-sm text-muted-foreground">
                                        Add manual content, website pages, or
                                        uploaded documents without sending the
                                        entire knowledge base to OpenAI at once.
                                    </p>
                                </div>
                            </div>
                        </CardHeader>
                        <CardContent class="space-y-6 pt-6">
                            <form
                                class="grid gap-4"
                                @submit.prevent="submitKnowledgeSource"
                            >
                                <div class="grid gap-4 md:grid-cols-2">
                                    <div class="grid gap-2">
                                        <Label for="title">Source title</Label>
                                        <Input
                                            id="title"
                                            v-model="form.title"
                                            placeholder="Pricing FAQ"
                                        />
                                        <InputError
                                            :message="form.errors.title"
                                        />
                                    </div>

                                    <div class="grid gap-2">
                                        <Label for="source_type"
                                            >Source type</Label
                                        >
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
                                                    sourceTypeLabels[
                                                        sourceType
                                                    ] ?? sourceType
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
                                    <InputError
                                        :message="form.errors.content"
                                    />
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
                                    <InputError
                                        :message="form.errors.source_url"
                                    />
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
                                        Supported: PDF, TXT, DOC, DOCX. Current
                                        plan limit:
                                        {{
                                            client.plan?.max_file_upload_mb ??
                                            10
                                        }}
                                        MB.
                                    </p>
                                    <InputError
                                        :message="form.errors.source_file"
                                    />
                                </div>

                                <div class="flex flex-wrap gap-3">
                                    <Button
                                        type="submit"
                                        :disabled="form.processing"
                                    >
                                        {{
                                            form.processing
                                                ? 'Saving...'
                                                : 'Add knowledge source'
                                        }}
                                    </Button>
                                </div>
                            </form>

                            <div class="grid gap-3">
                                <article
                                    v-for="source in knowledge_sources"
                                    :key="source.id"
                                    class="rounded-2xl border border-sidebar-border/70 p-4"
                                >
                                    <div
                                        class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between"
                                    >
                                        <div class="space-y-2">
                                            <div
                                                class="flex flex-wrap items-center gap-2"
                                            >
                                                <h3 class="font-medium">
                                                    {{ source.title }}
                                                </h3>
                                                <Badge
                                                    :variant="
                                                        badgeVariant(
                                                            source.status,
                                                        )
                                                    "
                                                >
                                                    {{
                                                        statusLabels[
                                                            source.status
                                                        ] ?? source.status
                                                    }}
                                                </Badge>
                                                <Badge variant="outline">
                                                    {{
                                                        sourceTypeLabels[
                                                            source.source_type
                                                        ] ?? source.source_type
                                                    }}
                                                </Badge>
                                            </div>
                                            <div
                                                class="grid gap-1 text-sm text-muted-foreground"
                                            >
                                                <p v-if="source.source_url">
                                                    {{ source.source_url }}
                                                </p>
                                                <p v-if="source.file_name">
                                                    {{ source.file_name }}
                                                </p>
                                                <p>
                                                    Token estimate:
                                                    {{
                                                        source.token_estimate.toLocaleString()
                                                    }}
                                                </p>
                                                <p>
                                                    Chunk count:
                                                    {{
                                                        source.chunk_count.toLocaleString()
                                                    }}
                                                </p>
                                                <p v-if="source.source_hash">
                                                    Source hash:
                                                    {{ source.source_hash }}
                                                </p>
                                                <p
                                                    v-if="
                                                        source.processing_error
                                                    "
                                                    class="text-destructive"
                                                >
                                                    {{
                                                        source.processing_error
                                                    }}
                                                </p>
                                            </div>
                                        </div>

                                        <div
                                            class="text-sm text-muted-foreground"
                                        >
                                            <p>
                                                Created
                                                {{
                                                    formatDate(
                                                        source.created_at,
                                                    )
                                                }}
                                            </p>
                                            <p>
                                                Synced
                                                {{
                                                    formatDate(
                                                        source.last_synced_at,
                                                    )
                                                }}
                                            </p>
                                            <p>
                                                Processed
                                                {{
                                                    formatDate(
                                                        source.processed_at,
                                                    )
                                                }}
                                            </p>
                                        </div>
                                    </div>
                                </article>

                                <div
                                    v-if="knowledge_sources.length === 0"
                                    class="rounded-2xl border border-dashed border-sidebar-border/70 px-6 py-10 text-center text-sm text-muted-foreground"
                                >
                                    No knowledge sources yet. Add manual text, a
                                    URL, or an upload to start shaping the RAG
                                    base.
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <Card class="gap-0 border-sidebar-border/70">
                        <CardHeader class="border-b border-sidebar-border/70">
                            <div class="flex items-start gap-3">
                                <div
                                    class="rounded-2xl border border-sidebar-border/70 p-3"
                                >
                                    <DatabaseZap class="size-5" />
                                </div>
                                <div>
                                    <h2 class="text-xl font-semibold">
                                        Usage history
                                    </h2>
                                    <p class="text-sm text-muted-foreground">
                                        Token usage, cached input, and estimated
                                        cost history for billing and margin
                                        control.
                                    </p>
                                </div>
                            </div>
                        </CardHeader>
                        <CardContent class="space-y-3 pt-6">
                            <article
                                v-for="log in usage_logs"
                                :key="log.id"
                                class="rounded-2xl border border-sidebar-border/70 p-4"
                            >
                                <div
                                    class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between"
                                >
                                    <div class="space-y-2">
                                        <div
                                            class="flex flex-wrap items-center gap-2"
                                        >
                                            <Badge
                                                :variant="
                                                    badgeVariant(
                                                        log.interaction_type ===
                                                            'cache_hit'
                                                            ? 'processing'
                                                            : 'ready',
                                                    )
                                                "
                                            >
                                                {{ log.interaction_type }}
                                            </Badge>
                                            <span
                                                class="text-sm text-muted-foreground"
                                                >{{ log.model ?? 'n/a' }}</span
                                            >
                                        </div>
                                        <p class="text-sm">
                                            {{
                                                log.request_excerpt ??
                                                'No excerpt recorded.'
                                            }}
                                        </p>
                                    </div>

                                    <div
                                        class="grid gap-1 text-sm text-muted-foreground lg:text-right"
                                    >
                                        <p>
                                            Total:
                                            <span class="text-foreground">{{
                                                log.total_tokens.toLocaleString()
                                            }}</span>
                                        </p>
                                        <p>
                                            Cached:
                                            <span class="text-foreground">{{
                                                log.cached_input_tokens.toLocaleString()
                                            }}</span>
                                        </p>
                                        <p>
                                            Cost:
                                            <span class="text-foreground">{{
                                                formatCurrency(
                                                    log.estimated_cost,
                                                )
                                            }}</span>
                                        </p>
                                        <p>{{ formatDate(log.created_at) }}</p>
                                    </div>
                                </div>
                            </article>

                            <div
                                v-if="usage_logs.length === 0"
                                class="rounded-2xl border border-dashed border-sidebar-border/70 px-6 py-10 text-center text-sm text-muted-foreground"
                            >
                                No usage logs yet. This section will fill in
                                once chat, embedding, or cache activity starts
                                being recorded.
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <div class="grid gap-4">
                    <Card class="gap-0 border-sidebar-border/70">
                        <CardHeader class="border-b border-sidebar-border/70">
                            <div class="flex items-start gap-3">
                                <div
                                    class="rounded-2xl border border-sidebar-border/70 p-3"
                                >
                                    <DatabaseZap class="size-5" />
                                </div>
                                <div>
                                    <h2 class="text-xl font-semibold">
                                        Persistent answer cache
                                    </h2>
                                    <p class="text-sm text-muted-foreground">
                                        Exact-match answer cache entries stored
                                        per client to cut repeated model calls.
                                    </p>
                                </div>
                            </div>
                        </CardHeader>
                        <CardContent class="space-y-3 pt-6">
                            <article
                                v-for="entry in cache_entries"
                                :key="entry.id"
                                class="rounded-2xl border border-sidebar-border/70 p-4"
                            >
                                <div class="space-y-3">
                                    <div
                                        class="flex flex-wrap items-center gap-2"
                                    >
                                        <Badge variant="outline">
                                            {{ entry.hit_count }} hits
                                        </Badge>
                                        <Badge
                                            :variant="
                                                entry.expires_at
                                                    ? 'secondary'
                                                    : 'default'
                                            "
                                        >
                                            {{
                                                entry.expires_at
                                                    ? 'TTL'
                                                    : 'No expiry'
                                            }}
                                        </Badge>
                                    </div>
                                    <div class="space-y-1">
                                        <p class="text-sm font-medium">
                                            {{ entry.question }}
                                        </p>
                                        <p
                                            class="text-sm text-muted-foreground"
                                        >
                                            {{ entry.answer }}
                                        </p>
                                    </div>
                                    <div
                                        class="flex flex-wrap gap-4 text-xs text-muted-foreground"
                                    >
                                        <p>
                                            Saved:
                                            {{
                                                entry.total_tokens_saved.toLocaleString()
                                            }}
                                            tokens
                                        </p>
                                        <p>
                                            Last hit
                                            {{ formatDate(entry.last_hit_at) }}
                                        </p>
                                        <p>
                                            Expires
                                            {{ formatDate(entry.expires_at) }}
                                        </p>
                                    </div>
                                </div>
                            </article>

                            <div
                                v-if="cache_entries.length === 0"
                                class="rounded-2xl border border-dashed border-sidebar-border/70 px-6 py-10 text-center text-sm text-muted-foreground"
                            >
                                No persistent cache entries yet. Repeated
                                questions will start showing up here once the
                                chat runtime stores them.
                            </div>
                        </CardContent>
                    </Card>

                    <Card class="gap-0 border-sidebar-border/70">
                        <CardHeader class="border-b border-sidebar-border/70">
                            <h2 class="text-xl font-semibold">
                                Subscription and rollout
                            </h2>
                            <p class="text-sm text-muted-foreground">
                                Package limits, AI settings, and install details
                                used by the public widget.
                            </p>
                        </CardHeader>
                        <CardContent class="space-y-4 pt-6 text-sm">
                            <div
                                class="rounded-2xl border border-sidebar-border/70 p-4"
                            >
                                <div
                                    class="flex items-center gap-2 font-medium"
                                >
                                    <Bot class="size-4" />
                                    Install snippet
                                </div>
                                <pre
                                    class="mt-3 overflow-x-auto rounded-lg bg-slate-950 px-3 py-3 text-xs text-slate-100"
                                ><code>{{ embedSnippet }}</code></pre>
                            </div>

                            <div
                                class="rounded-2xl border border-sidebar-border/70 p-4"
                            >
                                <div
                                    class="flex items-center gap-2 font-medium"
                                >
                                    <Globe class="size-4" />
                                    Package
                                </div>
                                <div
                                    class="mt-3 grid gap-2 text-muted-foreground"
                                >
                                    <p>
                                        Name:
                                        <span class="text-foreground">{{
                                            client.plan?.name ?? 'None'
                                        }}</span>
                                    </p>
                                    <p>
                                        Price:
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
                                                    ?.max_knowledge_sources ?? 0
                                            }}
                                            sources</span
                                        >
                                    </p>
                                </div>
                            </div>

                            <div
                                class="rounded-2xl border border-sidebar-border/70 p-4"
                            >
                                <div
                                    class="flex items-center gap-2 font-medium"
                                >
                                    <Link2 class="size-4" />
                                    AI and cache config
                                </div>
                                <div
                                    class="mt-3 grid gap-2 text-muted-foreground"
                                >
                                    <p>
                                        Chat model:
                                        <span class="text-foreground">{{
                                            client.chat_model
                                        }}</span>
                                    </p>
                                    <p>
                                        Embedding model:
                                        <span class="text-foreground">{{
                                            client.embedding_model
                                        }}</span>
                                    </p>
                                    <p>
                                        Retrieved chunks:
                                        <span class="text-foreground">{{
                                            client.retrieval_chunk_count
                                        }}</span>
                                    </p>
                                    <p>
                                        Prompt caching:
                                        <span class="text-foreground">{{
                                            client.prompt_caching_enabled
                                                ? 'Enabled'
                                                : 'Disabled'
                                        }}</span>
                                    </p>
                                    <p>
                                        Semantic cache:
                                        <span class="text-foreground">{{
                                            client.semantic_cache_enabled
                                                ? `${client.cache_ttl_hours}h TTL`
                                                : 'Disabled'
                                        }}</span>
                                    </p>
                                    <p>
                                        Answer cache hits:
                                        <span class="text-foreground">{{
                                            memory_summary.cache_hits.toLocaleString()
                                        }}</span>
                                    </p>
                                    <p>
                                        Queued sources:
                                        <span class="text-foreground">{{
                                            memory_summary.queued_sources.toLocaleString()
                                        }}</span>
                                    </p>
                                </div>
                            </div>

                            <div
                                class="rounded-2xl border border-sidebar-border/70 p-4"
                            >
                                <div
                                    class="flex items-center gap-2 font-medium"
                                >
                                    <FileText class="size-4" />
                                    Knowledge rules
                                </div>
                                <p
                                    class="mt-3 whitespace-pre-wrap text-muted-foreground"
                                >
                                    {{
                                        client.system_prompt ||
                                        'No system prompt configured yet.'
                                    }}
                                </p>
                            </div>

                            <div
                                class="rounded-2xl border border-sidebar-border/70 p-4"
                            >
                                <div
                                    class="flex items-center gap-2 font-medium"
                                >
                                    <Upload class="size-4" />
                                    Allowed domains
                                </div>
                                <div class="mt-3 flex flex-wrap gap-2">
                                    <Badge
                                        v-for="domain in client.allowed_domains"
                                        :key="domain"
                                        variant="outline"
                                    >
                                        {{ domain }}
                                    </Badge>
                                    <p
                                        v-if="
                                            client.allowed_domains.length === 0
                                        "
                                        class="text-muted-foreground"
                                    >
                                        No domains configured yet.
                                    </p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </section>
        </div>
    </AppLayout>
</template>
