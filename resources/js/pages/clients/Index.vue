<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import {
    Bot,
    DatabaseZap,
    FolderOpen,
    Gauge,
    Plus,
    Sparkles,
} from 'lucide-vue-next';
import { computed } from 'vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader } from '@/components/ui/card';
import AppLayout from '@/layouts/AppLayout.vue';
import { dashboard } from '@/routes';
import type { BreadcrumbItem, ClientListItem } from '@/types';

type Props = {
    clients: ClientListItem[];
    summary: {
        total_clients: number;
        active_clients: number;
        paused_clients: number;
        monthly_token_capacity: number;
        current_month_usage: number;
        knowledge_sources: number;
    };
    status?: string;
};

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Clients',
        href: dashboard(),
    },
];

const statusLabels: Record<string, string> = {
    draft: 'Draft',
    active: 'Active',
    paused: 'Paused',
};

const metricCards = computed(() => [
    {
        label: 'Clients',
        value: props.summary.total_clients,
        helper: 'Tenant workspaces configured',
        icon: Bot,
    },
    {
        label: 'Knowledge sources',
        value: props.summary.knowledge_sources,
        helper: 'Documents, manual notes, or website sources tracked',
        icon: FolderOpen,
    },
    {
        label: 'Token capacity',
        value: new Intl.NumberFormat().format(
            props.summary.monthly_token_capacity,
        ),
        helper: 'Combined client allowance across packages',
        icon: Gauge,
    },
    {
        label: 'Current month usage',
        value: new Intl.NumberFormat().format(
            props.summary.current_month_usage,
        ),
        helper: 'Logged token history so far this month',
        icon: DatabaseZap,
    },
]);

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
    if (status === 'active') {
        return 'default';
    }

    if (status === 'paused') {
        return 'secondary';
    }

    return 'outline';
}
</script>

<template>
    <Head title="Clients" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-1 flex-col gap-6 p-4 md:p-6">
            <section
                class="overflow-hidden rounded-3xl border border-sidebar-border/70 bg-[radial-gradient(circle_at_top_right,_rgba(15,118,110,0.18),_transparent_36%),linear-gradient(135deg,_rgba(15,23,42,0.96),_rgba(15,118,110,0.92))] p-6 text-white shadow-sm"
            >
                <div
                    class="flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between"
                >
                    <div class="max-w-2xl space-y-3">
                        <Badge
                            variant="secondary"
                            class="border-white/10 bg-white/10 text-white"
                        >
                            SaaS client control plane
                        </Badge>
                        <div class="space-y-2">
                            <h1 class="text-3xl font-semibold tracking-tight">
                                Manage tenants, package limits, knowledge
                                sources, and token usage from one admin
                                workspace.
                            </h1>
                            <p class="text-sm text-white/75">
                                The client setup now reflects the docs strategy:
                                each tenant gets a dedicated package, AI
                                configuration, knowledge base pipeline, and
                                usage tracking surface.
                            </p>
                        </div>
                    </div>

                    <Button
                        variant="secondary"
                        as-child
                        class="bg-white text-slate-950 hover:bg-white/90"
                    >
                        <Link href="/clients/create">
                            <Plus class="mr-2 size-4" />
                            New client
                        </Link>
                    </Button>
                </div>
            </section>

            <div
                v-if="statusMessage"
                class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700 dark:border-emerald-300/10 dark:bg-emerald-500/10 dark:text-emerald-200"
            >
                {{ statusMessage }}
            </div>

            <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                <Card
                    v-for="metric in metricCards"
                    :key="metric.label"
                    class="gap-0 border-sidebar-border/70"
                >
                    <CardHeader class="pb-3">
                        <div class="flex items-center justify-between gap-4">
                            <div>
                                <p class="text-sm text-muted-foreground">
                                    {{ metric.label }}
                                </p>
                                <p
                                    class="text-2xl font-semibold tracking-tight"
                                >
                                    {{ metric.value }}
                                </p>
                            </div>
                            <div
                                class="rounded-2xl border border-sidebar-border/70 bg-accent/30 p-3"
                            >
                                <component :is="metric.icon" class="size-5" />
                            </div>
                        </div>
                    </CardHeader>
                    <CardContent class="pt-0 text-sm text-muted-foreground">
                        {{ metric.helper }}
                    </CardContent>
                </Card>
            </section>

            <section class="grid gap-4 xl:grid-cols-[minmax(0,2fr)_360px]">
                <Card class="gap-0 border-sidebar-border/70">
                    <CardHeader class="border-b border-sidebar-border/70">
                        <h2 class="text-xl font-semibold">Client workspaces</h2>
                        <p class="text-sm text-muted-foreground">
                            Open a workspace to manage plan details, RAG
                            settings, knowledge sources, and usage history.
                        </p>
                    </CardHeader>

                    <CardContent class="grid gap-4 pt-6 2xl:grid-cols-2">
                        <article
                            v-for="client in clients"
                            :key="client.id"
                            class="rounded-2xl border border-sidebar-border/70 bg-card/70 p-5"
                        >
                            <div
                                class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between"
                            >
                                <div class="space-y-2">
                                    <div
                                        class="flex flex-wrap items-center gap-2"
                                    >
                                        <h3
                                            class="text-lg font-semibold tracking-tight"
                                        >
                                            {{ client.name }}
                                        </h3>
                                        <Badge
                                            :variant="
                                                badgeVariant(client.status)
                                            "
                                        >
                                            {{
                                                statusLabels[client.status] ??
                                                client.status
                                            }}
                                        </Badge>
                                        <Badge
                                            v-if="client.plan"
                                            variant="outline"
                                        >
                                            {{ client.plan.name }}
                                        </Badge>
                                    </div>
                                    <div
                                        class="grid gap-1 text-sm text-muted-foreground"
                                    >
                                        <p>
                                            {{
                                                client.contact_email ??
                                                'No contact email yet'
                                            }}
                                        </p>
                                        <p>
                                            {{
                                                client.website_url ??
                                                'No website configured yet'
                                            }}
                                        </p>
                                    </div>
                                </div>

                                <div
                                    class="rounded-2xl border border-sidebar-border/70 p-3 text-white"
                                    :style="{
                                        background: `linear-gradient(135deg, ${client.widget_settings.primary_color}, ${client.widget_settings.accent_color})`,
                                    }"
                                >
                                    <Sparkles class="size-5" />
                                </div>
                            </div>

                            <div
                                class="mt-5 grid gap-3 text-sm text-muted-foreground md:grid-cols-3"
                            >
                                <div
                                    class="rounded-xl border border-sidebar-border/70 px-3 py-3"
                                >
                                    <p class="font-medium text-foreground">
                                        Sources
                                    </p>
                                    <p class="mt-1">
                                        {{ client.knowledge_sources_count }}
                                    </p>
                                </div>
                                <div
                                    class="rounded-xl border border-sidebar-border/70 px-3 py-3"
                                >
                                    <p class="font-medium text-foreground">
                                        Monthly usage
                                    </p>
                                    <p class="mt-1">
                                        {{
                                            client.current_month_tokens.toLocaleString()
                                        }}
                                    </p>
                                </div>
                                <div
                                    class="rounded-xl border border-sidebar-border/70 px-3 py-3"
                                >
                                    <p class="font-medium text-foreground">
                                        Token cap
                                    </p>
                                    <p class="mt-1">
                                        {{
                                            client.monthly_token_limit.toLocaleString()
                                        }}
                                    </p>
                                </div>
                            </div>

                            <div class="mt-5 flex flex-wrap gap-3">
                                <Button as-child>
                                    <Link :href="`/clients/${client.id}`"
                                        >Open workspace</Link
                                    >
                                </Button>
                                <Button variant="outline" as-child>
                                    <Link :href="`/clients/${client.id}/edit`"
                                        >Edit setup</Link
                                    >
                                </Button>
                            </div>
                        </article>
                    </CardContent>
                </Card>

                <div class="grid gap-4">
                    <Card class="gap-0 border-sidebar-border/70">
                        <CardHeader class="border-b border-sidebar-border/70">
                            <h2 class="text-xl font-semibold">
                                Admin checklist
                            </h2>
                            <p class="text-sm text-muted-foreground">
                                The client workspace is now aligned to the SaaS
                                architecture in the docs.
                            </p>
                        </CardHeader>
                        <CardContent class="space-y-4 pt-6 text-sm">
                            <div class="rounded-xl border px-4 py-4">
                                <p class="font-medium">1. Assign package</p>
                                <p class="mt-1 text-muted-foreground">
                                    Free, Pro, or Ultra Pro with explicit token
                                    and knowledge limits.
                                </p>
                            </div>
                            <div class="rounded-xl border px-4 py-4">
                                <p class="font-medium">2. Configure RAG</p>
                                <p class="mt-1 text-muted-foreground">
                                    Store prompt, retrieval chunk count, and
                                    caching rules per tenant.
                                </p>
                            </div>
                            <div class="rounded-xl border px-4 py-4">
                                <p class="font-medium">
                                    3. Build the knowledge base
                                </p>
                                <p class="mt-1 text-muted-foreground">
                                    Track manual content, URLs, and uploaded
                                    files before chunking and embeddings are
                                    added.
                                </p>
                            </div>
                            <div class="rounded-xl border px-4 py-4">
                                <p class="font-medium">4. Monitor spend</p>
                                <p class="mt-1 text-muted-foreground">
                                    Usage logs are shaped for token reporting,
                                    prompt caching, and future billing views.
                                </p>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </section>
        </div>
    </AppLayout>
</template>
