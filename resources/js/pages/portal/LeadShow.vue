<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ArrowLeft, Bot, Clock, Mail, Phone, User as UserIcon } from 'lucide-vue-next';
import { ref } from 'vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';

type Message = { role: 'user' | 'assistant'; content: string };

type LeadFull = {
    id: number;
    name: string;
    contact: string;
    user_request: string | null;
    trigger: string;
    status: string;
    notes: string | null;
    created_at: string;
    conversation_snapshot: Message[] | null;
    chat_session_id: number | null;
};

const props = defineProps<{ lead: LeadFull }>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/portal/dashboard' },
    { title: 'Leads', href: '/portal/leads' },
    { title: props.lead.name, href: `/portal/leads/${props.lead.id}` },
];

const updatingStatus = ref(false);

function updateStatus(status: string) {
    if (props.lead.status === status) return;
    updatingStatus.value = true;
    router.patch(`/portal/leads/${props.lead.id}/status`, { status }, {
        preserveScroll: true,
        onFinish: () => (updatingStatus.value = false),
    });
}

function fmt(v: string) {
    const d = new Date(v.endsWith('Z') ? v : v + 'Z');
    return d.toLocaleString(undefined, {
        year: 'numeric', month: 'long', day: 'numeric',
        hour: '2-digit', minute: '2-digit',
    });
}

const isEmail = props.lead.contact.includes('@');

function statusClass(s: string) {
    if (s === 'new') return 'border-amber-300 bg-amber-50 text-amber-700 dark:border-amber-700 dark:bg-amber-900/20 dark:text-amber-400';
    if (s === 'contacted') return 'border-blue-300 bg-blue-50 text-blue-700 dark:border-blue-700 dark:bg-blue-900/20 dark:text-blue-400';
    return 'border-sidebar-border/60 bg-muted/50 text-muted-foreground';
}
</script>

<template>
    <Head :title="`Lead: ${lead.name}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-1 flex-col gap-6 p-4 md:p-6 lg:max-w-5xl mx-auto w-full">

            <!-- Header -->
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <div class="flex items-center gap-3 flex-wrap">
                        <h1 class="text-2xl font-bold tracking-tight">{{ lead.name }}</h1>
                        <Badge
                            variant="outline"
                            class="h-6 px-2.5 text-[10px] font-bold uppercase tracking-widest"
                            :class="statusClass(lead.status)"
                        >
                            {{ lead.status }}
                        </Badge>
                    </div>
                    <p class="mt-1.5 flex items-center gap-1.5 text-sm text-muted-foreground">
                        <Clock class="h-3.5 w-3.5 shrink-0" />
                        Captured {{ fmt(lead.created_at) }}
                    </p>
                </div>
                <Button variant="outline" size="sm" as-child class="shrink-0">
                    <Link href="/portal/leads">
                        <ArrowLeft class="mr-1.5 h-3.5 w-3.5" />
                        Back to leads
                    </Link>
                </Button>
            </div>

            <div class="grid grid-cols-1 gap-6 md:grid-cols-3">

                <!-- ── Left column ─────────────────────────────────────────── -->
                <div class="space-y-5 md:col-span-1">

                    <!-- Contact card -->
                    <Card class="border-sidebar-border/60 shadow-sm">
                        <CardHeader class="pb-3">
                            <CardTitle class="text-base">Contact Info</CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-4">
                            <div class="space-y-0.5">
                                <p class="text-[10px] font-semibold uppercase tracking-wider text-muted-foreground">Name</p>
                                <div class="flex items-center gap-2 text-sm font-medium">
                                    <UserIcon class="h-3.5 w-3.5 text-muted-foreground" />
                                    {{ lead.name }}
                                </div>
                            </div>

                            <div class="space-y-0.5">
                                <p class="text-[10px] font-semibold uppercase tracking-wider text-muted-foreground">{{ isEmail ? 'Email' : 'Phone' }}</p>
                                <div class="flex items-center gap-2 text-sm font-medium">
                                    <Mail v-if="isEmail" class="h-3.5 w-3.5 text-muted-foreground" />
                                    <Phone v-else class="h-3.5 w-3.5 text-muted-foreground" />
                                    <a
                                        :href="isEmail ? `mailto:${lead.contact}` : `tel:${lead.contact}`"
                                        class="text-blue-600 hover:underline dark:text-blue-400"
                                    >
                                        {{ lead.contact }}
                                    </a>
                                </div>
                            </div>

                            <div v-if="lead.user_request" class="space-y-1 border-t border-sidebar-border/50 pt-3">
                                <p class="text-[10px] font-semibold uppercase tracking-wider text-muted-foreground">What they asked</p>
                                <p class="text-sm text-muted-foreground border-l-2 border-primary/40 pl-3 py-1 bg-muted/30 rounded-r-md">
                                    "{{ lead.user_request }}"
                                </p>
                            </div>

                            <div v-if="lead.notes" class="space-y-1 border-t border-sidebar-border/50 pt-3">
                                <p class="text-[10px] font-semibold uppercase tracking-wider text-muted-foreground">Notes</p>
                                <p class="text-sm text-muted-foreground">{{ lead.notes }}</p>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Status actions card -->
                    <Card class="border-sidebar-border/60 shadow-sm">
                        <CardHeader class="pb-3">
                            <CardTitle class="text-base">Update Status</CardTitle>
                            <CardDescription class="text-xs">Track your follow-up progress.</CardDescription>
                        </CardHeader>
                        <CardContent class="space-y-2">
                            <button
                                class="flex w-full items-center gap-2.5 rounded-lg border px-3.5 py-2.5 text-sm font-medium transition-all"
                                :class="lead.status === 'new'
                                    ? 'border-amber-400 bg-amber-50 text-amber-700 dark:border-amber-600 dark:bg-amber-900/20 dark:text-amber-300'
                                    : 'border-sidebar-border/50 text-muted-foreground hover:border-sidebar-border hover:bg-muted/30'"
                                :disabled="updatingStatus || lead.status === 'new'"
                                @click="updateStatus('new')"
                            >
                                <span class="h-2 w-2 rounded-full bg-amber-400 shrink-0" />
                                New Lead
                            </button>
                            <button
                                class="flex w-full items-center gap-2.5 rounded-lg border px-3.5 py-2.5 text-sm font-medium transition-all"
                                :class="lead.status === 'contacted'
                                    ? 'border-blue-400 bg-blue-50 text-blue-700 dark:border-blue-600 dark:bg-blue-900/20 dark:text-blue-300'
                                    : 'border-sidebar-border/50 text-muted-foreground hover:border-sidebar-border hover:bg-muted/30'"
                                :disabled="updatingStatus || lead.status === 'contacted'"
                                @click="updateStatus('contacted')"
                            >
                                <span class="h-2 w-2 rounded-full bg-blue-400 shrink-0" />
                                Contacted
                            </button>
                            <button
                                class="flex w-full items-center gap-2.5 rounded-lg border px-3.5 py-2.5 text-sm font-medium transition-all"
                                :class="lead.status === 'closed'
                                    ? 'border-emerald-400 bg-emerald-50 text-emerald-700 dark:border-emerald-600 dark:bg-emerald-900/20 dark:text-emerald-300'
                                    : 'border-sidebar-border/50 text-muted-foreground hover:border-sidebar-border hover:bg-muted/30'"
                                :disabled="updatingStatus || lead.status === 'closed'"
                                @click="updateStatus('closed')"
                            >
                                <span class="h-2 w-2 rounded-full bg-emerald-400 shrink-0" />
                                Closed / Done
                            </button>
                        </CardContent>
                    </Card>
                </div>

                <!-- ── Right column: conversation ───────────────────────────── -->
                <div class="md:col-span-2">
                    <Card class="flex h-full flex-col border-sidebar-border/60 shadow-sm">
                        <CardHeader class="flex flex-row items-center justify-between pb-2">
                            <div>
                                <CardTitle class="text-base">Conversation Snapshot</CardTitle>
                                <CardDescription class="text-xs">
                                    The messages leading up to this contact request.
                                </CardDescription>
                            </div>
                            <Badge variant="outline" class="shrink-0 text-[9px] font-mono uppercase tracking-widest text-muted-foreground">
                                {{ lead.trigger }}
                            </Badge>
                        </CardHeader>

                        <CardContent class="flex-1 mt-2">
                            <!-- Messages -->
                            <div
                                v-if="lead.conversation_snapshot && lead.conversation_snapshot.length > 0"
                                class="space-y-4 rounded-xl border border-sidebar-border/50 bg-muted/10 p-4"
                            >
                                <div
                                    v-for="(msg, i) in lead.conversation_snapshot"
                                    :key="i"
                                    class="flex"
                                    :class="msg.role === 'user' ? 'justify-end' : 'justify-start'"
                                >
                                    <div class="flex max-w-[85%] gap-2.5" :class="msg.role === 'user' ? 'flex-row-reverse' : 'flex-row'">
                                        <div class="mt-auto mb-1 flex h-7 w-7 shrink-0 items-center justify-center rounded-full"
                                            :class="msg.role === 'user' ? 'bg-blue-600 text-white' : 'bg-emerald-600 text-white'">
                                            <UserIcon v-if="msg.role === 'user'" class="h-3.5 w-3.5" />
                                            <Bot v-else class="h-3.5 w-3.5" />
                                        </div>
                                        <div class="flex flex-col gap-0.5" :class="msg.role === 'user' ? 'items-end' : 'items-start'">
                                            <span class="text-[9px] font-bold uppercase tracking-wider text-muted-foreground px-1">
                                                {{ msg.role === 'user' ? 'Visitor' : 'Assistant' }}
                                            </span>
                                            <div
                                                class="rounded-2xl px-4 py-2.5 text-sm shadow-sm"
                                                :class="msg.role === 'user'
                                                    ? 'rounded-br-sm bg-blue-600 text-white'
                                                    : 'rounded-bl-sm border border-sidebar-border/50 bg-muted text-foreground'"
                                            >
                                                <p class="whitespace-pre-wrap leading-relaxed">{{ msg.content }}</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Capture summary -->
                                <div class="mt-4 rounded-xl border border-blue-200 bg-blue-50/60 p-4 dark:border-blue-900/40 dark:bg-blue-900/10">
                                    <p class="mb-2.5 text-[10px] font-bold uppercase tracking-widest text-blue-600 dark:text-blue-400">Lead Captured</p>
                                    <div class="space-y-1.5 text-sm">
                                        <p><span class="font-semibold text-foreground">Name:</span> {{ lead.name }}</p>
                                        <p><span class="font-semibold text-foreground">Contact:</span> {{ lead.contact }}</p>
                                        <p v-if="lead.notes"><span class="font-semibold text-foreground">Notes:</span> {{ lead.notes }}</p>
                                    </div>
                                </div>
                            </div>

                            <!-- No conversation -->
                            <div v-else class="flex h-48 flex-col items-center justify-center rounded-xl border border-dashed border-sidebar-border/60 text-center text-muted-foreground">
                                <p class="text-sm">No conversation history attached to this lead.</p>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
