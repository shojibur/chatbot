<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import axios from 'axios';
import {
    Bot,
    Clock,
    Globe,
    Loader2,
    MessageSquare,
    Monitor,
    Search,
    User as UserIcon,
    Zap,
} from 'lucide-vue-next';
import { computed, nextTick, ref, watch } from 'vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';

type ChatSession = {
    id: number;
    visitor_ip?: string | null;
    visitor_identifier?: string | null;
    first_message?: string | null;
    page_url?: string | null;
    user_agent?: string | null;
    message_count: number;
    total_tokens: number;
    created_at: string;
};

type PaginatedSessions = {
    data: ChatSession[];
    current_page: number;
    per_page: number;
    next_page_url: string | null;
    prev_page_url: string | null;
};

const props = defineProps<{ sessions: PaginatedSessions }>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/portal/dashboard' },
    { title: 'Chat History', href: '/portal/chat-history' },
];

// ── Session selection ─────────────────────────────────────────────────────────
const selectedId = ref<number | null>(props.sessions.data[0]?.id ?? null);
const selectedSession = computed(
    () => props.sessions.data.find((s) => s.id === selectedId.value) ?? null,
);

// ── Search ────────────────────────────────────────────────────────────────────
const search = ref('');
const filteredSessions = computed(() => {
    const q = search.value.toLowerCase().trim();
    if (!q) return props.sessions.data;
    return props.sessions.data.filter(
        (s) =>
            s.visitor_ip?.toLowerCase().includes(q) ||
            s.first_message?.toLowerCase().includes(q) ||
            s.page_url?.toLowerCase().includes(q),
    );
});

// ── Messages (lazy) ───────────────────────────────────────────────────────────
const loadedMessages = ref<Record<number, any[]>>({});
const loadingMessages = ref(false);
const messagesEl = ref<HTMLElement | null>(null);

watch(
    selectedId,
    async (id) => {
        if (!id) return;
        if (!loadedMessages.value[id]) {
            loadingMessages.value = true;
            try {
                const res = await axios.get(`/portal/chat-history/${id}/messages`);
                loadedMessages.value[id] = res.data.messages ?? [];
            } catch {
                loadedMessages.value[id] = [];
            } finally {
                loadingMessages.value = false;
            }
        }
        nextTick(() => {
            if (messagesEl.value) messagesEl.value.scrollTop = messagesEl.value.scrollHeight;
        });
    },
    { immediate: true },
);

const currentMessages = computed(() =>
    selectedId.value ? (loadedMessages.value[selectedId.value] ?? []) : [],
);

// ── Helpers ───────────────────────────────────────────────────────────────────
function relative(v?: string | null) {
    if (!v) return '--';
    const sec = Math.floor(
        (Date.now() - new Date(v.endsWith('Z') ? v : v + 'Z').getTime()) / 1000,
    );
    if (sec < 60) return 'Just now';
    if (sec < 3600) return `${Math.floor(sec / 60)}m ago`;
    if (sec < 86400) return `${Math.floor(sec / 3600)}h ago`;
    return `${Math.floor(sec / 86400)}d ago`;
}

function fmt(v?: string | null) {
    if (!v) return '--';
    return new Date(v.endsWith('Z') ? v : v + 'Z').toLocaleString(undefined, {
        year: 'numeric', month: 'short', day: 'numeric',
        hour: '2-digit', minute: '2-digit',
    });
}

function fmtTime(v?: string | null) {
    if (!v) return '';
    return new Date(v.endsWith('Z') ? v : v + 'Z').toLocaleTimeString(undefined, {
        hour: '2-digit', minute: '2-digit',
    });
}
</script>

<template>
    <Head title="Chat History" />

    <!--
        Key layout strategy:
        - AppLayout provides a flex-col container that fills the viewport.
        - We take all remaining space with `flex-1 min-h-0` and `overflow-hidden`.
        - The split panel uses `flex` row; each column uses `overflow-hidden` + `flex-col`.
        - The scrollable sections inside each column use `flex-1 overflow-y-auto`.
        This means ONLY the inner lists scroll, never the outer page.
    -->
    <AppLayout :breadcrumbs="breadcrumbs">
        <!--
            h-[calc(100dvh-4rem)] = full viewport minus the 4rem (h-16) AppSidebarHeader.
            overflow-hidden at this level stops the page from ever scrolling.
            Each inner panel uses flex-1 + overflow-y-auto to scroll within itself.
        -->
        <div class="flex flex-col overflow-hidden p-4 md:p-6 gap-4" style="height: calc(100dvh - 4rem);">

            <!-- Header row (fixed, does not scroll) -->
            <div class="flex shrink-0 flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h1 class="text-2xl font-bold tracking-tight">Chat History</h1>
                    <p class="mt-1 text-sm text-muted-foreground">
                        All visitor conversations with your chatbot.
                    </p>
                </div>
                <Badge variant="outline" class="w-fit">
                    {{ sessions.data.length }} sessions · Page {{ sessions.current_page }}
                </Badge>
            </div>

            <!-- Split panel — fills all remaining height, no outer scroll -->
            <div class="flex flex-1 min-h-0 overflow-hidden rounded-xl border border-sidebar-border/60 bg-card shadow-sm">

                <!-- ── LEFT: session list ─────────────────────────────────── -->
                <div class="flex w-72 shrink-0 flex-col border-r border-sidebar-border/50">

                    <!-- Search (fixed) -->
                    <div class="shrink-0 border-b border-sidebar-border/40 p-3">
                        <div class="relative">
                            <Search class="absolute left-3 top-1/2 h-3.5 w-3.5 -translate-y-1/2 text-muted-foreground/60" />
                            <input
                                v-model="search"
                                type="text"
                                placeholder="Search conversations…"
                                class="h-8 w-full rounded-lg border border-input bg-muted/30 pl-8 pr-3 text-sm outline-none focus:border-ring focus:ring-1 focus:ring-ring"
                            />
                        </div>
                    </div>

                    <!-- Scrollable session list -->
                    <div class="flex-1 overflow-y-auto divide-y divide-sidebar-border/30">
                        <p
                            v-if="filteredSessions.length === 0"
                            class="p-8 text-center text-sm text-muted-foreground"
                        >
                            {{ search ? 'No sessions match.' : 'No conversations yet.' }}
                        </p>

                        <button
                            v-for="s in filteredSessions"
                            :key="s.id"
                            class="group w-full text-left flex flex-col gap-1.5 px-4 py-3.5 transition-colors hover:bg-muted/30"
                            :class="selectedId === s.id ? 'bg-primary/5 border-r-2 border-primary' : ''"
                            @click="selectedId = s.id"
                        >
                            <div class="flex items-center justify-between gap-2">
                                <div class="flex items-center gap-2 min-w-0">
                                    <div
                                        class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full text-white"
                                        :class="selectedId === s.id ? 'bg-primary' : 'bg-blue-500/70'"
                                    >
                                        <UserIcon class="h-3 w-3" />
                                    </div>
                                    <span class="truncate text-sm font-medium">
                                        {{ s.visitor_identifier || s.visitor_ip || 'Anonymous' }}
                                    </span>
                                </div>
                                <span class="shrink-0 text-[10px] text-muted-foreground">
                                    {{ relative(s.created_at) }}
                                </span>
                            </div>

                            <p class="truncate pl-8 text-xs text-muted-foreground">
                                {{ s.first_message || 'No message preview' }}
                            </p>

                            <div class="flex items-center gap-1.5 pl-8">
                                <Badge variant="outline" class="h-4 px-1.5 text-[9px]">
                                    {{ s.message_count }} msgs
                                </Badge>
                                <span class="text-[10px] text-muted-foreground tabular-nums">
                                    {{ s.total_tokens?.toLocaleString() }} tokens
                                </span>
                            </div>
                        </button>
                    </div>

                    <!-- Pagination (fixed at bottom) -->
                    <div
                        v-if="sessions.prev_page_url || sessions.next_page_url"
                        class="shrink-0 flex items-center justify-between border-t border-sidebar-border/40 bg-muted/10 px-3 py-2"
                    >
                        <span class="text-xs text-muted-foreground">Page {{ sessions.current_page }}</span>
                        <div class="flex gap-1">
                            <Button size="icon" variant="outline" class="h-7 w-7" :disabled="!sessions.prev_page_url" as-child>
                                <Link v-if="sessions.prev_page_url" :href="sessions.prev_page_url" preserve-scroll>←</Link>
                                <span v-else>←</span>
                            </Button>
                            <Button size="icon" variant="outline" class="h-7 w-7" :disabled="!sessions.next_page_url" as-child>
                                <Link v-if="sessions.next_page_url" :href="sessions.next_page_url" preserve-scroll>→</Link>
                                <span v-else>→</span>
                            </Button>
                        </div>
                    </div>
                </div>

                <!-- ── RIGHT: messages ────────────────────────────────────── -->
                <div class="flex flex-1 min-w-0 flex-col overflow-hidden">

                    <template v-if="selectedSession">

                        <!-- Meta bar (fixed) -->
                        <div class="shrink-0 flex flex-wrap items-center gap-x-4 gap-y-1.5 border-b border-sidebar-border/40 bg-muted/5 px-5 py-2.5 text-xs text-muted-foreground">
                            <span class="flex items-center gap-1.5 font-semibold text-foreground">
                                <MessageSquare class="h-3.5 w-3.5" />
                                {{ selectedSession.visitor_identifier || selectedSession.visitor_ip || 'Anonymous' }}
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
                            <span class="flex items-center gap-1 ml-auto shrink-0">
                                <Clock class="h-3 w-3" />
                                {{ fmt(selectedSession.created_at) }}
                            </span>
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
                                        :class="msg.role === 'user' ? 'bg-blue-600 text-white' : 'bg-emerald-600 text-white'"
                                    >
                                        <UserIcon v-if="msg.role === 'user'" class="h-3.5 w-3.5" />
                                        <Bot v-else class="h-3.5 w-3.5" />
                                    </div>

                                    <!-- Bubble -->
                                    <div class="flex max-w-[75%] flex-col gap-1"
                                        :class="msg.role === 'user' ? 'items-end' : 'items-start'">
                                        <div
                                            class="rounded-2xl px-4 py-2.5 text-sm shadow-sm"
                                            :class="msg.role === 'user'
                                                ? 'rounded-br-sm bg-blue-600 text-white'
                                                : 'rounded-bl-sm border border-sidebar-border/40 bg-muted/50 text-foreground'"
                                        >
                                            <p class="whitespace-pre-wrap leading-relaxed">{{ msg.content }}</p>
                                        </div>
                                        <div class="flex items-center gap-2 px-1">
                                            <span class="text-[10px] text-muted-foreground tabular-nums">
                                                {{ fmtTime(msg.created_at) }}
                                            </span>
                                            <Badge
                                                v-if="msg.from_cache"
                                                variant="secondary"
                                                class="h-4 px-1.5 text-[8px] border-emerald-200 bg-emerald-50 text-emerald-700 dark:bg-emerald-900/20 dark:text-emerald-400"
                                            >
                                                <Zap class="mr-0.5 h-2 w-2" /> cached
                                            </Badge>
                                        </div>
                                    </div>
                                </div>

                                <div
                                    v-if="!loadingMessages && currentMessages.length === 0"
                                    class="flex min-h-32 flex-col items-center justify-center gap-2 text-muted-foreground"
                                >
                                    <MessageSquare class="h-8 w-8 opacity-20" />
                                    <p class="text-sm">No messages logged for this session.</p>
                                </div>
                            </template>
                        </div>
                    </template>

                    <!-- Nothing selected -->
                    <div
                        v-else
                        class="flex flex-1 flex-col items-center justify-center gap-3 text-muted-foreground"
                    >
                        <div class="flex h-14 w-14 items-center justify-center rounded-full bg-muted">
                            <MessageSquare class="h-6 w-6 opacity-30" />
                        </div>
                        <p class="text-sm">Select a conversation to view messages.</p>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
