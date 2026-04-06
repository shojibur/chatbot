<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ArrowLeft, Clock, ExternalLink, Mail, Phone, User as UserIcon } from 'lucide-vue-next';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/AppLayout.vue';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';

type Message = {
    role: 'user' | 'assistant';
    content: string;
};

type LeadFull = {
    id: number;
    client_id: number;
    chat_session_id: number | null;
    name: string;
    contact: string;
    user_request: string | null;
    notes: string | null;
    trigger: string;
    status: string;
    created_at: string;
    conversation_snapshot: Message[] | null;
    client: {
        id: number;
        name: string;
    };
    chat_session?: {
        id: number;
        session_token: string;
    } | null;
};

type Props = {
    lead: LeadFull;
};

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: dashboard() },
    { title: 'Leads', href: '/leads' },
    { title: props.lead.name, href: `/leads/${props.lead.id}` },
];

function formatDate(value: string): string {
    const dateStr = value.endsWith('Z') ? value : value + 'Z';
    return new Date(dateStr).toLocaleString(undefined, {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
}

function updateStatus(newStatus: string) {
    router.patch(`/leads/${props.lead.id}/status`, { status: newStatus }, {
        preserveScroll: true,
    });
}

function badgeVariant(status: string): 'default' | 'secondary' | 'outline' {
    if (status === 'new') return 'default';
    if (status === 'contacted') return 'secondary';
    return 'outline';
}

function badgeColor(status: string) {
    if (status === 'new') return 'bg-amber-500 hover:bg-amber-600';
    if (status === 'contacted') return 'bg-blue-500 hover:bg-blue-600 text-white';
    return '';
}

const isEmail = props.lead.contact.includes('@');
</script>

<template>
    <Head :title="`Lead: ${lead.name}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-1 flex-col gap-6 p-4 md:p-6 lg:max-w-5xl mx-auto w-full">
            <!-- Header -->
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <div class="flex items-center gap-3">
                        <h1 class="text-2xl font-semibold tracking-tight">{{ lead.name }}</h1>
                        <Badge :variant="badgeVariant(lead.status)" :class="badgeColor(lead.status)">
                            {{ lead.status.charAt(0).toUpperCase() + lead.status.slice(1) }}
                        </Badge>
                    </div>
                    <p class="mt-1 text-sm text-muted-foreground flex items-center gap-1.5">
                        <Clock class="size-3.5" />
                        Captured {{ formatDate(lead.created_at) }} via 
                        <Link :href="`/clients/${lead.client_id}`" class="font-medium hover:underline text-foreground">
                            {{ lead.client.name }}
                        </Link>
                    </p>
                </div>
                <Button variant="outline" size="sm" as-child>
                    <Link href="/leads">
                        <ArrowLeft class="mr-1 size-3.5" />
                        Back to leads
                    </Link>
                </Button>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- Left column: Lead Info -->
                <div class="space-y-6 md:col-span-1">
                    <Card class="border-sidebar-border/70">
                        <CardHeader>
                            <CardTitle class="text-lg">Lead Details</CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-4">
                            <div class="space-y-1">
                                <span class="text-xs text-muted-foreground">Name</span>
                                <div class="flex items-center gap-2 font-medium">
                                    <UserIcon class="size-4 text-muted-foreground" />
                                    {{ lead.name }}
                                </div>
                            </div>
                            
                            <div class="space-y-1">
                                <span class="text-xs text-muted-foreground">Contact Info</span>
                                <div class="flex items-center gap-2 font-medium">
                                    <Mail v-if="isEmail" class="size-4 text-muted-foreground" />
                                    <Phone v-else class="size-4 text-muted-foreground" />
                                    <a v-if="isEmail" :href="`mailto:${lead.contact}`" class="hover:underline text-blue-600 dark:text-blue-400">
                                        {{ lead.contact }}
                                    </a>
                                    <a v-else :href="`tel:${lead.contact}`" class="hover:underline text-blue-600 dark:text-blue-400">
                                        {{ lead.contact }}
                                    </a>
                                </div>
                            </div>

                            <div v-if="lead.notes" class="space-y-1 border-t border-sidebar-border/50 pt-4 mt-2">
                                <span class="text-xs text-muted-foreground">User Notes / Request Details</span>
                                <p class="text-sm border-l-2 border-primary/50 pl-3 py-1 bg-muted/20">
                                    {{ lead.notes }}
                                </p>
                            </div>
                        </CardContent>
                    </Card>

                    <Card class="border-sidebar-border/70">
                        <CardHeader>
                            <CardTitle class="text-lg">Actions</CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-3">
                            <Button 
                                class="w-full justify-start" 
                                :variant="lead.status === 'new' ? 'default' : 'outline'"
                                :disabled="lead.status === 'new'"
                                @click="updateStatus('new')"
                            >
                                Mark as New
                            </Button>
                            <Button 
                                class="w-full justify-start border-blue-200 text-blue-700 hover:bg-blue-50 dark:border-blue-900 dark:text-blue-300 dark:hover:bg-blue-900/30" 
                                :variant="lead.status === 'contacted' ? 'default' : 'outline'"
                                :disabled="lead.status === 'contacted'"
                                @click="updateStatus('contacted')"
                            >
                                Mark as Contacted
                            </Button>
                            <Button 
                                class="w-full justify-start border-emerald-200 text-emerald-700 hover:bg-emerald-50 dark:border-emerald-900 dark:text-emerald-300 dark:hover:bg-emerald-900/30" 
                                :variant="lead.status === 'closed' ? 'default' : 'outline'"
                                :disabled="lead.status === 'closed'"
                                @click="updateStatus('closed')"
                            >
                                Mark as Closed
                            </Button>
                        </CardContent>
                    </Card>
                </div>

                <!-- Right column: Conversation -->
                <div class="md:col-span-2">
                    <Card class="border-sidebar-border/70 h-full flex flex-col">
                        <CardHeader class="flex flex-row items-center justify-between py-4 pb-2">
                            <div>
                                <CardTitle class="text-lg">Conversation Snapshot</CardTitle>
                                <CardDescription>
                                    The final messages leading up to the contact request.
                                </CardDescription>
                            </div>
                            <Button v-if="lead.chat_session_id" variant="outline" size="sm" as-child>
                                <Link :href="`/clients/${lead.client_id}/chat-history/${lead.chat_session_id}/messages`">
                                    Full session <ExternalLink class="ml-1.5 size-3.5" />
                                </Link>
                            </Button>
                        </CardHeader>
                        
                        <CardContent class="flex-1 mt-4">
                            <!-- Chat Bubble Replay -->
                            <div v-if="lead.conversation_snapshot && lead.conversation_snapshot.length > 0" class="rounded-xl border border-sidebar-border/70 bg-muted/10 p-4 space-y-4">
                                <div 
                                    v-for="(msg, i) in lead.conversation_snapshot" 
                                    :key="i"
                                    class="flex flex-col"
                                    :class="msg.role === 'user' ? 'items-end' : 'items-start'"
                                >
                                    <div class="mb-1 text-[10px] font-medium text-muted-foreground uppercase tracking-wider px-1">
                                        {{ msg.role === 'user' ? 'Visitor' : 'AI Assistant' }}
                                    </div>
                                    <div 
                                        class="px-4 py-2.5 rounded-2xl max-w-[85%] text-sm"
                                        :class="msg.role === 'user' 
                                            ? 'bg-primary text-primary-foreground rounded-tr-sm' 
                                            : 'bg-muted border border-border/50 text-foreground rounded-tl-sm shadow-xs'"
                                    >
                                        <p class="whitespace-pre-wrap">{{ msg.content }}</p>
                                    </div>
                                </div>
                                
                                <div class="flex flex-col items-center mt-6 pt-4 border-t border-dashed border-sidebar-border/60">
                                    <Badge variant="outline" class="bg-card uppercase font-mono tracking-widest text-[10px] text-muted-foreground mb-4">
                                        Lead Capture Triggered ({{ lead.trigger }})
                                    </Badge>
                                    <div class="w-full space-y-3">
                                        <div class="flex flex-col items-start">
                                            <div class="mb-1 text-[10px] font-medium text-muted-foreground uppercase tracking-wider px-1">
                                                AI Assistant
                                            </div>
                                            <div class="px-4 py-3 rounded-2xl rounded-tl-sm w-full border border-blue-200 bg-blue-50/50 text-blue-900 dark:border-blue-900/50 dark:bg-blue-900/20 dark:text-blue-100 text-sm">
                                                <p><span class="font-semibold text-xs uppercase tracking-wider text-blue-600 dark:text-blue-400 mr-2">Captured Name</span> {{ lead.name }}</p>
                                                <p class="mt-2"><span class="font-semibold text-xs uppercase tracking-wider text-blue-600 dark:text-blue-400 mr-2">Captured Contact</span> {{ lead.contact }}</p>
                                                <p v-if="lead.notes" class="mt-2"><span class="font-semibold text-xs uppercase tracking-wider text-blue-600 dark:text-blue-400 mr-2">Notes</span> {{ lead.notes }}</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div v-else class="flex flex-col items-center justify-center h-48 border border-dashed border-sidebar-border/70 rounded-xl text-center text-muted-foreground">
                                <p class="text-sm">No conversation history attached to this lead.</p>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
