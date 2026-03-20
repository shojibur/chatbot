<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import ClientForm from '@/components/clients/ClientForm.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { dashboard } from '@/routes';
import type { BreadcrumbItem, ClientFormRecord, PlanRecord } from '@/types';

type Props = {
    client: ClientFormRecord;
    plans: PlanRecord[];
    widget_styles: string[];
    client_statuses: string[];
    widget_positions: string[];
    chat_models: string[];
    embedding_models: string[];
};

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Clients',
        href: dashboard(),
    },
    {
        title: props.client.name,
        href: `/clients/${props.client.id}`,
    },
    {
        title: 'Edit',
        href: `/clients/${props.client.id}/edit`,
    },
];
</script>

<template>
    <Head :title="`Edit ${client.name}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-4 md:p-6">
            <ClientForm
                :client="client"
                :plans="plans"
                :widget-styles="widget_styles"
                :client-statuses="client_statuses"
                :widget-positions="widget_positions"
                :chat-models="chat_models"
                :embedding-models="embedding_models"
                :heading="`Edit ${client.name}`"
                description="Refine the client package, knowledge strategy, and widget behavior as the SaaS rollout evolves."
                :cancel-href="`/clients/${client.id}`"
            />
        </div>
    </AppLayout>
</template>
