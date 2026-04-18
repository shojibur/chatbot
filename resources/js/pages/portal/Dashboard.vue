<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import {
    Bot,
    ChevronUp,
    DatabaseZap,
    Eye,
    FileText,
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
    plan_limits: {
        max_knowledge_sources: number;
        max_file_upload_mb: number;
        monthly_token_limit: number;
        monthly_message_limit: number;
    };
    at_knowledge_source_limit: boolean;
    status?: string;
    lead_count?: number;
};

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/portal/dashboard' },
];

// Form for new knowledge source
const form = useForm<KnowledgeSourceForm>({
    title: '',
    source_type: props.knowledge_source_types[0] ?? 'manual',
    content: '',
    source_url: '',
    source_file: null,
});

function submitKnowledgeSource(): void {
    form.post('/portal/knowledge-sources', {
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
    router.delete(`/portal/knowledge-sources/${deleteSourceTarget.value.id}`, {
        preserveScroll: true,
        onFinish: () => {
            deletingSource.value = false;
            deleteSourceTarget.value = null;
        },
    });
}

function retrySource(source: KnowledgeSourceRecord) {
    router.post(`/portal/knowledge-sources/${source.id}/retry`, {}, {
        preserveScroll: true,
    });
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
    editForm.patch(`/portal/knowledge-sources/${editingSource.value.id}`, {
        preserveScroll: true,
        onSuccess: () => {
            editingSource.value = null;
            editForm.reset();
        },
    });
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

async function toggleViewChunks(source: KnowledgeSourceRecord) {
    if (viewingChunksSourceId.value === source.id) {
        viewingChunksSourceId.value = null;
        chunksData.value = [];
        return;
    }

    viewingChunksSourceId.value = source.id;
    chunksLoading.value = true;

    try {
        const res = await fetch(`/portal/knowledge-sources/${source.id}/chunks`, {
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
        });
        const data = await res.json();
        chunksData.value = data.chunks ?? [];
    } catch {
        chunksData.value = [];
    } finally {
        chunksLoading.value = false;
    }
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
    if (props.status === 'knowledge-source-created') return { text: 'Knowledge source added and queued for processing.', type: 'success' };
    if (props.status === 'knowledge-source-deleted') return { text: 'Knowledge source removed.', type: 'success' };
    if (props.status === 'knowledge-source-updated') return { text: 'Knowledge source updated.', type: 'success' };
    if (props.status === 'knowledge-source-retrying') return { text: 'Processing restarted.', type: 'success' };
    if (props.status === 'knowledge-source-duplicate') return { text: 'This source already exists — no duplicate created.', type: 'warning' };
    if (props.status === 'knowledge-source-limit-reached') return { text: `You have reached your plan limit of ${props.plan_limits.max_knowledge_sources} knowledge sources. Please contact your administrator to upgrade.`, type: 'error' };
    return null;
});

const ksUsagePercent = computed(() => {
    const max = props.plan_limits.max_knowledge_sources;
    return Math.min(100, Math.round((props.knowledge_sources.length / max) * 100));
});

const tokenUsagePercent = computed(() => {
    const max = props.plan_limits.monthly_token_limit;
    if (!max) return 0;
    return Math.min(100, Math.round((props.usage_summary.current_period_tokens / max) * 100));
});

function badgeVariant(status: string): 'default' | 'secondary' | 'outline' {
    if (status === 'active' || status === 'ready') return 'default';
    if (status === 'paused' || status === 'processing') return 'secondary';
    return 'outline';
}
</script>

<template>
    <Head title="Client Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-1 flex-col gap-6 p-4 md:p-6">
            <!-- Header -->
            <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                <div>
                    <h1 class="text-2xl font-bold tracking-tight">Welcome, {{ client.name }}</h1>
                    <p class="text-muted-foreground text-sm">Manage your chatbot's knowledge and monitor its performance.</p>
                </div>
                <div class="flex items-center gap-2">
                    <Badge v-if="client.plan" variant="outline" class="px-3 py-1 text-xs font-medium border-orange-200 bg-orange-50 text-orange-700 dark:border-orange-900/50 dark:bg-orange-900/20 dark:text-orange-400">
                        {{ client.plan.name }} Plan
                    </Badge>
                    <Badge :variant="badgeVariant(client.status)" class="px-3 py-1 text-xs font-medium">
                        {{ statusLabels[client.status] ?? client.status }}
                    </Badge>
                </div>
            </div>

            <!-- Status message -->
            <div
                v-if="statusMessage"
                class="rounded-xl border px-4 py-3 text-sm"
                :class="statusMessage.type === 'error'
                    ? 'border-red-200 bg-red-50 text-red-700 dark:border-red-900/30 dark:bg-red-900/10 dark:text-red-400'
                    : statusMessage.type === 'warning'
                        ? 'border-amber-200 bg-amber-50 text-amber-700 dark:border-amber-900/30 dark:bg-amber-900/10 dark:text-amber-400'
                        : 'border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-300/10 dark:bg-emerald-500/10 dark:text-emerald-200'"
            >
                {{ statusMessage.text }}
            </div>

            <!-- Stats Overview -->
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <Card class="border-sidebar-border/60 shadow-sm">
                    <CardHeader class="flex flex-row items-center justify-between pb-2">
                        <p class="text-xs font-medium text-muted-foreground uppercase tracking-wider">Monthly Tokens</p>
                        <Bot class="h-4 w-4 text-muted-foreground/70" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">{{ usage_summary.current_period_tokens.toLocaleString() }}</div>
                        <div class="mt-1 flex items-center gap-2">
                            <div class="h-1.5 flex-1 rounded-full bg-secondary">
                                <div 
                                    class="h-full rounded-full bg-primary transition-all" 
                                    :style="{ width: Math.min(100, (usage_summary.current_period_tokens / client.monthly_token_limit) * 100) + '%' }"
                                />
                            </div>
                            <span class="text-[10px] text-muted-foreground whitespace-nowrap">
                                {{ Math.round((usage_summary.current_period_tokens / client.monthly_token_limit) * 100) }}%
                            </span>
                        </div>
                    </CardContent>
                </Card>
                <Card class="border-sidebar-border/60 shadow-sm">
                    <CardHeader class="flex flex-row items-center justify-between pb-2">
                        <p class="text-xs font-medium text-muted-foreground uppercase tracking-wider">Knowledge Chunks</p>
                        <DatabaseZap class="h-4 w-4 text-muted-foreground/70" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">{{ memory_summary.chunk_count.toLocaleString() }}</div>
                        <p class="text-[10px] text-muted-foreground mt-1">{{ memory_summary.ready_sources }} verified sources ready</p>
                    </CardContent>
                </Card>
                <Card class="border-sidebar-border/60 shadow-sm">
                    <CardHeader class="flex flex-row items-center justify-between pb-2">
                        <p class="text-xs font-medium text-muted-foreground uppercase tracking-wider">Queries</p>
                        <MessageSquare class="h-4 w-4 text-muted-foreground/70" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">{{ usage_summary.current_period_requests.toLocaleString() }}</div>
                        <p class="text-[10px] text-muted-foreground mt-1">{{ memory_summary.cache_hits }} answered from cache</p>
                    </CardContent>
                </Card>
                <Card class="border-sidebar-border/60 shadow-sm">
                    <CardHeader class="flex flex-row items-center justify-between pb-2">
                        <p class="text-xs font-medium text-muted-foreground uppercase tracking-wider">Leads</p>
                        <FileText class="h-4 w-4 text-muted-foreground/70" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">{{ lead_count ?? 0 }}</div>
                        <p class="text-[10px] text-muted-foreground mt-1">Contact inquiries this month</p>
                    </CardContent>
                </Card>
            </div>

            <!-- New Knowledge Source Form -->
            <Card class="border-sidebar-border/60 shadow-sm">
                <CardHeader>
                    <div class="flex items-center justify-between">
                        <div>
                            <h2 class="text-lg font-semibold">Update Knowledge</h2>
                            <p class="text-sm text-muted-foreground">Add new information to your chatbot's brain.</p>
                        </div>
                        <!-- Quota indicator -->
                        <div class="text-right">
                            <p class="text-xs font-semibold tabular-nums" :class="at_knowledge_source_limit ? 'text-red-600 dark:text-red-400' : 'text-muted-foreground'">
                                {{ knowledge_sources.length }} / {{ plan_limits.max_knowledge_sources }} sources
                            </p>
                            <div class="mt-1 h-1.5 w-28 rounded-full bg-secondary overflow-hidden">
                                <div
                                    class="h-full rounded-full transition-all"
                                    :class="ksUsagePercent >= 100 ? 'bg-red-500' : ksUsagePercent >= 80 ? 'bg-amber-500' : 'bg-emerald-500'"
                                    :style="{ width: ksUsagePercent + '%' }"
                                />
                            </div>
                        </div>
                    </div>
                    <!-- Limit warning banner -->
                    <div v-if="at_knowledge_source_limit" class="mt-3 rounded-lg border border-red-200 bg-red-50 px-3 py-2.5 text-sm text-red-700 dark:border-red-900/30 dark:bg-red-900/10 dark:text-red-400">
                        ⚠️ You've reached your plan limit of <strong>{{ plan_limits.max_knowledge_sources }}</strong> knowledge sources. Contact your administrator to upgrade.
                    </div>
                </CardHeader>
                <CardContent>
                    <form @submit.prevent="submitKnowledgeSource" class="grid gap-4">
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div class="grid gap-2">
                                <Label for="title">Label</Label>
                                <Input id="title" v-model="form.title" placeholder="e.g. Return Policy" />
                                <InputError :message="form.errors.title" />
                            </div>
                            <div class="grid gap-2">
                                <Label for="source_type">Type</Label>
                                <select
                                    id="source_type"
                                    v-model="form.source_type"
                                    class="h-10 rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-sm transition-colors outline-none focus:ring-1 focus:ring-ring"
                                >
                                    <option v-for="st in knowledge_source_types" :key="st" :value="st">{{ sourceTypeLabels[st] }}</option>
                                </select>
                            </div>
                        </div>

                        <div v-if="form.source_type === 'manual'" class="grid gap-2">
                            <Label for="content">Text Content</Label>
                            <textarea
                                id="content"
                                v-model="form.content"
                                class="min-h-32 rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-sm transition-colors outline-none focus:ring-1 focus:ring-ring"
                                placeholder="Paste information here..."
                            />
                            <InputError :message="form.errors.content" />
                        </div>

                        <div v-if="form.source_type === 'url'" class="grid gap-2">
                            <Label for="source_url">Website URL</Label>
                            <Input id="source_url" v-model="form.source_url" placeholder="https://yourwebsite.com/faq" />
                            <InputError :message="form.errors.source_url" />
                        </div>

                        <div v-if="form.source_type === 'file'" class="grid gap-2">
                            <Label for="source_file">Upload Document</Label>
                            <Input id="source_file" type="file" @input="handleFileChange" />
                            <p class="text-[10px] text-muted-foreground">PDF, TXT, or DOCX up to {{ plan_limits.max_file_upload_mb }}MB</p>
                            <InputError :message="form.errors.source_file" />
                        </div>

                        <div class="flex items-center justify-between gap-4">
                            <p v-if="at_knowledge_source_limit" class="text-xs text-red-600 dark:text-red-400">
                                Knowledge source limit reached.
                            </p>
                            <span v-else />
                            <Button type="submit" :disabled="form.processing || at_knowledge_source_limit">
                                <Loader2 v-if="form.processing" class="mr-2 h-4 w-4 animate-spin" />
                                Add Knowledge
                            </Button>
                        </div>
                    </form>
                </CardContent>
            </Card>

            <!-- Knowledge Base List -->
            <Card class="border-sidebar-border/60 shadow-sm">
                <CardHeader class="flex flex-row items-center justify-between">
                    <div>
                        <h2 class="text-lg font-semibold">Active Sources</h2>
                        <p class="text-sm text-muted-foreground">Approved information currently used by the bot.</p>
                    </div>
                        <Badge variant="outline" class="font-normal">{{ knowledge_sources.length }} / {{ plan_limits.max_knowledge_sources }} Sources</Badge>
                </CardHeader>
                <CardContent>
                    <div class="space-y-3">
                        <div v-if="knowledge_sources.length === 0" class="flex flex-col items-center justify-center rounded-lg border border-dashed py-12 text-center">
                            <Bot class="mb-4 h-12 w-12 text-muted-foreground/30" />
                            <p class="text-sm font-medium text-muted-foreground">No knowledge added yet</p>
                        </div>
                        
                        <div v-else class="grid gap-3">
                            <div v-for="source in knowledge_sources" :key="source.id" class="group rounded-lg border border-sidebar-border/50 bg-card p-4 transition-all hover:border-sidebar-border hover:shadow-sm">
                                <div class="flex items-start justify-between">
                                    <div class="flex flex-col gap-1 min-w-0">
                                        <div class="flex items-center gap-2">
                                            <h3 class="font-medium text-sm truncate">{{ source.title }}</h3>
                                            <Badge :variant="badgeVariant(source.status)" class="h-5 text-[9px] font-bold px-1.5">
                                                {{ statusLabels[source.status] }}
                                            </Badge>
                                        </div>
                                        <div class="flex items-center gap-3 text-[10px] text-muted-foreground">
                                            <span class="flex items-center gap-1 uppercase tracking-tighter font-semibold">
                                                <Bot class="h-3 w-3" />
                                                {{ sourceTypeLabels[source.source_type] }}
                                            </span>
                                            <span>&bull;</span>
                                            <span>{{ source.chunk_count }} Chunks</span>
                                            <span v-if="source.last_synced_at">&bull;</span>
                                            <span v-if="source.last_synced_at">Synced {{ source.last_synced_at }}</span>
                                        </div>
                                        <p v-if="source.processing_error" class="mt-1 text-[10px] text-red-500 font-medium bg-red-50 dark:bg-red-900/10 p-1.5 rounded border border-red-100 dark:border-red-900/30">
                                            Error: {{ source.processing_error }}
                                        </p>
                                    </div>
                                    
                                    <div class="flex gap-1">
                                        <Button v-if="source.chunk_count > 0" variant="ghost" size="icon" class="h-8 w-8 text-muted-foreground hover:text-foreground" @click="toggleViewChunks(source)" title="View memory chunks">
                                            <Eye v-if="viewingChunksSourceId !== source.id" class="h-4 w-4" />
                                            <ChevronUp v-else class="h-4 w-4" />
                                        </Button>
                                        <Button v-if="source.source_type === 'manual'" variant="ghost" size="icon" class="h-8 w-8 text-muted-foreground hover:text-foreground" @click="startEditSource(source)" title="Edit">
                                            <Pencil class="h-4 w-4" />
                                        </Button>
                                        <Button v-if="source.status === 'failed'" variant="ghost" size="icon" class="h-8 w-8 text-muted-foreground hover:text-foreground" @click="retrySource(source)" title="Retry">
                                            <RefreshCw class="h-4 w-4" />
                                        </Button>
                                        <Button variant="ghost" size="icon" class="h-8 w-8 text-muted-foreground hover:text-red-600" @click="confirmDeleteSource(source)" title="Delete">
                                            <Trash2 class="h-4 w-4" />
                                        </Button>
                                    </div>
                                </div>

                                <!-- Inline Edit -->
                                <div v-if="editingSource?.id === source.id && source.source_type === 'manual'" class="mt-4 space-y-3 rounded-md bg-muted/30 p-4 border border-sidebar-border/40">
                                    <div class="grid gap-2">
                                        <Label class="text-xs">Title</Label>
                                        <Input v-model="editForm.title" class="h-8" />
                                    </div>
                                    <div class="grid gap-2">
                                        <Label class="text-xs">Content</Label>
                                        <textarea v-model="editForm.content" class="min-h-32 rounded-md border border-input bg-background px-3 py-2 text-sm outline-none focus:ring-1 focus:ring-ring" />
                                    </div>
                                    <div class="flex gap-2 justify-end">
                                        <Button variant="outline" size="sm" @click="cancelEditSource">Cancel</Button>
                                        <Button size="sm" :disabled="editForm.processing" @click="submitEditSource">Save</Button>
                                    </div>
                                </div>

                                <!-- Chunks View -->
                                <div v-if="viewingChunksSourceId === source.id" class="mt-4 rounded-md bg-muted/40 p-1 border border-sidebar-border/40 overflow-hidden">
                                    <div v-if="chunksLoading" class="flex items-center justify-center p-8 text-xs text-muted-foreground">
                                        <Loader2 class="mr-2 h-4 w-4 animate-spin" />
                                        Loading memory chunks...
                                    </div>
                                    <div v-else class="max-h-[300px] overflow-y-auto divide-y divide-sidebar-border/20">
                                        <div v-for="chunk in chunksData" :key="chunk.id" class="p-3">
                                            <div class="flex items-center gap-2 mb-1.5">
                                                <Badge variant="outline" class="h-4 text-[9px] font-mono">#{{ chunk.chunk_index }}</Badge>
                                                <span class="text-[9px] text-muted-foreground tracking-tight">{{ chunk.character_count }} chars &bull; {{ chunk.token_estimate }} tokens</span>
                                            </div>
                                            <p class="text-[11px] leading-relaxed text-muted-foreground line-clamp-3">{{ chunk.content }}</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>
    </AppLayout>

    <ConfirmDeleteDialog
        :open="!!deleteSourceTarget"
        title="Delete Knowledge Source"
        :description="`Are you sure you want to delete '${deleteSourceTarget?.title}'? This information will be removed from your chatbot's memory immediately.`"
        :processing="deletingSource"
        @close="deleteSourceTarget = null"
        @confirm="executeDeleteSource"
    />
</template>
