<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import {
    Activity,
    ArrowUpRight,
    Bot,
    Coins,
    Database,
    FolderOpen,
    TrendingUp,
    TrendingDown,
    Minus,
    Zap,
} from 'lucide-vue-next';
import { computed } from 'vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader } from '@/components/ui/card';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';

type Props = {
    stats: {
        total_clients: number;
        active_clients: number;
        paused_clients: number;
        draft_clients: number;
        total_knowledge_sources: number;
        ready_sources: number;
        total_chunks: number;
        current_month_tokens: number;
        current_month_cost: number;
        current_month_requests: number;
        previous_month_tokens: number;
        previous_month_cost: number;
        previous_month_requests: number;
    };
    recent_clients: {
        id: number;
        name: string;
        status: string;
        plan_name: string | null;
        knowledge_sources_count: number;
        current_month_tokens: number;
        created_at: string;
    }[];
    recent_logs: {
        id: number;
        client_name: string;
        interaction_type: string;
        total_tokens: number;
        estimated_cost: number;
        created_at: string;
    }[];
};

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
];

const fmt = (n: number) => new Intl.NumberFormat().format(n);
const fmtCost = (n: number) => `$${n.toFixed(4)}`;

function trendPercent(current: number, previous: number): number | null {
    if (previous === 0) return current > 0 ? 100 : null;
    return Math.round(((current - previous) / previous) * 100);
}

const tokenTrend = computed(() =>
    trendPercent(
        props.stats.current_month_tokens,
        props.stats.previous_month_tokens,
    ),
);
const costTrend = computed(() =>
    trendPercent(
        props.stats.current_month_cost,
        props.stats.previous_month_cost,
    ),
);
const requestTrend = computed(() =>
    trendPercent(
        props.stats.current_month_requests,
        props.stats.previous_month_requests,
    ),
);

function trendIcon(value: number | null) {
    if (value === null) return Minus;
    return value >= 0 ? TrendingUp : TrendingDown;
}

function trendColor(value: number | null) {
    if (value === null) return 'text-muted-foreground';
    return value >= 0 ? 'text-emerald-500' : 'text-red-500';
}

const statusLabels: Record<string, string> = {
    draft: 'Draft',
    active: 'Active',
    paused: 'Paused',
};

function badgeVariant(status: string): 'default' | 'secondary' | 'outline' {
    if (status === 'active') return 'default';
    if (status === 'paused') return 'secondary';
    return 'outline';
}

const interactionLabels: Record<string, string> = {
    chat: 'Chat',
    embedding: 'Embedding',
    cache_hit: 'Cache Hit',
    retrieval: 'Retrieval',
};
</script>

<template>
    <Head title="Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-1 flex-col gap-6 p-4 md:p-6">
            <!-- Welcome header -->
            <div>
                <h1 class="text-2xl font-semibold tracking-tight">Dashboard</h1>
                <p class="text-sm text-muted-foreground">
                    Overview of your Zaochat platform.
                </p>
            </div>

            <!-- Stat cards -->
            <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <Card class="gap-0 border-sidebar-border/70">
                    <CardHeader class="pb-2">
                        <div class="flex items-center justify-between">
                            <p
                                class="text-sm font-medium text-muted-foreground"
                            >
                                Total Clients
                            </p>
                            <div
                                class="rounded-lg border border-sidebar-border/70 bg-accent/30 p-2"
                            >
                                <Bot class="size-4 text-muted-foreground" />
                            </div>
                        </div>
                    </CardHeader>
                    <CardContent>
                        <p class="text-3xl font-semibold tracking-tight">
                            {{ stats.total_clients }}
                        </p>
                        <p class="mt-1 text-xs text-muted-foreground">
                            <span class="text-emerald-500"
                                >{{ stats.active_clients }} active</span
                            >
                            <span v-if="stats.paused_clients">
                                &middot; {{ stats.paused_clients }} paused</span
                            >
                            <span v-if="stats.draft_clients">
                                &middot; {{ stats.draft_clients }} draft</span
                            >
                        </p>
                    </CardContent>
                </Card>

                <Card class="gap-0 border-sidebar-border/70">
                    <CardHeader class="pb-2">
                        <div class="flex items-center justify-between">
                            <p
                                class="text-sm font-medium text-muted-foreground"
                            >
                                Knowledge Sources
                            </p>
                            <div
                                class="rounded-lg border border-sidebar-border/70 bg-accent/30 p-2"
                            >
                                <FolderOpen
                                    class="size-4 text-muted-foreground"
                                />
                            </div>
                        </div>
                    </CardHeader>
                    <CardContent>
                        <p class="text-3xl font-semibold tracking-tight">
                            {{ stats.total_knowledge_sources }}
                        </p>
                        <p class="mt-1 text-xs text-muted-foreground">
                            {{ stats.ready_sources }} ready &middot;
                            {{ fmt(stats.total_chunks) }} chunks indexed
                        </p>
                    </CardContent>
                </Card>

                <Card class="gap-0 border-sidebar-border/70">
                    <CardHeader class="pb-2">
                        <div class="flex items-center justify-between">
                            <p
                                class="text-sm font-medium text-muted-foreground"
                            >
                                Tokens This Month
                            </p>
                            <div
                                class="rounded-lg border border-sidebar-border/70 bg-accent/30 p-2"
                            >
                                <Zap class="size-4 text-muted-foreground" />
                            </div>
                        </div>
                    </CardHeader>
                    <CardContent>
                        <p class="text-3xl font-semibold tracking-tight">
                            {{ fmt(stats.current_month_tokens) }}
                        </p>
                        <div class="mt-1 flex items-center gap-1 text-xs">
                            <component
                                :is="trendIcon(tokenTrend)"
                                class="size-3"
                                :class="trendColor(tokenTrend)"
                            />
                            <span :class="trendColor(tokenTrend)">
                                {{
                                    tokenTrend !== null
                                        ? `${tokenTrend > 0 ? '+' : ''}${tokenTrend}%`
                                        : 'No prior data'
                                }}
                            </span>
                            <span class="text-muted-foreground"
                                >vs last month</span
                            >
                        </div>
                    </CardContent>
                </Card>

                <Card class="gap-0 border-sidebar-border/70">
                    <CardHeader class="pb-2">
                        <div class="flex items-center justify-between">
                            <p
                                class="text-sm font-medium text-muted-foreground"
                            >
                                Cost This Month
                            </p>
                            <div
                                class="rounded-lg border border-sidebar-border/70 bg-accent/30 p-2"
                            >
                                <Coins class="size-4 text-muted-foreground" />
                            </div>
                        </div>
                    </CardHeader>
                    <CardContent>
                        <p class="text-3xl font-semibold tracking-tight">
                            {{ fmtCost(stats.current_month_cost) }}
                        </p>
                        <div class="mt-1 flex items-center gap-1 text-xs">
                            <component
                                :is="trendIcon(costTrend)"
                                class="size-3"
                                :class="trendColor(costTrend)"
                            />
                            <span :class="trendColor(costTrend)">
                                {{
                                    costTrend !== null
                                        ? `${costTrend > 0 ? '+' : ''}${costTrend}%`
                                        : 'No prior data'
                                }}
                            </span>
                            <span class="text-muted-foreground"
                                >vs last month</span
                            >
                        </div>
                    </CardContent>
                </Card>
            </section>

            <!-- Requests stat bar -->
            <Card class="gap-0 border-sidebar-border/70">
                <CardContent class="flex flex-wrap items-center gap-6 py-4">
                    <div class="flex items-center gap-3">
                        <div
                            class="rounded-lg border border-sidebar-border/70 bg-accent/30 p-2"
                        >
                            <Activity class="size-4 text-muted-foreground" />
                        </div>
                        <div>
                            <p class="text-sm font-medium">
                                {{ fmt(stats.current_month_requests) }} requests
                                this month
                            </p>
                            <div class="flex items-center gap-1 text-xs">
                                <component
                                    :is="trendIcon(requestTrend)"
                                    class="size-3"
                                    :class="trendColor(requestTrend)"
                                />
                                <span :class="trendColor(requestTrend)">
                                    {{
                                        requestTrend !== null
                                            ? `${requestTrend > 0 ? '+' : ''}${requestTrend}%`
                                            : 'No prior data'
                                    }}
                                </span>
                                <span class="text-muted-foreground"
                                    >vs last month ({{
                                        fmt(stats.previous_month_requests)
                                    }})</span
                                >
                            </div>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Two-column: Recent clients + Recent activity -->
            <section class="grid gap-6 xl:grid-cols-2">
                <!-- Recent Clients -->
                <Card class="gap-0 border-sidebar-border/70">
                    <CardHeader
                        class="flex-row items-center justify-between border-b border-sidebar-border/70"
                    >
                        <div>
                            <h2 class="text-lg font-semibold">
                                Recent Clients
                            </h2>
                            <p class="text-sm text-muted-foreground">
                                Latest client workspaces
                            </p>
                        </div>
                        <Button variant="outline" size="sm" as-child>
                            <Link href="/clients">
                                View all
                                <ArrowUpRight class="ml-1 size-3.5" />
                            </Link>
                        </Button>
                    </CardHeader>
                    <CardContent class="p-0">
                        <div
                            v-if="recent_clients.length === 0"
                            class="p-6 text-center text-sm text-muted-foreground"
                        >
                            No clients yet. Create your first client to get
                            started.
                        </div>
                        <div v-else class="divide-y divide-sidebar-border/70">
                            <Link
                                v-for="client in recent_clients"
                                :key="client.id"
                                :href="`/clients/${client.id}`"
                                class="flex items-center justify-between gap-4 px-6 py-4 transition hover:bg-accent/50"
                            >
                                <div class="min-w-0">
                                    <div class="flex items-center gap-2">
                                        <p class="truncate text-sm font-medium">
                                            {{ client.name }}
                                        </p>
                                        <Badge
                                            :variant="
                                                badgeVariant(client.status)
                                            "
                                            class="text-[10px]"
                                        >
                                            {{
                                                statusLabels[client.status] ??
                                                client.status
                                            }}
                                        </Badge>
                                        <Badge
                                            v-if="client.plan_name"
                                            variant="outline"
                                            class="text-[10px]"
                                        >
                                            {{ client.plan_name }}
                                        </Badge>
                                    </div>
                                    <p
                                        class="mt-0.5 text-xs text-muted-foreground"
                                    >
                                        {{ client.knowledge_sources_count }}
                                        sources &middot;
                                        {{ fmt(client.current_month_tokens) }}
                                        tokens this month
                                    </p>
                                </div>
                                <ArrowUpRight
                                    class="size-4 shrink-0 text-muted-foreground"
                                />
                            </Link>
                        </div>
                    </CardContent>
                </Card>

                <!-- Recent Activity -->
                <Card class="gap-0 border-sidebar-border/70">
                    <CardHeader class="border-b border-sidebar-border/70">
                        <h2 class="text-lg font-semibold">Recent Activity</h2>
                        <p class="text-sm text-muted-foreground">
                            Latest API usage across all clients
                        </p>
                    </CardHeader>
                    <CardContent class="p-0">
                        <div
                            v-if="recent_logs.length === 0"
                            class="p-6 text-center text-sm text-muted-foreground"
                        >
                            No activity yet. Usage logs will appear here once
                            clients start chatting.
                        </div>
                        <div v-else class="divide-y divide-sidebar-border/70">
                            <div
                                v-for="log in recent_logs"
                                :key="log.id"
                                class="flex items-center justify-between gap-4 px-6 py-3.5"
                            >
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-medium">
                                        {{ log.client_name }}
                                    </p>
                                    <p
                                        class="mt-0.5 text-xs text-muted-foreground"
                                    >
                                        {{
                                            interactionLabels[
                                                log.interaction_type
                                            ] ?? log.interaction_type
                                        }}
                                        &middot;
                                        {{ fmt(log.total_tokens) }} tokens
                                    </p>
                                </div>
                                <div class="shrink-0 text-right">
                                    <p class="text-sm font-medium">
                                        {{ fmtCost(log.estimated_cost) }}
                                    </p>
                                    <p class="text-xs text-muted-foreground">
                                        {{
                                            new Date(
                                                log.created_at,
                                            ).toLocaleDateString()
                                        }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </section>
        </div>
    </AppLayout>
</template>
