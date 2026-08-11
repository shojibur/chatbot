<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import axios from 'axios';
import DOMPurify from 'dompurify';
import {
    Bot,
    Clock,
    Globe,
    HandMetal,
    Loader2,
    MessageSquare,
    Monitor,
    Radio,
    Send,
    User as UserIcon,
    Zap,
} from 'lucide-vue-next';
import { marked } from 'marked';
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';

// ── Types ─────────────────────────────────────────────────────────────────────

type Session = {
    id: number;
    session_token: string;
    visitor_ip: string | null;
    visitor_identifier: string | null;
    first_message: string | null;
    page_url: string | null;
    user_agent: string | null;
    message_count: number;
    total_tokens: number;
    last_activity_at: string | null;
    created_at: string | null;
    is_active: boolean;
    is_human_takeover: boolean;
    taken_over_by_user_id: number | null;
    taken_over_at: string | null;
};

type Message = {
    id: number;
    role: string;
    content: string;
    token_count: number;
    from_cache: boolean;
    source: string;
    sent_by_name: string | null;
    human_takeover: boolean;
    created_at: string | null;
};

// ── Props & breadcrumbs ───────────────────────────────────────────────────────

const props = defineProps<{ sessions: Session[] }>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/portal/dashboard' },
    { title: 'Live Sessions', href: '/portal/live-sessions' },
];

// ── Reactive session list (updated by polling) ────────────────────────────────

const sessions = ref<Session[]>(props.sessions);

// ── Computed groupings ────────────────────────────────────────────────────────

const liveSessions = computed(() =>
    sessions.value.filter((s) => s.is_human_takeover || s.is_active),
);
const historySessions = computed(() =>
    sessions.value.filter((s) => !s.is_human_takeover && !s.is_active),
);

// ── Selected session ──────────────────────────────────────────────────────────

const selectedId = ref<number | null>(liveSessions.value[0]?.id ?? null);
const selectedSession = computed(
    () => sessions.value.find((s) => s.id === selectedId.value) ?? null,
);

// ── Messages ──────────────────────────────────────────────────────────────────

const loadedMessages = ref<Record<number, Message[]>>({});
const loadingMessages = ref(false);
const messagesEl = ref<HTMLElement | null>(null);

async function loadMessages(id: number, silent = false): Promise<void> {
    if (!silent) loadingMessages.value = true;
    try {
        const res = await axios.get(`/portal/live-sessions/${id}/messages`);
        const incoming: Message[] = res.data.messages ?? [];
        const current = loadedMessages.value[id] ?? [];

        // Only update + scroll if something actually changed
        if (JSON.stringify(incoming.map((m) => m.id)) !== JSON.stringify(current.map((m) => m.id))) {
            loadedMessages.value[id] = incoming;
            // Also refresh session meta (takeover status may have changed)
            const updatedSession: Session = res.data.session;
            if (updatedSession) {
                sessions.value = sessions.value.map((s) =>
                    s.id === updatedSession.id ? { ...s, ...updatedSession } : s,
                );
            }
            await nextTick();
            scrollToBottom();
        }
    } catch {
        if (!loadedMessages.value[id]) loadedMessages.value[id] = [];
    } finally {
        if (!silent) loadingMessages.value = false;
    }
}

watch(
    selectedId,
    async (id) => {
        if (!id) return;
        await loadMessages(id);
    },
    { immediate: true },
);

const currentMessages = computed(() =>
    selectedId.value ? (loadedMessages.value[selectedId.value] ?? []) : [],
);

function scrollToBottom(): void {
    if (messagesEl.value) messagesEl.value.scrollTop = messagesEl.value.scrollHeight;
}

// ── Polling ───────────────────────────────────────────────────────────────────

let pollTimer: ReturnType<typeof setInterval> | null = null;

async function pollSessions(): Promise<void> {
    try {
        const res = await axios.get('/portal/live-sessions', {
            headers: { 'X-Inertia': 'true', 'X-Inertia-Version': '' },
        });
        // Inertia returns JSON props when called with the header
        const incoming: Session[] = res.data?.props?.sessions ?? null;
        if (incoming) {
            sessions.value = incoming;
        }
    } catch {
        // silent — keep showing what we have
    }

    // Always refresh open session messages silently
    if (selectedId.value) {
        await loadMessages(selectedId.value, true);
    }
}

onMounted(() => {
    pollTimer = setInterval(pollSessions, 8000);
});

onBeforeUnmount(() => {
    if (pollTimer) clearInterval(pollTimer);
});

// ── Takeover / release ────────────────────────────────────────────────────────

const savingTakeover = ref(false);
const takeoverError = ref<string | null>(null);

async function handleTakeover(): Promise<void> {
    if (!selectedSession.value) return;
    savingTakeover.value = true;
    takeoverError.value = null;
    try {
        const isTakenOver = selectedSession.value.is_human_takeover;
        const url = isTakenOver
            ? `/portal/live-sessions/${selectedSession.value.id}/release`
            : `/portal/live-sessions/${selectedSession.value.id}/takeover`;

        const res = await axios.post(url);
        const updated: Session = res.data.session;
        sessions.value = sessions.value.map((s) => (s.id === updated.id ? updated : s));
    } catch (err: any) {
        takeoverError.value = err?.response?.data?.message ?? 'Something went wrong.';
    } finally {
        savingTakeover.value = false;
    }
}

// ── Send message ──────────────────────────────────────────────────────────────

const replyText = ref('');
const sendingMessage = ref(false);
const sendError = ref<string | null>(null);

async function handleSend(): Promise<void> {
    if (!selectedSession.value || !replyText.value.trim()) return;
    sendingMessage.value = true;
    sendError.value = null;
    try {
        const res = await axios.post(
            `/portal/live-sessions/${selectedSession.value.id}/send-message`,
            { content: replyText.value.trim() },
        );
        const updatedSession: Session = res.data.session;
        const newMessage: Message = res.data.message;

        sessions.value = sessions.value.map((s) =>
            s.id === updatedSession.id ? updatedSession : s,
        );
        loadedMessages.value[updatedSession.id] = [
            ...(loadedMessages.value[updatedSession.id] ?? []),
            newMessage,
        ];
        replyText.value = '';
        await nextTick();
        scrollToBottom();
    } catch (err: any) {
        sendError.value = err?.response?.data?.message ?? 'Failed to send message.';
    } finally {
        sendingMessage.value = false;
    }
}

function onKeydown(e: KeyboardEvent): void {
    if (e.key === 'Enter' && !e.shiftKey) {
        e.preventDefault();
        handleSend();
    }
}

// ── Markdown ──────────────────────────────────────────────────────────────────

marked.setOptions({ breaks: true, gfm: true });

function parseMarkdown(text?: string | null): string {
    if (!text) return '';
    return DOMPurify.sanitize(marked.parse(text) as string, {
        ALLOWED_TAGS: [
            'b', 'i', 'em', 'strong', 'a', 'p', 'br',
            'ul', 'ol', 'li', 'code', 'pre', 'blockquote',
            'h1', 'h2', 'h3', 'h4', 'h5', 'h6',
            'hr', 'table', 'thead', 'tbody', 'tr', 'th', 'td',
        ],
    });
}

// ── Helpers ───────────────────────────────────────────────────────────────────

function relative(v?: string | null): string {
    if (!v) return '--';
    const sec = Math.floor(
        (Date.now() - new Date(v.endsWith('Z') ? v : v + 'Z').getTime()) / 1000,
    );
    if (sec < 60) return 'Just now';
    if (sec < 3600) return `${Math.floor(sec / 60)}m ago`;
    if (sec < 86400) return `${Math.floor(sec / 3600)}h ago`;
    return `${Math.floor(sec / 86400)}d ago`;
}

function fmt(v?: string | null): string {
    if (!v) return '--';
    return new Date(v.endsWith('Z') ? v : v + 'Z').toLocaleString(undefined, {
        year: 'numeric', month: 'short', day: 'numeric',
        hour: '2-digit', minute: '2-digit',
    });
}

function fmtTime(v?: string | null): string {
    if (!v) return '';
    return new Date(v.endsWith('Z') ? v : v + 'Z').toLocaleTimeString(undefined, {
        hour: '2-digit', minute: '2-digit',
    });
}

function visitorName(s: Session): string {
    return s.visitor_identifier || s.visitor_ip || 'Anonymous';
}
</script>

<template>
    <Head title="Live Sessions" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col overflow-hidden p-4 md:p-6 gap-4" style="height: calc(100dvh - 4rem);">

            <!-- Header -->
            <div class="flex shrink-0 flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h1 class="text-2xl font-bold tracking-tight">Live Sessions</h1>
                    <p class="mt-1 text-sm text-muted-foreground">
                        Monitor active visitor conversations and take over from the AI.
                    </p>
                </div>
                <div class="flex items-center gap-2">
                    <Badge
                        v-if="liveSessions.length > 0"
                        class="gap-1.5 border-emerald-300 bg-emerald-50 text-emerald-700 dark:bg-emerald-900/20 dark:text-emerald-400"
                        variant="outline"
                    >
                        <span class="inline-block h-1.5 w-1.5 animate-pulse rounded-full bg-emerald-500" />
                        {{ liveSessions.length }} active
                    </Badge>
                    <Badge v-else variant="outline" class="text-muted-foreground">
                        No active sessions
                    </Badge>
                </div>
            </div>

            <!-- Split panel -->
            <div class="flex flex-1 min-h-0 overflow-hidden rounded-xl border border-sidebar-border/60 bg-card shadow-sm">

                <!-- ── LEFT: session list ─────────────────────────────────── -->
                <div class="flex w-72 shrink-0 flex-col border-r border-sidebar-border/50">
                    <div class="flex-1 overflow-y-auto">

                        <!-- Empty state -->
                        <div
                            v-if="sessions.length === 0"
                            class="flex flex-col items-center justify-center gap-3 p-10 text-center text-muted-foreground"
                        >
                            <Radio class="h-10 w-10 opacity-20" />
                            <p class="text-sm">No sessions yet. They appear here as visitors start chatting.</p>
                        </div>

                        <template v-else>
                            <!-- Active section -->
                            <template v-if="liveSessions.length > 0">
                                <div class="sticky top-0 z-10 flex items-center gap-2 bg-card px-4 py-2 border-b border-sidebar-border/30">
                                    <span class="inline-block h-1.5 w-1.5 animate-pulse rounded-full bg-emerald-500" />
                                    <span class="text-[11px] font-semibold uppercase tracking-wider text-emerald-600 dark:text-emerald-400">
                                        Active · {{ liveSessions.length }}
                                    </span>
                                </div>

                                <button
                                    v-for="s in liveSessions"
                                    :key="s.id"
                                    class="group w-full text-left flex flex-col gap-1.5 px-4 py-3.5 transition-colors hover:bg-muted/30 border-b border-sidebar-border/20"
                                    :class="selectedId === s.id ? 'bg-primary/5 border-r-2 border-primary' : ''"
                                    @click="selectedId = s.id"
                                >
                                    <div class="flex items-center justify-between gap-2">
                                        <div class="flex items-center gap-2 min-w-0">
                                            <div class="relative flex h-6 w-6 shrink-0 items-center justify-center rounded-full text-white"
                                                :class="s.is_human_takeover ? 'bg-primary' : 'bg-amber-500'">
                                                <UserIcon class="h-3 w-3" />
                                                <span class="absolute -top-0.5 -right-0.5 h-2 w-2 rounded-full border border-card"
                                                    :class="s.is_human_takeover ? 'bg-emerald-400' : 'bg-amber-300 animate-pulse'" />
                                            </div>
                                            <span class="truncate text-sm font-medium">{{ visitorName(s) }}</span>
                                        </div>
                                        <span class="shrink-0 text-[10px] text-muted-foreground">
                                            {{ relative(s.last_activity_at) }}
                                        </span>
                                    </div>

                                    <p class="truncate pl-8 text-xs text-muted-foreground">
                                        {{ s.first_message || 'No preview' }}
                                    </p>

                                    <div class="flex items-center gap-1.5 pl-8">
                                        <Badge
                                            variant="outline"
                                            class="h-4 px-1.5 text-[9px]"
                                            :class="s.is_human_takeover
                                                ? 'border-primary/30 bg-primary/10 text-primary'
                                                : 'border-amber-300 bg-amber-50 text-amber-700 dark:bg-amber-900/20 dark:text-amber-400'"
                                        >
                                            {{ s.is_human_takeover ? 'You\'re live' : 'Active now' }}
                                        </Badge>
                                        <span class="text-[10px] text-muted-foreground">{{ s.message_count }} msgs</span>
                                    </div>
                                </button>
                            </template>

                            <!-- History section -->
                            <template v-if="historySessions.length > 0">
                                <div class="sticky top-0 z-10 flex items-center gap-2 bg-card px-4 py-2 border-b border-sidebar-border/30">
                                    <span class="text-[11px] font-semibold uppercase tracking-wider text-muted-foreground">
                                        History · {{ historySessions.length }}
                                    </span>
                                </div>

                                <button
                                    v-for="s in historySessions"
                                    :key="s.id"
                                    class="group w-full text-left flex flex-col gap-1.5 px-4 py-3.5 transition-colors hover:bg-muted/30 border-b border-sidebar-border/20"
                                    :class="selectedId === s.id ? 'bg-primary/5 border-r-2 border-primary' : ''"
                                    @click="selectedId = s.id"
                                >
                                    <div class="flex items-center justify-between gap-2">
                                        <div class="flex items-center gap-2 min-w-0">
                                            <div class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-muted text-muted-foreground">
                                                <UserIcon class="h-3 w-3" />
                                            </div>
                                            <span class="truncate text-sm font-medium">{{ visitorName(s) }}</span>
                                        </div>
                                        <span class="shrink-0 text-[10px] text-muted-foreground">
                                            {{ relative(s.last_activity_at) }}
                                        </span>
                                    </div>
                                    <p class="truncate pl-8 text-xs text-muted-foreground">
                                        {{ s.first_message || 'No preview' }}
                                    </p>
                                    <div class="flex items-center gap-1.5 pl-8">
                                        <Badge variant="outline" class="h-4 px-1.5 text-[9px]">
                                            {{ s.message_count }} msgs
                                        </Badge>
                                    </div>
                                </button>
                            </template>
                        </template>
                    </div>
                </div>

                <!-- ── RIGHT: chat thread ─────────────────────────────────── -->
                <div class="flex flex-1 min-w-0 flex-col overflow-hidden">

                    <template v-if="selectedSession">

                        <!-- Meta bar -->
                        <div class="shrink-0 flex flex-wrap items-center gap-x-4 gap-y-1.5 border-b border-sidebar-border/40 bg-muted/5 px-5 py-2.5 text-xs text-muted-foreground">
                            <span class="flex items-center gap-1.5 font-semibold text-foreground">
                                <MessageSquare class="h-3.5 w-3.5" />
                                {{ visitorName(selectedSession) }}
                            </span>
                            <span v-if="selectedSession.page_url" class="flex items-center gap-1 truncate max-w-[200px]">
                                <Globe class="h-3 w-3 shrink-0" />
                                <a :href="selectedSession.page_url" target="_blank" class="truncate hover:underline">
                                    {{ selectedSession.page_url }}
                                </a>
                            </span>
                            <span v-if="selectedSession.user_agent" class="hidden xl:flex items-center gap-1">
                                <Monitor class="h-3 w-3 shrink-0" />
                                <span class="truncate max-w-[160px]">{{ (selectedSession.user_agent || '').slice(0, 55) }}…</span>
                            </span>
                            <span class="flex items-center gap-1">
                                <Clock class="h-3 w-3" />
                                {{ fmt(selectedSession.created_at) }}
                            </span>

                            <!-- Takeover pill -->
                            <div class="ml-auto flex items-center gap-2 shrink-0">
                                <span v-if="takeoverError" class="text-destructive text-[10px]">{{ takeoverError }}</span>
                                <button
                                    class="flex items-center gap-1.5 rounded-full border px-3 py-1 text-xs font-medium transition-colors"
                                    :class="selectedSession.is_human_takeover
                                        ? 'border-primary bg-primary text-primary-foreground hover:bg-primary/90'
                                        : 'border-sidebar-border/60 bg-muted/30 text-muted-foreground hover:bg-muted/60'"
                                    :disabled="savingTakeover"
                                    @click="handleTakeover"
                                >
                                    <Loader2 v-if="savingTakeover" class="h-3 w-3 animate-spin" />
                                    <HandMetal v-else class="h-3 w-3" />
                                    {{ selectedSession.is_human_takeover ? 'Live — hand back to AI' : 'Take over' }}
                                </button>
                            </div>
                        </div>

                        <!-- Takeover / AI banner -->
                        <div
                            v-if="selectedSession.is_human_takeover"
                            class="shrink-0 flex items-center gap-2 border-b border-primary/20 bg-primary/5 px-5 py-2 text-xs text-primary"
                        >
                            <HandMetal class="h-3.5 w-3.5" />
                            You are live — your replies go directly to the visitor.
                        </div>
                        <div
                            v-else-if="selectedSession.is_active"
                            class="shrink-0 flex items-center gap-2 border-b border-amber-200 bg-amber-50 px-5 py-2 text-xs text-amber-700 dark:bg-amber-900/10 dark:text-amber-400 dark:border-amber-800"
                        >
                            <span class="inline-block h-1.5 w-1.5 animate-pulse rounded-full bg-amber-500" />
                            Visitor is active — AI is responding. Tap "Take over" to reply yourself.
                        </div>

                        <!-- Scrollable messages -->
                        <div ref="messagesEl" class="flex-1 overflow-y-auto space-y-5 px-5 py-5">

                            <div v-if="loadingMessages" class="flex h-full min-h-32 items-center justify-center gap-2 text-sm text-muted-foreground">
                                <Loader2 class="h-4 w-4 animate-spin" />
                                Loading messages…
                            </div>

                            <template v-else>
                                <div
                                    v-for="msg in currentMessages"
                                    :key="msg.id"
                                    class="flex gap-3"
                                    :class="msg.role === 'user' ? 'flex-row-reverse' : 'flex-row'"
                                >
                                    <!-- Avatar -->
                                    <div
                                        class="mt-auto mb-1 flex h-7 w-7 shrink-0 items-center justify-center rounded-full"
                                        :class="msg.role === 'user'
                                            ? 'bg-blue-600 text-white'
                                            : msg.source === 'portal_operator' || msg.source === 'mobile_operator'
                                                ? 'bg-primary text-primary-foreground'
                                                : 'bg-emerald-600 text-white'"
                                    >
                                        <UserIcon v-if="msg.role === 'user'" class="h-3.5 w-3.5" />
                                        <HandMetal v-else-if="msg.source === 'portal_operator' || msg.source === 'mobile_operator'" class="h-3.5 w-3.5" />
                                        <Bot v-else class="h-3.5 w-3.5" />
                                    </div>

                                    <!-- Bubble -->
                                    <div
                                        class="flex max-w-[75%] flex-col gap-1"
                                        :class="msg.role === 'user' ? 'items-end' : 'items-start'"
                                    >
                                        <!-- Operator name label -->
                                        <span
                                            v-if="msg.sent_by_name"
                                            class="px-1 text-[10px] text-muted-foreground"
                                        >
                                            {{ msg.sent_by_name }}
                                        </span>

                                        <div
                                            class="rounded-2xl px-4 py-2.5 text-sm shadow-sm"
                                            :class="msg.role === 'user'
                                                ? 'rounded-br-sm bg-blue-600 text-white'
                                                : (msg.source === 'portal_operator' || msg.source === 'mobile_operator')
                                                    ? 'rounded-bl-sm border border-primary/30 bg-primary/10 text-foreground'
                                                    : 'rounded-bl-sm border border-sidebar-border/40 bg-muted/50 text-foreground'"
                                        >
                                            <div
                                                v-if="msg.role === 'assistant' && msg.source !== 'portal_operator' && msg.source !== 'mobile_operator'"
                                                class="[&_a]:underline [&_blockquote]:border-l-2 [&_blockquote]:border-sidebar-border [&_blockquote]:pl-3 [&_code]:rounded [&_code]:bg-black/5 [&_code]:px-1 [&_code]:py-0.5 [&_ol]:mb-3 [&_ol]:list-decimal [&_ol]:pl-5 [&_p]:mb-3 [&_p:last-child]:mb-0 [&_pre]:overflow-x-auto [&_pre]:rounded [&_pre]:bg-black/5 [&_pre]:p-3 [&_ul]:mb-3 [&_ul]:list-disc [&_ul]:pl-5 leading-relaxed"
                                                v-html="parseMarkdown(msg.content)"
                                            />
                                            <p v-else class="whitespace-pre-wrap leading-relaxed">{{ msg.content }}</p>
                                        </div>

                                        <div class="flex items-center gap-2 px-1">
                                            <span class="text-[10px] text-muted-foreground tabular-nums">{{ fmtTime(msg.created_at) }}</span>
                                            <Badge
                                                v-if="msg.from_cache"
                                                variant="secondary"
                                                class="h-4 px-1.5 text-[8px] border-emerald-200 bg-emerald-50 text-emerald-700 dark:bg-emerald-900/20 dark:text-emerald-400"
                                            >
                                                <Zap class="mr-0.5 h-2 w-2" /> cached
                                            </Badge>
                                            <Badge
                                                v-if="msg.source === 'portal_operator' || msg.source === 'mobile_operator'"
                                                variant="secondary"
                                                class="h-4 px-1.5 text-[8px] border-primary/20 bg-primary/10 text-primary"
                                            >
                                                <HandMetal class="mr-0.5 h-2 w-2" /> human
                                            </Badge>
                                        </div>
                                    </div>
                                </div>

                                <div
                                    v-if="!loadingMessages && currentMessages.length === 0"
                                    class="flex min-h-32 flex-col items-center justify-center gap-2 text-muted-foreground"
                                >
                                    <MessageSquare class="h-8 w-8 opacity-20" />
                                    <p class="text-sm">No messages yet.</p>
                                </div>
                            </template>
                        </div>

                        <!-- Reply box (only when taken over) -->
                        <div
                            v-if="selectedSession.is_human_takeover"
                            class="shrink-0 border-t border-sidebar-border/40 bg-card p-3"
                        >
                            <div v-if="sendError" class="mb-2 rounded-md border border-destructive/30 bg-destructive/10 px-3 py-1.5 text-xs text-destructive">
                                {{ sendError }}
                            </div>
                            <div class="flex items-end gap-2">
                                <textarea
                                    v-model="replyText"
                                    placeholder="Type your reply… (Enter to send, Shift+Enter for new line)"
                                    rows="2"
                                    class="flex-1 resize-none rounded-lg border border-input bg-muted/30 px-3 py-2 text-sm outline-none focus:border-ring focus:ring-1 focus:ring-ring"
                                    :disabled="sendingMessage"
                                    @keydown="onKeydown"
                                />
                                <Button
                                    size="icon"
                                    class="h-10 w-10 shrink-0"
                                    :disabled="sendingMessage || !replyText.trim()"
                                    @click="handleSend"
                                >
                                    <Loader2 v-if="sendingMessage" class="h-4 w-4 animate-spin" />
                                    <Send v-else class="h-4 w-4" />
                                </Button>
                            </div>
                        </div>

                        <!-- CTA when not yet taken over but session is active -->
                        <div
                            v-else-if="selectedSession.is_active"
                            class="shrink-0 border-t border-sidebar-border/40 bg-card p-3"
                        >
                            <button
                                class="flex w-full items-center justify-center gap-2 rounded-lg border border-primary/30 bg-primary/5 py-3 text-sm text-primary hover:bg-primary/10 transition-colors"
                                :disabled="savingTakeover"
                                @click="handleTakeover"
                            >
                                <HandMetal class="h-4 w-4" />
                                Take over and reply as yourself
                            </button>
                        </div>
                    </template>

                    <!-- Nothing selected -->
                    <div
                        v-else
                        class="flex flex-1 flex-col items-center justify-center gap-3 text-muted-foreground"
                    >
                        <div class="flex h-14 w-14 items-center justify-center rounded-full bg-muted">
                            <Radio class="h-6 w-6 opacity-30" />
                        </div>
                        <p class="text-sm">Select a session to view the conversation.</p>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
