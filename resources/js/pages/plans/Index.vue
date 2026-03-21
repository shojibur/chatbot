<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Check, Pencil, Users, X } from 'lucide-vue-next';
import { Badge } from '@/components/ui/badge';
import AppLayout from '@/layouts/AppLayout.vue';
import { dashboard } from '@/routes';
import type { BreadcrumbItem, PlanListItem } from '@/types';

type Props = {
    plans: PlanListItem[];
    status?: string;
};

defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: dashboard() },
    { title: 'Plans', href: '/plans' },
];

function formatNumber(n: number): string {
    if (n >= 1_000_000) return `${(n / 1_000_000).toFixed(1)}M`;
    if (n >= 1_000) return `${(n / 1_000).toFixed(0)}K`;
    return n.toString();
}
</script>

<template>
    <Head title="Plans" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-4 md:p-6">
            <!-- Header -->
            <div class="mb-6">
                <h1 class="text-xl font-semibold text-gray-900 dark:text-gray-100">Plans</h1>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    Manage pricing tiers, token limits, and feature sets.
                </p>
            </div>

            <!-- Status flash -->
            <div
                v-if="status === 'plan-updated'"
                class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700 dark:border-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-400"
            >
                Plan updated successfully.
            </div>

            <!-- Plan cards -->
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <div
                    v-for="plan in plans"
                    :key="plan.id"
                    class="relative rounded-xl border border-gray-200 bg-white p-5 dark:border-gray-700 dark:bg-gray-800"
                >
                    <!-- Active / Inactive badge -->
                    <div class="mb-4 flex items-start justify-between">
                        <div>
                            <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                                {{ plan.name }}
                            </h2>
                            <p class="mt-0.5 text-sm text-gray-500 dark:text-gray-400">
                                {{ plan.slug }}
                            </p>
                        </div>
                        <Badge
                            :variant="plan.is_active ? 'default' : 'secondary'"
                            class="shrink-0"
                        >
                            <Check v-if="plan.is_active" class="mr-1 h-3 w-3" />
                            <X v-else class="mr-1 h-3 w-3" />
                            {{ plan.is_active ? 'Active' : 'Inactive' }}
                        </Badge>
                    </div>

                    <!-- Price -->
                    <div class="mb-4">
                        <span class="text-3xl font-bold text-gray-900 dark:text-gray-100">
                            ${{ plan.price_monthly }}
                        </span>
                        <span class="text-sm text-gray-500 dark:text-gray-400">/month</span>
                    </div>

                    <!-- Description -->
                    <p
                        v-if="plan.description"
                        class="mb-4 text-sm leading-relaxed text-gray-600 dark:text-gray-300"
                    >
                        {{ plan.description }}
                    </p>

                    <!-- Limits -->
                    <div class="mb-4 space-y-2 rounded-lg bg-gray-50 p-3 dark:bg-gray-900/50">
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-gray-500 dark:text-gray-400">Tokens/mo</span>
                            <span class="font-medium text-gray-900 dark:text-gray-100">
                                {{ formatNumber(plan.monthly_token_limit) }}
                            </span>
                        </div>
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-gray-500 dark:text-gray-400">Messages/mo</span>
                            <span class="font-medium text-gray-900 dark:text-gray-100">
                                {{ plan.monthly_message_limit ? formatNumber(plan.monthly_message_limit) : '—' }}
                            </span>
                        </div>
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-gray-500 dark:text-gray-400">Knowledge sources</span>
                            <span class="font-medium text-gray-900 dark:text-gray-100">
                                {{ plan.max_knowledge_sources }}
                            </span>
                        </div>
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-gray-500 dark:text-gray-400">Max upload</span>
                            <span class="font-medium text-gray-900 dark:text-gray-100">
                                {{ plan.max_file_upload_mb }} MB
                            </span>
                        </div>
                    </div>

                    <!-- Features -->
                    <ul v-if="plan.features.length" class="mb-4 space-y-1.5">
                        <li
                            v-for="(feature, i) in plan.features"
                            :key="i"
                            class="flex items-start gap-2 text-sm text-gray-600 dark:text-gray-300"
                        >
                            <Check class="mt-0.5 h-3.5 w-3.5 shrink-0 text-emerald-500" />
                            {{ feature }}
                        </li>
                    </ul>

                    <!-- Footer -->
                    <div class="flex items-center justify-between border-t border-gray-100 pt-4 dark:border-gray-700">
                        <span class="flex items-center gap-1.5 text-sm text-gray-500 dark:text-gray-400">
                            <Users class="h-3.5 w-3.5" />
                            {{ plan.clients_count }} {{ plan.clients_count === 1 ? 'client' : 'clients' }}
                        </span>
                        <Link
                            :href="`/plans/${plan.id}/edit`"
                            class="inline-flex items-center gap-1.5 rounded-md bg-gray-900 px-3 py-1.5 text-xs font-medium text-white transition hover:bg-gray-800 dark:bg-gray-100 dark:text-gray-900 dark:hover:bg-gray-200"
                        >
                            <Pencil class="h-3 w-3" />
                            Edit
                        </Link>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
