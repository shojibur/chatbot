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

defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Clients',
        href: dashboard(),
    },
    {
        title: 'New client',
        href: '/clients/create',
    },
];
</script>

<template>
    <Head title="New client" />

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
                heading="Create a new client workspace"
                description="Set the package, AI configuration, widget identity, and cost controls before knowledge sources and the public chatbot go live."
                cancel-href="/dashboard"
            />
        </div>
    </AppLayout>
</template>
