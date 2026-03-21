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
        title: 'Dashboard',
        href: dashboard(),
    },
    {
        title: 'Clients',
        href: '/clients',
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

const embedSnippet = computed(
    () =>
        `<script src="${props.widget_script_url}" data-client="${props.client.unique_code}" async><\/script>`,
);

function formatDate(value?: string | null): string {
    if (!value) return '--';
    return new Date(value).toLocaleDateString(undefined, {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    });
}

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
            <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                <div class="min-w-0">
                    <div class="flex flex-wrap items-center gap-2">
                        <h1 class="text-2xl font-semibold tracking-tight">{{ client.name }}</h1>
                        <Badge :variant="badgeVariant(client.status)">
                            {{ statusLabels[client.status] ?? client.status }}
                        </Badge>
                        <Badge v-if="client.plan" variant="outline">
                            {{ client.plan.name }}
                        </Badge>
                    </div>
                    <p class="mt-1 text-sm text-muted-foreground">
                        {{ client.business_description || 'No business description yet.' }}
                    </p>
                </div>
                <div class="flex shrink-0 gap-2">
                    <Button as-child size="sm">
                        <Link :href="`/clients/${client.id}/edit`">Edit client</Link>
                    </Button>
                    <Button variant="outline" size="sm" as-child>
                        <Link href="/clients">Back to clients</Link>
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
                        <p class="text-xs font-medium text-muted-foreground">Tokens this period</p>
                    </CardHeader>
                    <CardContent>
                        <p class="text-2xl font-semibold tracking-tight">
                            {{ usage_summary.current_period_tokens.toLocaleString() }}
                        </p>
                        <p class="mt-0.5 text-xs text-muted-foreground">
                            {{ usage_summary.current_period_cached_tokens.toLocaleString() }} cached
                        </p>
                    </CardContent>
                </Card>
                <Card class="gap-0 border-sidebar-border/70">
                    <CardHeader class="pb-2">
                        <p class="text-xs font-medium text-muted-foreground">Estimated cost</p>
                    </CardHeader>
                    <CardContent>
                        <p class="text-2xl font-semibold tracking-tight">
                            {{ formatCurrency(usage_summary.current_period_cost) }}
                        </p>
                        <p class="mt-0.5 text-xs text-muted-foreground">
                            {{ usage_summary.current_period_requests }} requests
                        </p>
                    </CardContent>
                </Card>
                <Card class="gap-0 border-sidebar-border/70">
                    <CardHeader class="pb-2">
                        <p class="text-xs font-medium text-muted-foreground">Knowledge base</p>
                    </CardHeader>
                    <CardContent>
                        <p class="text-2xl font-semibold tracking-tight">
                            {{ memory_summary.chunk_count.toLocaleString() }} chunks
                        </p>
                        <p class="mt-0.5 text-xs text-muted-foreground">
                            {{ memory_summary.ready_sources }} ready / {{ memory_summary.queued_sources }} queued
                        </p>
                    </CardContent>
                </Card>
                <Card class="gap-0 border-sidebar-border/70">
                    <CardHeader class="pb-2">
                        <p class="text-xs font-medium text-muted-foreground">Answer cache</p>
                    </CardHeader>
                    <CardContent>
                        <p class="text-2xl font-semibold tracking-tight">
                            {{ memory_summary.cache_entries.toLocaleString() }} entries
                        </p>
                        <p class="mt-0.5 text-xs text-muted-foreground">
                            {{ memory_summary.cache_hits }} hits &middot; {{ memory_summary.saved_tokens.toLocaleString() }} tokens saved
                        </p>
                    </CardContent>
                </Card>
            </section>

            <!-- Knowledge base manager -->
            <Card class="gap-0 border-sidebar-border/70">
                <CardHeader class="border-b border-sidebar-border/70">
                    <h2 class="text-lg font-semibold">Knowledge base</h2>
                    <p class="text-sm text-muted-foreground">
                        Add manual content, website pages, or uploaded documents.
                    </p>
                </CardHeader>
                <CardContent class="space-y-6 pt-6">
                    <form class="grid gap-4" @submit.prevent="submitKnowledgeSource">
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div class="grid gap-2">
                                <Label for="title">Source title</Label>
                                <Input id="title" v-model="form.title" placeholder="Pricing FAQ" />
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
                                        {{ sourceTypeLabels[sourceType] ?? sourceType }}
                                    </option>
                                </select>
                                <InputError :message="form.errors.source_type" />
                            </div>
                        </div>

                        <div v-if="form.source_type === 'manual'" class="grid gap-2">
                            <Label for="content">Manual content</Label>
                            <textarea
                                id="content"
                                v-model="form.content"
                                class="min-h-32 rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs transition-[color,box-shadow] outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                                placeholder="Paste FAQs, support scripts, policy text, or any client-approved knowledge."
                            />
                            <InputError :message="form.errors.content" />
                        </div>

                        <div v-if="form.source_type === 'url'" class="grid gap-2">
                            <Label for="source_url">Website URL</Label>
                            <Input id="source_url" v-model="form.source_url" placeholder="https://client.test/faq" />
                            <InputError :message="form.errors.source_url" />
                        </div>

                        <div v-if="form.source_type === 'file'" class="grid gap-2">
                            <Label for="source_file">Upload file</Label>
                            <Input id="source_file" type="file" @input="handleFileChange" />
                            <p class="text-xs text-muted-foreground">
                                Supported: PDF, TXT, DOC, DOCX. Limit: {{ client.plan?.max_file_upload_mb ?? 10 }} MB.
                            </p>
                            <InputError :message="form.errors.source_file" />
                        </div>

                        <div>
                            <Button type="submit" :disabled="form.processing">
                                {{ form.processing ? 'Saving...' : 'Add knowledge source' }}
                            </Button>
                        </div>
                    </form>

                    <!-- Source list -->
                    <div v-if="knowledge_sources.length > 0" class="overflow-hidden rounded-lg border border-sidebar-border/70">
                        <div class="hidden border-b border-sidebar-border/70 bg-muted/30 px-4 py-2.5 text-xs font-medium uppercase tracking-wider text-muted-foreground sm:grid sm:grid-cols-[2fr_1fr_1fr_1fr]">
                            <span>Source</span>
                            <span>Type</span>
                            <span>Chunks</span>
                            <span>Status</span>
                        </div>
                        <div class="divide-y divide-sidebar-border/70">
                            <div
                                v-for="source in knowledge_sources"
                                :key="source.id"
                                class="px-4 py-3"
                            >
                                <!-- Desktop row -->
                                <div class="hidden items-center sm:grid sm:grid-cols-[2fr_1fr_1fr_1fr]">
                                    <div class="min-w-0">
                                        <p class="truncate text-sm font-medium">{{ source.title }}</p>
                                        <p v-if="source.source_url" class="truncate text-xs text-muted-foreground">{{ source.source_url }}</p>
                                        <p v-if="source.file_name" class="truncate text-xs text-muted-foreground">{{ source.file_name }}</p>
                                    </div>
                                    <Badge variant="outline" class="w-fit text-xs">
                                        {{ sourceTypeLabels[source.source_type] ?? source.source_type }}
                                    </Badge>
                                    <p class="text-sm text-muted-foreground">
                                        {{ source.chunk_count }} / {{ source.token_estimate.toLocaleString() }} tokens
                                    </p>
                                    <Badge :variant="badgeVariant(source.status)" class="w-fit text-xs">
                                        {{ statusLabels[source.status] ?? source.status }}
                                    </Badge>
                                </div>
                                <!-- Mobile -->
                                <div class="space-y-1.5 sm:hidden">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <p class="text-sm font-medium">{{ source.title }}</p>
                                        <Badge :variant="badgeVariant(source.status)" class="text-[10px]">
                                            {{ statusLabels[source.status] ?? source.status }}
                                        </Badge>
                                        <Badge variant="outline" class="text-[10px]">
                                            {{ sourceTypeLabels[source.source_type] ?? source.source_type }}
                                        </Badge>
                                    </div>
                                    <p class="text-xs text-muted-foreground">
                                        {{ source.chunk_count }} chunks &middot; {{ source.token_estimate.toLocaleString() }} tokens
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div
                        v-else
                        class="rounded-lg border border-dashed border-sidebar-border/70 px-6 py-8 text-center text-sm text-muted-foreground"
                    >
                        No knowledge sources yet. Add manual text, a URL, or an upload above.
                    </div>
                </CardContent>
            </Card>

            <!-- Two-column: Usage history + Sidebar info -->
            <section class="grid gap-6 xl:grid-cols-[1fr_1fr]">
                <!-- Usage history -->
                <Card class="gap-0 border-sidebar-border/70">
                    <CardHeader class="border-b border-sidebar-border/70">
                        <h2 class="text-lg font-semibold">Usage history</h2>
                        <p class="text-sm text-muted-foreground">Recent API activity</p>
                    </CardHeader>
                    <CardContent class="p-0">
                        <div v-if="usage_logs.length === 0" class="p-6 text-center text-sm text-muted-foreground">
                            No usage logs yet.
                        </div>
                        <div v-else class="divide-y divide-sidebar-border/70">
                            <div
                                v-for="log in usage_logs"
                                :key="log.id"
                                class="flex items-center justify-between gap-4 px-4 py-3"
                            >
                                <div class="min-w-0">
                                    <p class="truncate text-sm">{{ log.request_excerpt ?? 'No excerpt' }}</p>
                                    <div class="mt-0.5 flex items-center gap-2 text-xs text-muted-foreground">
                                        <Badge :variant="log.interaction_type === 'cache_hit' ? 'secondary' : 'outline'" class="text-[10px]">
                                            {{ log.interaction_type }}
                                        </Badge>
                                        <span>{{ log.model ?? 'n/a' }}</span>
                                    </div>
                                </div>
                                <div class="shrink-0 text-right">
                                    <p class="text-sm tabular-nums">{{ log.total_tokens.toLocaleString() }} tokens</p>
                                    <p class="text-xs text-muted-foreground">{{ formatCurrency(log.estimated_cost) }}</p>
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Cache + Config combined -->
                <div class="grid gap-6">
                    <!-- Answer cache -->
                    <Card class="gap-0 border-sidebar-border/70">
                        <CardHeader class="border-b border-sidebar-border/70">
                            <h2 class="text-lg font-semibold">Answer cache</h2>
                            <p class="text-sm text-muted-foreground">Cached responses for repeated questions</p>
                        </CardHeader>
                        <CardContent class="p-0">
                            <div v-if="cache_entries.length === 0" class="p-6 text-center text-sm text-muted-foreground">
                                No cache entries yet.
                            </div>
                            <div v-else class="divide-y divide-sidebar-border/70">
                                <div
                                    v-for="entry in cache_entries"
                                    :key="entry.id"
                                    class="px-4 py-3"
                                >
                                    <p class="text-sm font-medium">{{ entry.question }}</p>
                                    <p class="mt-1 line-clamp-2 text-xs text-muted-foreground">{{ entry.answer }}</p>
                                    <div class="mt-2 flex flex-wrap items-center gap-3 text-xs text-muted-foreground">
                                        <span>{{ entry.hit_count }} hits</span>
                                        <span>{{ entry.total_tokens_saved.toLocaleString() }} tokens saved</span>
                                        <span v-if="entry.expires_at">Expires {{ formatDate(entry.expires_at) }}</span>
                                    </div>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Config summary -->
                    <Card class="gap-0 border-sidebar-border/70">
                        <CardHeader class="border-b border-sidebar-border/70">
                            <h2 class="text-lg font-semibold">Configuration</h2>
                        </CardHeader>
                        <CardContent class="space-y-4 pt-6 text-sm">
                            <!-- Install snippet -->
                            <div>
                                <div class="flex items-center gap-2 text-xs font-medium uppercase tracking-wider text-muted-foreground">
                                    <Bot class="size-3.5" />
                                    Install snippet
                                </div>
                                <pre class="mt-2 overflow-x-auto rounded-lg bg-slate-950 px-3 py-2.5 text-xs text-slate-100"><code>{{ embedSnippet }}</code></pre>
                            </div>

                            <!-- Package + AI config in a compact grid -->
                            <div class="grid gap-4 sm:grid-cols-2">
                                <div>
                                    <div class="flex items-center gap-2 text-xs font-medium uppercase tracking-wider text-muted-foreground">
                                        <Globe class="size-3.5" />
                                        Package
                                    </div>
                                    <div class="mt-2 grid gap-1 text-muted-foreground">
                                        <p>{{ client.plan?.name ?? 'None' }} <span class="text-foreground">${{ client.plan?.price_monthly?.toFixed(2) ?? '0.00' }}/mo</span></p>
                                        <p>Token cap: <span class="text-foreground">{{ client.monthly_token_limit.toLocaleString() }}</span></p>
                                        <p>Knowledge limit: <span class="text-foreground">{{ client.plan?.max_knowledge_sources ?? 0 }} sources</span></p>
                                    </div>
                                </div>
                                <div>
                                    <div class="flex items-center gap-2 text-xs font-medium uppercase tracking-wider text-muted-foreground">
                                        <Link2 class="size-3.5" />
                                        AI settings
                                    </div>
                                    <div class="mt-2 grid gap-1 text-muted-foreground">
                                        <p>Chat: <span class="text-foreground">{{ client.chat_model }}</span></p>
                                        <p>Embed: <span class="text-foreground">{{ client.embedding_model }}</span></p>
                                        <p>Chunks: <span class="text-foreground">{{ client.retrieval_chunk_count }}</span></p>
                                        <p>Prompt cache: <span class="text-foreground">{{ client.prompt_caching_enabled ? 'On' : 'Off' }}</span></p>
                                        <p>Semantic cache: <span class="text-foreground">{{ client.semantic_cache_enabled ? `${client.cache_ttl_hours}h TTL` : 'Off' }}</span></p>
                                    </div>
                                </div>
                            </div>

                            <!-- System prompt -->
                            <div>
                                <div class="flex items-center gap-2 text-xs font-medium uppercase tracking-wider text-muted-foreground">
                                    <FileText class="size-3.5" />
                                    System prompt
                                </div>
                                <p class="mt-2 whitespace-pre-wrap text-muted-foreground">
                                    {{ client.system_prompt || 'No system prompt configured.' }}
                                </p>
                            </div>

                            <!-- Allowed domains -->
                            <div v-if="client.allowed_domains.length > 0">
                                <div class="flex items-center gap-2 text-xs font-medium uppercase tracking-wider text-muted-foreground">
                                    <Upload class="size-3.5" />
                                    Allowed domains
                                </div>
                                <div class="mt-2 flex flex-wrap gap-1.5">
                                    <Badge v-for="domain in client.allowed_domains" :key="domain" variant="outline" class="text-xs">
                                        {{ domain }}
                                    </Badge>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </section>
        </div>
    </AppLayout>
</template>
