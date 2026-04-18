<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { Check, CreditCard, DatabaseZap, FileText, HardDrive, MessageCircle } from 'lucide-vue-next';
import { computed } from 'vue';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardHeader } from '@/components/ui/card';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';

type Plan = {
    name: string;
    description: string | null;
    price_monthly: number;
    monthly_token_limit: number;
    monthly_message_limit: number;
    max_knowledge_sources: number;
    max_file_upload_mb: number;
    features: string[];
};

type Limits = {
    max_knowledge_sources: number;
    max_file_upload_mb: number;
    monthly_token_limit: number;
    monthly_message_limit: number;
};

type Usage = {
    monthly_tokens: number;
    knowledge_sources: number;
};

const props = defineProps<{
    plan: Plan | null;
    usage: Usage;
    limits: Limits;
    client_status: string;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/portal/dashboard' },
    { title: 'Subscription', href: '/portal/subscription' },
];

const tokenPct = computed(() => {
    const max = props.limits.monthly_token_limit;
    if (!max) return 0;
    return Math.min(100, Math.round((props.usage.monthly_tokens / max) * 100));
});

const ksPct = computed(() => {
    const max = props.limits.max_knowledge_sources;
    if (!max) return 0;
    return Math.min(100, Math.round((props.usage.knowledge_sources / max) * 100));
});

function barColor(pct: number) {
    if (pct >= 90) return 'bg-red-500';
    if (pct >= 70) return 'bg-amber-500';
    return 'bg-emerald-500';
}

function statusBadgeClass(s: string) {
    if (s === 'active') return 'border-emerald-300 bg-emerald-50 text-emerald-700 dark:border-emerald-700 dark:bg-emerald-900/20 dark:text-emerald-400';
    if (s === 'paused') return 'border-amber-300 bg-amber-50 text-amber-700 dark:border-amber-700 dark:bg-amber-900/20 dark:text-amber-400';
    return 'border-sidebar-border/60 bg-muted/50 text-muted-foreground';
}
</script>

<template>
    <Head title="Subscription" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-1 flex-col gap-6 p-4 md:p-6 lg:max-w-4xl">

            <!-- Header -->
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h1 class="text-2xl font-bold tracking-tight">Subscription</h1>
                    <p class="mt-1 text-sm text-muted-foreground">
                        Your current plan, usage, and included features.
                    </p>
                </div>
                <Badge
                    variant="outline"
                    class="w-fit px-3 py-1 text-xs font-semibold uppercase tracking-wider"
                    :class="statusBadgeClass(client_status)"
                >
                    {{ client_status }}
                </Badge>
            </div>

            <!-- No plan state -->
            <div
                v-if="!plan"
                class="flex flex-col items-center justify-center rounded-xl border border-dashed border-sidebar-border/60 py-20 text-center"
            >
                <div class="mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-muted">
                    <CreditCard class="h-6 w-6 text-muted-foreground/40" />
                </div>
                <p class="text-sm font-medium text-muted-foreground">No plan assigned</p>
                <p class="mt-1 text-xs text-muted-foreground/70">
                    Contact your administrator to get a plan assigned to your account.
                </p>
            </div>

            <template v-else>
                <!-- Plan card -->
                <Card class="border-sidebar-border/60 shadow-sm">
                    <CardHeader class="pb-4">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <div class="flex items-center gap-2.5">
                                    <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-primary/10">
                                        <CreditCard class="h-5 w-5 text-primary" />
                                    </div>
                                    <div>
                                        <p class="text-lg font-bold">{{ plan.name }}</p>
                                        <p v-if="plan.description" class="text-sm text-muted-foreground">{{ plan.description }}</p>
                                    </div>
                                </div>
                            </div>
                            <div class="text-right">
                                <p v-if="plan.price_monthly > 0" class="text-2xl font-bold">${{ plan.price_monthly.toFixed(2) }}</p>
                                <p v-else class="text-sm font-semibold text-emerald-600 dark:text-emerald-400">Free</p>
                                <p v-if="plan.price_monthly > 0" class="text-xs text-muted-foreground">per month</p>
                            </div>
                        </div>
                    </CardHeader>
                    <CardContent>
                        <!-- Features -->
                        <div v-if="plan.features && plan.features.length > 0" class="grid grid-cols-1 gap-2 sm:grid-cols-2">
                            <div v-for="feature in plan.features" :key="feature" class="flex items-center gap-2 text-sm">
                                <div class="flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-emerald-100 dark:bg-emerald-900/30">
                                    <Check class="h-3 w-3 text-emerald-600 dark:text-emerald-400" />
                                </div>
                                <span>{{ feature }}</span>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Usage metrics -->
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">

                    <!-- Monthly tokens -->
                    <Card class="border-sidebar-border/60 shadow-sm">
                        <CardContent class="pt-5">
                            <div class="mb-4 flex items-center gap-3">
                                <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-blue-50 dark:bg-blue-900/20">
                                    <MessageCircle class="h-4 w-4 text-blue-600 dark:text-blue-400" />
                                </div>
                                <div>
                                    <p class="text-sm font-semibold">Monthly Tokens</p>
                                    <p class="text-xs text-muted-foreground">Resets each month</p>
                                </div>
                            </div>

                            <div class="mb-2 flex items-end justify-between">
                                <span class="text-2xl font-bold tabular-nums">{{ usage.monthly_tokens.toLocaleString() }}</span>
                                <span class="text-sm text-muted-foreground">/ {{ limits.monthly_token_limit.toLocaleString() }}</span>
                            </div>
                            <div class="h-2 w-full overflow-hidden rounded-full bg-secondary">
                                <div
                                    class="h-full rounded-full transition-all duration-700"
                                    :class="barColor(tokenPct)"
                                    :style="{ width: tokenPct + '%' }"
                                />
                            </div>
                            <p class="mt-1.5 text-right text-xs" :class="tokenPct >= 90 ? 'text-red-600 dark:text-red-400 font-semibold' : 'text-muted-foreground'">
                                {{ tokenPct }}% used
                            </p>
                        </CardContent>
                    </Card>

                    <!-- Knowledge sources -->
                    <Card class="border-sidebar-border/60 shadow-sm">
                        <CardContent class="pt-5">
                            <div class="mb-4 flex items-center gap-3">
                                <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-violet-50 dark:bg-violet-900/20">
                                    <DatabaseZap class="h-4 w-4 text-violet-600 dark:text-violet-400" />
                                </div>
                                <div>
                                    <p class="text-sm font-semibold">Knowledge Sources</p>
                                    <p class="text-xs text-muted-foreground">Active knowledge files</p>
                                </div>
                            </div>

                            <div class="mb-2 flex items-end justify-between">
                                <span class="text-2xl font-bold tabular-nums">{{ usage.knowledge_sources }}</span>
                                <span class="text-sm text-muted-foreground">/ {{ limits.max_knowledge_sources }}</span>
                            </div>
                            <div class="h-2 w-full overflow-hidden rounded-full bg-secondary">
                                <div
                                    class="h-full rounded-full transition-all duration-700"
                                    :class="barColor(ksPct)"
                                    :style="{ width: ksPct + '%' }"
                                />
                            </div>
                            <p class="mt-1.5 text-right text-xs" :class="ksPct >= 90 ? 'text-red-600 dark:text-red-400 font-semibold' : 'text-muted-foreground'">
                                {{ ksPct }}% used
                            </p>
                        </CardContent>
                    </Card>
                </div>

                <!-- Plan limits summary -->
                <Card class="border-sidebar-border/60 shadow-sm">
                    <CardHeader class="pb-3">
                        <p class="text-sm font-semibold">Plan Limits</p>
                        <p class="text-xs text-muted-foreground">What's included with your current plan.</p>
                    </CardHeader>
                    <CardContent>
                        <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
                            <div class="flex flex-col gap-1.5 rounded-lg border border-sidebar-border/50 bg-muted/20 p-3">
                                <div class="flex h-7 w-7 items-center justify-center rounded-md bg-blue-100 dark:bg-blue-900/20">
                                    <MessageCircle class="h-3.5 w-3.5 text-blue-600 dark:text-blue-400" />
                                </div>
                                <p class="text-lg font-bold tabular-nums">{{ (limits.monthly_token_limit / 1000).toFixed(0) }}k</p>
                                <p class="text-[10px] text-muted-foreground uppercase tracking-wider">Tokens/month</p>
                            </div>
                            <div class="flex flex-col gap-1.5 rounded-lg border border-sidebar-border/50 bg-muted/20 p-3">
                                <div class="flex h-7 w-7 items-center justify-center rounded-md bg-violet-100 dark:bg-violet-900/20">
                                    <DatabaseZap class="h-3.5 w-3.5 text-violet-600 dark:text-violet-400" />
                                </div>
                                <p class="text-lg font-bold tabular-nums">{{ limits.max_knowledge_sources }}</p>
                                <p class="text-[10px] text-muted-foreground uppercase tracking-wider">Knowledge Sources</p>
                            </div>
                            <div class="flex flex-col gap-1.5 rounded-lg border border-sidebar-border/50 bg-muted/20 p-3">
                                <div class="flex h-7 w-7 items-center justify-center rounded-md bg-amber-100 dark:bg-amber-900/20">
                                    <HardDrive class="h-3.5 w-3.5 text-amber-600 dark:text-amber-400" />
                                </div>
                                <p class="text-lg font-bold tabular-nums">{{ limits.max_file_upload_mb }}MB</p>
                                <p class="text-[10px] text-muted-foreground uppercase tracking-wider">Max File Size</p>
                            </div>
                            <div class="flex flex-col gap-1.5 rounded-lg border border-sidebar-border/50 bg-muted/20 p-3">
                                <div class="flex h-7 w-7 items-center justify-center rounded-md bg-emerald-100 dark:bg-emerald-900/20">
                                    <FileText class="h-3.5 w-3.5 text-emerald-600 dark:text-emerald-400" />
                                </div>
                                <p class="text-lg font-bold tabular-nums">{{ limits.monthly_message_limit }}</p>
                                <p class="text-[10px] text-muted-foreground uppercase tracking-wider">Messages/month</p>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Need upgrade note -->
                <div class="rounded-xl border border-sidebar-border/50 bg-muted/10 px-4 py-3.5 text-sm text-muted-foreground">
                    💬 Need more capacity or want to upgrade your plan? Contact your administrator.
                </div>
            </template>
        </div>
    </AppLayout>
</template>
