<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft, Plus, Trash2 } from 'lucide-vue-next';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import { dashboard } from '@/routes';
import type { BreadcrumbItem, PlanFormRecord } from '@/types';

type Props = {
    plan: Omit<PlanFormRecord, 'id'> & { id: null };
};

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: dashboard() },
    { title: 'Plans', href: '/plans' },
    { title: 'New plan', href: '/plans/create' },
];

const form = useForm({
    name: props.plan.name,
    description: props.plan.description,
    price_monthly: props.plan.price_monthly,
    monthly_token_limit: props.plan.monthly_token_limit,
    monthly_message_limit: props.plan.monthly_message_limit,
    max_knowledge_sources: props.plan.max_knowledge_sources,
    max_file_upload_mb: props.plan.max_file_upload_mb,
    features: [...props.plan.features],
    is_active: props.plan.is_active,
});

function submit() {
    form.post('/plans');
}

function addFeature() {
    form.features.push('');
}

function removeFeature(index: number) {
    form.features.splice(index, 1);
}
</script>

<template>
    <Head title="New Plan" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-4 md:p-6">
            <!-- Header -->
            <div class="mb-6 flex items-center gap-3">
                <Link
                    href="/plans"
                    class="inline-flex h-8 w-8 items-center justify-center rounded-md border border-gray-200 text-gray-500 transition hover:bg-gray-50 dark:border-gray-700 dark:text-gray-400 dark:hover:bg-gray-800"
                >
                    <ArrowLeft class="h-4 w-4" />
                </Link>
                <div>
                    <h1
                        class="text-xl font-semibold text-gray-900 dark:text-gray-100"
                    >
                        New plan
                    </h1>
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        The slug will be generated from the name.
                    </p>
                </div>
            </div>

            <form @submit.prevent="submit" class="mx-auto max-w-2xl space-y-6">
                <!-- Basic info -->
                <Card>
                    <CardHeader>
                        <CardTitle>Plan details</CardTitle>
                        <CardDescription
                            >Name, description, and pricing.</CardDescription
                        >
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div class="space-y-2">
                                <Label for="name">Name</Label>
                                <Input
                                    id="name"
                                    v-model="form.name"
                                    placeholder="e.g. Pro"
                                />
                                <p
                                    v-if="form.errors.name"
                                    class="text-sm text-red-600"
                                >
                                    {{ form.errors.name }}
                                </p>
                            </div>
                            <div class="space-y-2">
                                <Label for="price_monthly"
                                    >Price / month ($)</Label
                                >
                                <Input
                                    id="price_monthly"
                                    v-model.number="form.price_monthly"
                                    type="number"
                                    min="0"
                                    step="0.01"
                                />
                                <p
                                    v-if="form.errors.price_monthly"
                                    class="text-sm text-red-600"
                                >
                                    {{ form.errors.price_monthly }}
                                </p>
                            </div>
                        </div>
                        <div class="space-y-2">
                            <Label for="description">Description</Label>
                            <textarea
                                id="description"
                                v-model="form.description"
                                rows="2"
                                class="w-full rounded-md border border-gray-200 bg-white px-3 py-2 text-sm shadow-xs placeholder:text-gray-400 focus:border-gray-900 focus:ring-1 focus:ring-gray-900 focus:outline-none dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100 dark:placeholder:text-gray-500 dark:focus:border-gray-100 dark:focus:ring-gray-100"
                                placeholder="Short description of the plan"
                            />
                            <p
                                v-if="form.errors.description"
                                class="text-sm text-red-600"
                            >
                                {{ form.errors.description }}
                            </p>
                        </div>
                        <div class="flex items-center gap-3">
                            <Checkbox
                                id="is_active"
                                :checked="form.is_active"
                                @update:checked="
                                    form.is_active = $event as boolean
                                "
                            />
                            <Label for="is_active">Active</Label>
                        </div>
                    </CardContent>
                </Card>

                <!-- Limits -->
                <Card>
                    <CardHeader>
                        <CardTitle>Limits</CardTitle>
                        <CardDescription
                            >Token, message, and storage
                            allowances.</CardDescription
                        >
                    </CardHeader>
                    <CardContent>
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div class="space-y-2">
                                <Label for="monthly_token_limit"
                                    >Monthly token limit</Label
                                >
                                <Input
                                    id="monthly_token_limit"
                                    v-model.number="form.monthly_token_limit"
                                    type="number"
                                    min="0"
                                />
                                <p
                                    v-if="form.errors.monthly_token_limit"
                                    class="text-sm text-red-600"
                                >
                                    {{ form.errors.monthly_token_limit }}
                                </p>
                            </div>
                            <div class="space-y-2">
                                <Label for="monthly_message_limit"
                                    >Monthly message limit</Label
                                >
                                <Input
                                    id="monthly_message_limit"
                                    v-model.number="form.monthly_message_limit"
                                    type="number"
                                    min="0"
                                />
                                <p
                                    v-if="form.errors.monthly_message_limit"
                                    class="text-sm text-red-600"
                                >
                                    {{ form.errors.monthly_message_limit }}
                                </p>
                            </div>
                            <div class="space-y-2">
                                <Label for="max_knowledge_sources"
                                    >Max knowledge sources</Label
                                >
                                <Input
                                    id="max_knowledge_sources"
                                    v-model.number="form.max_knowledge_sources"
                                    type="number"
                                    min="1"
                                />
                                <p
                                    v-if="form.errors.max_knowledge_sources"
                                    class="text-sm text-red-600"
                                >
                                    {{ form.errors.max_knowledge_sources }}
                                </p>
                            </div>
                            <div class="space-y-2">
                                <Label for="max_file_upload_mb"
                                    >Max file upload (MB)</Label
                                >
                                <Input
                                    id="max_file_upload_mb"
                                    v-model.number="form.max_file_upload_mb"
                                    type="number"
                                    min="1"
                                />
                                <p
                                    v-if="form.errors.max_file_upload_mb"
                                    class="text-sm text-red-600"
                                >
                                    {{ form.errors.max_file_upload_mb }}
                                </p>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Features -->
                <Card>
                    <CardHeader>
                        <CardTitle>Features</CardTitle>
                        <CardDescription
                            >Bullet points shown on the plan
                            card.</CardDescription
                        >
                    </CardHeader>
                    <CardContent class="space-y-3">
                        <div
                            v-for="(_, index) in form.features"
                            :key="index"
                            class="flex items-center gap-2"
                        >
                            <Input
                                v-model="form.features[index]"
                                placeholder="e.g. Prompt caching and semantic cache"
                                class="flex-1"
                            />
                            <button
                                type="button"
                                @click="removeFeature(index)"
                                class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-md border border-gray-200 text-gray-400 transition hover:border-red-200 hover:bg-red-50 hover:text-red-600 dark:border-gray-700 dark:hover:border-red-800 dark:hover:bg-red-900/20 dark:hover:text-red-400"
                            >
                                <Trash2 class="h-4 w-4" />
                            </button>
                        </div>
                        <button
                            type="button"
                            @click="addFeature"
                            class="inline-flex items-center gap-1.5 rounded-md border border-dashed border-gray-300 px-3 py-2 text-sm text-gray-500 transition hover:border-gray-400 hover:text-gray-700 dark:border-gray-600 dark:text-gray-400 dark:hover:border-gray-500 dark:hover:text-gray-300"
                        >
                            <Plus class="h-3.5 w-3.5" />
                            Add feature
                        </button>
                    </CardContent>
                </Card>

                <!-- Actions -->
                <div class="flex items-center justify-end gap-3">
                    <Link
                        href="/plans"
                        class="inline-flex items-center rounded-md border border-gray-200 px-4 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-800"
                    >
                        Cancel
                    </Link>
                    <Button type="submit" :disabled="form.processing">
                        {{ form.processing ? 'Creating...' : 'Create plan' }}
                    </Button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
