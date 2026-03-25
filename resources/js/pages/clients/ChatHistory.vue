<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import {
    ArrowLeft,
    Clock,
    Globe,
    Monitor,
    User,
    Bot,
    Zap,
    MessageSquare,
} from 'lucide-vue-next';
import { ref, computed } from 'vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import { dashboard } from '@/routes';
import type {
    BreadcrumbItem,
    ChatSessionRecord,
    ClientWorkspace,
} from '@/types';

type PaginatedSessions = {
    data: ChatSessionRecord[];
    current_page: number;
    per_page: number;
    next_page_url: string | null;
    prev_page_url: string | null;
};

type Props = {
    client: ClientWorkspace;
    sessions: PaginatedSessions;
};

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: dashboard() },
    { title: 'Clients', href: '/clients' },
    { title: props.client.name, href: `/clients/${props.client.id}` },
    { title: 'Chat History', href: `/clients/${props.client.id}/chat-history` },
];

const selectedSessionId = ref<number | null>(
    props.sessions.data.length > 0 ? props.sessions.data[0].id : null,
);

const selectedSession = computed(() => {
    return (
        props.sessions.data.find((s) => s.id === selectedSessionId.value) ||
        null
    );
});

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

function formatRelativeTime(value?: string | null): string {
    if (!value) {
        return '--';
    }

    const dateStr = value.endsWith('Z') ? value : value + 'Z';
    const date = new Date(dateStr);
    const now = new Date();
    const diffInSeconds = Math.floor((now.getTime() - date.getTime()) / 1000);

    if (diffInSeconds < 60) {
        return 'Just now';
    }

    const diffInMinutes = Math.floor(diffInSeconds / 60);

    if (diffInMinutes < 60) {
        return `${diffInMinutes}m ago`;
    }

    const diffInHours = Math.floor(diffInMinutes / 60);

    if (diffInHours < 24) {
        return `${diffInHours}h ago`;
    }

    const diffInDays = Math.floor(diffInHours / 24);

    if (diffInDays < 7) {
        return `${diffInDays}d ago`;
    }

    return date.toLocaleDateString(undefined, {
        month: 'short',
        day: 'numeric',
    });
}

function formatTimeOnly(value?: string | null): string {
    if (!value) {
        return '--';
    }

    const dateStr = value.endsWith('Z') ? value : value + 'Z';

    return new Date(dateStr).toLocaleTimeString(undefined, {
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit',
    });
}

function truncateUserAgent(ua?: string | null): string {
    if (!ua) {
        return 'Unknown device';
    }

    if (ua.length > 60) {
        return ua.substring(0, 60) + '...';
    }

    return ua;
}
</script>

<template>
    <Head :title="`Chat History - ${client.name}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div
            class="flex flex-1 flex-col gap-6 p-4 md:h-[calc(100vh-4rem)] md:p-6"
        >
            <!-- Header -->
            <div
                class="flex shrink-0 flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
            >
                <div>
                    <h1 class="text-2xl font-semibold tracking-tight">
                        Chat History
                    </h1>
                    <p class="mt-1 text-sm text-muted-foreground">
                        Conversations from visitors interacting with
                        {{ client.name }}'s chatbot.
                    </p>
                </div>
                <Button variant="outline" size="sm" as-child>
                    <Link :href="`/clients/${client.id}`">
                        <ArrowLeft class="mr-1 size-3.5" />
                        Back to client
                    </Link>
                </Button>
            </div>

            <div
                class="flex min-h-[600px] flex-1 flex-col overflow-hidden rounded-xl border border-sidebar-border/70 bg-card md:min-h-0 md:flex-row"
            >
                <!-- Sidebar (List) -->
                <div
                    class="flex h-64 w-full flex-col overflow-hidden border-b border-sidebar-border/70 bg-muted/10 md:h-auto md:w-1/3 md:border-r md:border-b-0"
                >
                    <div
                        class="shrink-0 border-b border-sidebar-border/70 bg-card p-4"
                    >
                        <h2 class="text-sm font-semibold">
                            Recent Conversations
                        </h2>
                    </div>

                    <div class="flex-1 overflow-y-auto">
                        <div
                            v-if="sessions.data.length === 0"
                            class="p-8 text-center text-sm text-muted-foreground"
                        >
                            No chat sessions available.
                        </div>

                        <div class="divide-y divide-sidebar-border/50">
                            <button
                                v-for="session in sessions.data"
                                :key="session.id"
                                class="relative flex w-full flex-col gap-2 p-4 text-left transition-colors hover:bg-muted/40"
                                :class="{
                                    'bg-muted shadow-sm md:border-r-2 md:border-indigo-500':
                                        selectedSessionId === session.id,
                                }"
                                @click="selectedSessionId = session.id"
                            >
                                <div class="flex items-start justify-between">
                                    <div class="flex items-center gap-2">
                                        <div
                                            class="flex size-7 shrink-0 items-center justify-center rounded-full bg-blue-100 dark:bg-blue-900/40"
                                        >
                                            <User
                                                class="size-3.5 text-blue-600 dark:text-blue-400"
                                            />
                                        </div>
                                        <div>
                                            <span class="text-sm font-medium"
                                                >Visitor</span
                                            >
                                            <span
                                                v-if="session.visitor_ip"
                                                class="ml-2 text-xs text-muted-foreground"
                                                >{{ session.visitor_ip }}</span
                                            >
                                        </div>
                                    </div>
                                    <span
                                        class="text-[10px] whitespace-nowrap text-muted-foreground"
                                        >{{
                                            formatRelativeTime(
                                                session.created_at,
                                            )
                                        }}</span
                                    >
                                </div>
                                <div
                                    class="w-full truncate pr-4 text-xs text-muted-foreground"
                                >
                                    {{
                                        session.messages.length > 0
                                            ? session.messages[0].content
                                            : 'Empty session'
                                    }}
                                </div>
                                <div class="mt-1 flex items-center gap-2">
                                    <Badge variant="outline" class="text-[9px]"
                                        >{{ session.message_count }} msgs</Badge
                                    >
                                    <Badge
                                        variant="secondary"
                                        class="text-[9px]"
                                        >{{
                                            session.total_tokens.toLocaleString()
                                        }}
                                        tokens</Badge
                                    >
                                </div>
                            </button>
                        </div>
                    </div>

                    <!-- Pagination (Sidebar Bottom) -->
                    <div
                        class="shrink-0 border-t border-sidebar-border/70 bg-card p-3"
                        v-if="sessions.prev_page_url || sessions.next_page_url"
                    >
                        <div class="flex items-center justify-between">
                            <span class="text-xs text-muted-foreground"
                                >Page {{ sessions.current_page }}</span
                            >
                            <div class="flex gap-1">
                                <Button
                                    :disabled="!sessions.prev_page_url"
                                    variant="outline"
                                    size="icon"
                                    class="h-7 w-7"
                                    as-child
                                >
                                    <Link
                                        v-if="sessions.prev_page_url"
                                        :href="sessions.prev_page_url"
                                        preserve-scroll
                                    >
                                        &larr;
                                    </Link>
                                    <span v-else>&larr;</span>
                                </Button>
                                <Button
                                    :disabled="!sessions.next_page_url"
                                    variant="outline"
                                    size="icon"
                                    class="h-7 w-7"
                                    as-child
                                >
                                    <Link
                                        v-if="sessions.next_page_url"
                                        :href="sessions.next_page_url"
                                        preserve-scroll
                                    >
                                        &rarr;
                                    </Link>
                                    <span v-else>&rarr;</span>
                                </Button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Main Area (Messages) -->
                <div
                    class="relative flex min-h-[400px] flex-1 flex-col overflow-hidden bg-card md:min-h-0"
                >
                    <template v-if="selectedSession">
                        <!-- Header -->
                        <div
                            class="flex shrink-0 flex-wrap items-center gap-4 border-b border-sidebar-border/70 bg-muted/5 p-4 text-sm"
                        >
                            <div class="flex items-center gap-2 font-medium">
                                <MessageSquare
                                    class="size-4 text-muted-foreground"
                                />
                                Conversation details
                            </div>
                            <div
                                class="flex flex-wrap gap-4 text-xs text-muted-foreground md:ml-auto"
                            >
                                <span
                                    v-if="selectedSession.page_url"
                                    class="flex items-center gap-1"
                                >
                                    <Globe class="size-3" />
                                    <a
                                        :href="selectedSession.page_url"
                                        target="_blank"
                                        class="max-w-[150px] truncate hover:underline"
                                        :title="selectedSession.page_url"
                                        >{{ selectedSession.page_url }}</a
                                    >
                                </span>
                                <span
                                    v-if="selectedSession.user_agent"
                                    class="flex items-center gap-1"
                                    :title="selectedSession.user_agent"
                                >
                                    <Monitor class="size-3" />
                                    {{
                                        truncateUserAgent(
                                            selectedSession.user_agent,
                                        )
                                    }}
                                </span>
                                <span class="flex items-center gap-1">
                                    <Clock class="size-3" />
                                    {{
                                        formatDateTime(
                                            selectedSession.created_at,
                                        )
                                    }}
                                </span>
                            </div>
                        </div>

                        <!-- Chat Messages Container -->
                        <div
                            class="flex-1 space-y-6 overflow-y-auto p-4 md:p-6"
                        >
                            <div
                                v-for="message in selectedSession.messages"
                                :key="message.id"
                                class="flex w-full"
                                :class="
                                    message.role === 'user'
                                        ? 'justify-end'
                                        : 'justify-start'
                                "
                            >
                                <div
                                    class="flex max-w-[85%] gap-3 md:max-w-[75%]"
                                    :class="
                                        message.role === 'user'
                                            ? 'flex-row-reverse'
                                            : 'flex-row'
                                    "
                                >
                                    <!-- Avatar -->
                                    <div
                                        class="mt-auto mb-1 flex size-8 shrink-0 items-center justify-center rounded-full"
                                        :class="
                                            message.role === 'user'
                                                ? 'bg-blue-600 text-white'
                                                : 'bg-emerald-600 text-white'
                                        "
                                    >
                                        <User
                                            v-if="message.role === 'user'"
                                            class="size-4"
                                        />
                                        <Bot v-else class="size-4" />
                                    </div>

                                    <!-- Bubble -->
                                    <div
                                        class="flex flex-col gap-1.5"
                                        :class="
                                            message.role === 'user'
                                                ? 'items-end'
                                                : 'items-start'
                                        "
                                    >
                                        <div
                                            class="rounded-2xl px-4 py-3 text-sm shadow-sm"
                                            :class="
                                                message.role === 'user'
                                                    ? 'rounded-br-sm bg-blue-600 text-white'
                                                    : 'rounded-bl-sm border border-sidebar-border/50 bg-muted text-foreground'
                                            "
                                        >
                                            <p
                                                class="leading-relaxed whitespace-pre-wrap"
                                            >
                                                {{ message.content }}
                                            </p>
                                        </div>
                                        <div
                                            class="flex items-center gap-2 px-1 opacity-70"
                                        >
                                            <span
                                                class="text-[10px] text-muted-foreground"
                                                >{{
                                                    formatTimeOnly(
                                                        message.created_at,
                                                    )
                                                }}</span
                                            >
                                            <Badge
                                                v-if="message.from_cache"
                                                variant="secondary"
                                                class="h-4 border-emerald-200 bg-emerald-100 px-1 py-0 text-[8px] text-emerald-800 hover:bg-emerald-200 dark:bg-emerald-900/30 dark:text-emerald-400"
                                            >
                                                <Zap class="mr-0.5 size-2" />
                                                cached
                                            </Badge>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div
                                v-if="selectedSession.messages.length === 0"
                                class="flex h-full items-center justify-center text-sm text-muted-foreground"
                            >
                                No messages logged for this session.
                            </div>
                        </div>
                    </template>
                    <div
                        v-else
                        class="flex h-full flex-col items-center justify-center space-y-3 p-8 text-center text-muted-foreground"
                    >
                        <div
                            class="flex size-12 items-center justify-center rounded-full bg-muted"
                        >
                            <MessageSquare class="size-5" />
                        </div>
                        <p>
                            Select a conversation from the left to view the chat
                            history.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
