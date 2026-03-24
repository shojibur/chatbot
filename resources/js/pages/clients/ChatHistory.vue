<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import {
    ArrowLeft,
    ChevronDown,
    ChevronUp,
    Clock,
    Globe,
    MessageSquare,
    Monitor,
    User,
    Bot,
    Zap,
} from 'lucide-vue-next';
import { ref } from 'vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader } from '@/components/ui/card';
import AppLayout from '@/layouts/AppLayout.vue';
import { dashboard } from '@/routes';
import type { BreadcrumbItem, ChatSessionRecord, ClientWorkspace } from '@/types';

type PaginatedSessions = {
    data: ChatSessionRecord[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
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

const expandedSessions = ref<Set<number>>(new Set());

function toggleSession(sessionId: number) {
    if (expandedSessions.value.has(sessionId)) {
        expandedSessions.value.delete(sessionId);
    } else {
        expandedSessions.value.add(sessionId);
    }
}

function formatDateTime(value?: string | null): string {
    if (!value) return '--';
    return new Date(value).toLocaleString(undefined, {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
}

function formatTimeOnly(value?: string | null): string {
    if (!value) return '--';
    return new Date(value).toLocaleTimeString(undefined, {
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit',
    });
}

function truncateUserAgent(ua?: string | null): string {
    if (!ua) return 'Unknown device';
    if (ua.length > 60) return ua.substring(0, 60) + '...';
    return ua;
}
</script>

<template>
    <Head :title="`Chat History - ${client.name}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-1 flex-col gap-6 p-4 md:p-6">
            <!-- Header -->
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h1 class="text-2xl font-semibold tracking-tight">Chat History</h1>
                    <p class="mt-1 text-sm text-muted-foreground">
                        Conversations from visitors interacting with {{ client.name }}'s chatbot.
                        History is kept for 14 days.
                    </p>
                </div>
                <Button variant="outline" size="sm" as-child>
                    <Link :href="`/clients/${client.id}`">
                        <ArrowLeft class="mr-1 size-3.5" />
                        Back to client
                    </Link>
                </Button>
            </div>

            <!-- Summary stats -->
            <section class="grid gap-4 sm:grid-cols-3">
                <Card class="gap-0 border-sidebar-border/70">
                    <CardHeader class="pb-2">
                        <p class="text-xs font-medium text-muted-foreground">Total sessions</p>
                    </CardHeader>
                    <CardContent>
                        <p class="text-2xl font-semibold tracking-tight">{{ sessions.total }}</p>
                    </CardContent>
                </Card>
                <Card class="gap-0 border-sidebar-border/70">
                    <CardHeader class="pb-2">
                        <p class="text-xs font-medium text-muted-foreground">Total messages</p>
                    </CardHeader>
                    <CardContent>
                        <p class="text-2xl font-semibold tracking-tight">
                            {{ sessions.data.reduce((sum, s) => sum + s.message_count, 0) }}
                        </p>
                    </CardContent>
                </Card>
                <Card class="gap-0 border-sidebar-border/70">
                    <CardHeader class="pb-2">
                        <p class="text-xs font-medium text-muted-foreground">Total tokens</p>
                    </CardHeader>
                    <CardContent>
                        <p class="text-2xl font-semibold tracking-tight">
                            {{ sessions.data.reduce((sum, s) => sum + s.total_tokens, 0).toLocaleString() }}
                        </p>
                    </CardContent>
                </Card>
            </section>

            <!-- Sessions list -->
            <Card class="gap-0 border-sidebar-border/70">
                <CardHeader class="border-b border-sidebar-border/70">
                    <h2 class="text-lg font-semibold">Conversations</h2>
                    <p class="text-sm text-muted-foreground">
                        Click a session to expand the full conversation.
                    </p>
                </CardHeader>
                <CardContent class="p-0">
                    <div v-if="sessions.data.length === 0" class="p-6 text-center text-sm text-muted-foreground">
                        No chat sessions recorded yet. Conversations will appear here once visitors start using the chatbot.
                    </div>

                    <div v-else class="divide-y divide-sidebar-border/70">
                        <div
                            v-for="session in sessions.data"
                            :key="session.id"
                        >
                            <!-- Session header (clickable) -->
                            <button
                                class="flex w-full items-center gap-4 px-4 py-4 text-left transition hover:bg-muted/30"
                                @click="toggleSession(session.id)"
                            >
                                <div class="flex size-9 shrink-0 items-center justify-center rounded-full bg-muted">
                                    <MessageSquare class="size-4 text-muted-foreground" />
                                </div>
                                <div class="min-w-0 flex-1">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <span class="text-sm font-medium">
                                            {{ session.message_count }} messages
                                        </span>
                                        <Badge variant="outline" class="text-[10px]">
                                            {{ session.total_tokens.toLocaleString() }} tokens
                                        </Badge>
                                        <Badge v-if="session.visitor_ip" variant="secondary" class="text-[10px]">
                                            {{ session.visitor_ip }}
                                        </Badge>
                                    </div>
                                    <div class="mt-0.5 flex flex-wrap items-center gap-3 text-xs text-muted-foreground">
                                        <span class="flex items-center gap-1">
                                            <Clock class="size-3" />
                                            {{ formatDateTime(session.created_at) }}
                                        </span>
                                        <span v-if="session.page_url" class="flex items-center gap-1 truncate">
                                            <Globe class="size-3 shrink-0" />
                                            {{ session.page_url }}
                                        </span>
                                    </div>
                                </div>
                                <ChevronUp v-if="expandedSessions.has(session.id)" class="size-4 shrink-0 text-muted-foreground" />
                                <ChevronDown v-else class="size-4 shrink-0 text-muted-foreground" />
                            </button>

                            <!-- Expanded conversation -->
                            <div
                                v-if="expandedSessions.has(session.id)"
                                class="border-t border-sidebar-border/50 bg-muted/10 px-4 py-4"
                            >
                                <!-- Session metadata -->
                                <div class="mb-4 flex flex-wrap gap-4 text-xs text-muted-foreground">
                                    <span v-if="session.user_agent" class="flex items-center gap-1">
                                        <Monitor class="size-3" />
                                        {{ truncateUserAgent(session.user_agent) }}
                                    </span>
                                    <span v-if="session.last_activity_at" class="flex items-center gap-1">
                                        <Clock class="size-3" />
                                        Last activity: {{ formatDateTime(session.last_activity_at) }}
                                    </span>
                                </div>

                                <!-- Messages -->
                                <div class="space-y-3">
                                    <div
                                        v-for="message in session.messages"
                                        :key="message.id"
                                        class="flex gap-3"
                                        :class="message.role === 'user' ? 'justify-start' : 'justify-start'"
                                    >
                                        <div
                                            class="flex size-7 shrink-0 items-center justify-center rounded-full"
                                            :class="message.role === 'user' ? 'bg-blue-100 dark:bg-blue-900/30' : 'bg-emerald-100 dark:bg-emerald-900/30'"
                                        >
                                            <User v-if="message.role === 'user'" class="size-3.5 text-blue-600 dark:text-blue-400" />
                                            <Bot v-else class="size-3.5 text-emerald-600 dark:text-emerald-400" />
                                        </div>
                                        <div class="min-w-0 flex-1">
                                            <div class="flex items-center gap-2">
                                                <span class="text-xs font-medium" :class="message.role === 'user' ? 'text-blue-600 dark:text-blue-400' : 'text-emerald-600 dark:text-emerald-400'">
                                                    {{ message.role === 'user' ? 'Visitor' : 'Assistant' }}
                                                </span>
                                                <span class="text-[10px] text-muted-foreground">
                                                    {{ formatTimeOnly(message.created_at) }}
                                                </span>
                                                <Badge v-if="message.from_cache" variant="secondary" class="h-4 text-[9px]">
                                                    <Zap class="mr-0.5 size-2.5" />
                                                    cached
                                                </Badge>
                                                <span v-if="message.token_count > 0" class="text-[10px] text-muted-foreground">
                                                    {{ message.token_count }} tokens
                                                </span>
                                            </div>
                                            <p class="mt-1 whitespace-pre-wrap text-sm leading-relaxed">
                                                {{ message.content }}
                                            </p>
                                        </div>
                                    </div>
                                </div>

                                <div v-if="session.messages.length === 0" class="py-4 text-center text-sm text-muted-foreground">
                                    No messages in this session.
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Pagination -->
                    <div
                        v-if="sessions.last_page > 1"
                        class="flex items-center justify-between border-t border-sidebar-border/70 px-4 py-3"
                    >
                        <p class="text-sm text-muted-foreground">
                            Page {{ sessions.current_page }} of {{ sessions.last_page }}
                            ({{ sessions.total }} sessions)
                        </p>
                        <div class="flex gap-2">
                            <Button
                                v-if="sessions.prev_page_url"
                                variant="outline"
                                size="sm"
                                as-child
                            >
                                <Link :href="sessions.prev_page_url" preserve-scroll>Previous</Link>
                            </Button>
                            <Button
                                v-if="sessions.next_page_url"
                                variant="outline"
                                size="sm"
                                as-child
                            >
                                <Link :href="sessions.next_page_url" preserve-scroll>Next</Link>
                            </Button>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
