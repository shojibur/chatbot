<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import axios from 'axios';
import DOMPurify from 'dompurify';
import { Bot, Info, Loader2, RotateCcw, Send, User as UserIcon, Zap } from 'lucide-vue-next';
import { marked } from 'marked';
import { computed, nextTick, onMounted, ref } from 'vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem, ClientWorkspace } from '@/types';

type Props = {
    client: ClientWorkspace;
};

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/portal/dashboard' },
    { title: 'Playground', href: '/portal/playground' },
];

interface ChatMessage {
    role: 'user' | 'assistant';
    content: string;
    cached?: boolean;
    error?: boolean;
    chunks?: number;
    tokens?: number;
    ms?: number;
    timestamp: Date;
}

const messages   = ref<ChatMessage[]>([]);
const input      = ref('');
const loading    = ref(false);
const messagesEl = ref<HTMLElement | null>(null);
const inputEl    = ref<HTMLTextAreaElement | null>(null);
const sessionId  = ref(crypto.randomUUID());
const showStats  = ref(true);

// Session stats
const totalTokens  = ref(0);
const totalRequests = ref(0);
const cacheHits    = ref(0);

// Welcome message
const welcome = props.client.widget_settings?.welcome_message || `Hello! I'm the ${props.client.name} assistant. Ask me anything.`;

onMounted(() => {
    messages.value.push({ role: 'assistant', content: welcome, timestamp: new Date() });
    inputEl.value?.focus();
});

marked.setOptions({ breaks: true, gfm: true });

function parseMarkdown(text: string): string {
    if (!text) return '';
    return DOMPurify.sanitize(marked.parse(text) as string);
}

function scrollToBottom() {
    nextTick(() => {
        if (messagesEl.value) {
            messagesEl.value.scrollTop = messagesEl.value.scrollHeight;
        }
    });
}

async function send() {
    const text = input.value.trim();
    if (!text || loading.value) return;

    input.value = '';
    autoResizeTextarea();

    messages.value.push({ role: 'user', content: text, timestamp: new Date() });
    loading.value = true;
    totalRequests.value++;
    scrollToBottom();

    const t0 = performance.now();

    try {
        const res = await axios.post('/portal/playground/chat', {
            message:    text,
            session_id: sessionId.value,
        });

        const ms   = Math.round(performance.now() - t0);
        const data = res.data;

        if (data.cached) cacheHits.value++;
        totalTokens.value += data.tokens_used ?? 0;

        messages.value.push({
            role:      'assistant',
            content:   data.answer,
            cached:    data.cached ?? false,
            chunks:    data.chunks_used,
            tokens:    data.tokens_used,
            ms,
            timestamp: new Date(),
        });
    } catch (err: any) {
        const msg = err?.response?.data?.error ?? err?.message ?? 'Something went wrong.';
        messages.value.push({
            role:      'assistant',
            content:   msg,
            error:     true,
            timestamp: new Date(),
        });
    } finally {
        loading.value = false;
        scrollToBottom();
        nextTick(() => inputEl.value?.focus());
    }
}

function onKeydown(e: KeyboardEvent) {
    if (e.key === 'Enter' && !e.shiftKey) {
        e.preventDefault();
        send();
    }
}

function autoResizeTextarea() {
    if (!inputEl.value) return;
    inputEl.value.style.height = 'auto';
    inputEl.value.style.height = Math.min(inputEl.value.scrollHeight, 160) + 'px';
}

function clearChat() {
    messages.value = [];
    sessionId.value = crypto.randomUUID();
    totalTokens.value = 0;
    totalRequests.value = 0;
    cacheHits.value = 0;
    messages.value.push({ role: 'assistant', content: welcome, timestamp: new Date() });
    nextTick(() => inputEl.value?.focus());
}

function fmt(d: Date) {
    return d.toLocaleTimeString(undefined, { hour: '2-digit', minute: '2-digit' });
}

const accentColor = computed(() => props.client.widget_settings?.accent_color || '#6366f1');
</script>

<template>
    <Head title="Chat Playground" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-1 flex-col gap-4 p-4 md:p-6">

            <!-- Header -->
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h1 class="text-2xl font-bold tracking-tight">Chat Playground</h1>
                    <p class="mt-1 text-sm text-muted-foreground">
                        Test how your chatbot responds using your current knowledge base.
                    </p>
                </div>
                <div class="flex gap-2">
                    <Button variant="outline" size="sm" @click="showStats = !showStats">
                        <Info class="mr-1.5 h-4 w-4" />
                        {{ showStats ? 'Hide' : 'Show' }} Stats
                    </Button>
                    <Button variant="outline" size="sm" @click="clearChat">
                        <RotateCcw class="mr-1.5 h-4 w-4" />
                        New Chat
                    </Button>
                </div>
            </div>

            <!-- Main layout -->
            <div class="grid gap-4" :class="showStats ? 'lg:grid-cols-[1fr_260px]' : ''">

                <!-- Chat window -->
                <div class="flex flex-col overflow-hidden rounded-xl border border-sidebar-border/60 bg-card shadow-sm" style="height: calc(100vh - 220px); min-height: 500px;">

                    <!-- Chat header -->
                    <div class="flex shrink-0 items-center justify-between border-b border-sidebar-border/40 bg-muted/10 px-4 py-3">
                        <div class="flex items-center gap-2.5">
                            <div class="flex h-8 w-8 items-center justify-center rounded-full" :style="{ background: accentColor }">
                                <Bot class="h-4 w-4 text-white" />
                            </div>
                            <div>
                                <p class="text-sm font-semibold">{{ client.name }}</p>
                                <p class="text-[10px] text-muted-foreground">{{ client.chat_model }}</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-1.5">
                            <span class="h-2 w-2 rounded-full bg-emerald-500 animate-pulse" />
                            <span class="text-xs text-muted-foreground">Online</span>
                        </div>
                    </div>

                    <!-- Messages -->
                    <div ref="messagesEl" class="flex-1 overflow-y-auto space-y-5 p-4 md:p-5">

                        <div
                            v-for="(msg, i) in messages"
                            :key="i"
                            class="flex gap-3"
                            :class="msg.role === 'user' ? 'flex-row-reverse' : 'flex-row'"
                        >
                            <!-- Avatar -->
                            <div
                                class="mt-auto mb-1 flex h-7 w-7 shrink-0 items-center justify-center rounded-full"
                                :class="msg.role === 'user' ? 'bg-primary text-primary-foreground' : ''"
                                :style="msg.role === 'assistant' ? { background: accentColor } : {}"
                            >
                                <UserIcon v-if="msg.role === 'user'" class="h-3.5 w-3.5" />
                                <Bot v-else class="h-3.5 w-3.5 text-white" />
                            </div>

                            <!-- Bubble -->
                            <div class="flex max-w-[80%] flex-col gap-1" :class="msg.role === 'user' ? 'items-end' : 'items-start'">
                                <div
                                    class="rounded-2xl px-4 py-2.5 text-sm shadow-sm"
                                    :class="[
                                        msg.role === 'user'
                                            ? 'rounded-br-sm bg-primary text-primary-foreground'
                                            : msg.error
                                                ? 'rounded-bl-sm border border-red-200 bg-red-50 text-red-700 dark:border-red-900/30 dark:bg-red-900/10 dark:text-red-400'
                                                : 'rounded-bl-sm border border-sidebar-border/40 bg-muted/50 text-foreground',
                                    ]"
                                >
                                    <div
                                        v-if="msg.role === 'assistant'"
                                        class="prose prose-sm dark:prose-invert max-w-none"
                                        v-html="parseMarkdown(msg.content)"
                                    />
                                    <p v-else class="whitespace-pre-wrap leading-relaxed">{{ msg.content }}</p>
                                </div>

                                <!-- Meta row -->
                                <div class="flex items-center gap-2 px-1">
                                    <span class="text-[10px] text-muted-foreground">{{ fmt(msg.timestamp) }}</span>
                                    <Badge v-if="msg.cached" variant="secondary" class="h-4 px-1.5 text-[8px] border-emerald-200 bg-emerald-50 text-emerald-700 dark:bg-emerald-900/20 dark:text-emerald-400">
                                        <Zap class="mr-0.5 h-2 w-2" /> cached
                                    </Badge>
                                    <span v-if="msg.ms" class="text-[10px] text-muted-foreground">{{ msg.ms }}ms</span>
                                    <span v-if="msg.tokens" class="text-[10px] text-muted-foreground">{{ msg.tokens }} tokens</span>
                                </div>
                            </div>
                        </div>

                        <!-- Typing indicator -->
                        <div v-if="loading" class="flex flex-row gap-3">
                            <div class="mt-auto mb-1 flex h-7 w-7 shrink-0 items-center justify-center rounded-full" :style="{ background: accentColor }">
                                <Bot class="h-3.5 w-3.5 text-white" />
                            </div>
                            <div class="flex items-center gap-1.5 rounded-2xl rounded-bl-sm border border-sidebar-border/40 bg-muted/50 px-4 py-3">
                                <span class="h-1.5 w-1.5 rounded-full bg-muted-foreground/50 animate-bounce" />
                                <span class="h-1.5 w-1.5 rounded-full bg-muted-foreground/50 animate-bounce [animation-delay:0.15s]" />
                                <span class="h-1.5 w-1.5 rounded-full bg-muted-foreground/50 animate-bounce [animation-delay:0.3s]" />
                            </div>
                        </div>
                    </div>

                    <!-- Input bar -->
                    <div class="shrink-0 border-t border-sidebar-border/40 bg-card px-4 py-3">
                        <div class="flex items-end gap-3">
                            <textarea
                                ref="inputEl"
                                v-model="input"
                                rows="1"
                                :disabled="loading"
                                placeholder="Type a message… (Enter to send, Shift+Enter for new line)"
                                class="flex-1 resize-none overflow-hidden rounded-xl border border-input bg-muted/30 px-4 py-2.5 text-sm outline-none transition-all focus:border-ring focus:ring-1 focus:ring-ring disabled:opacity-50"
                                style="min-height: 44px; max-height: 160px;"
                                @keydown="onKeydown"
                                @input="autoResizeTextarea"
                            />
                            <Button
                                size="icon"
                                class="h-11 w-11 shrink-0 rounded-xl"
                                :disabled="!input.trim() || loading"
                                :style="{ background: accentColor, borderColor: accentColor }"
                                @click="send"
                            >
                                <Loader2 v-if="loading" class="h-4 w-4 animate-spin text-white" />
                                <Send v-else class="h-4 w-4 text-white" />
                            </Button>
                        </div>
                        <p class="mt-1.5 text-center text-[10px] text-muted-foreground/50">
                            Shift+Enter for new line · Responses use your live knowledge base
                        </p>
                    </div>
                </div>

                <!-- Stats panel -->
                <div v-if="showStats" class="space-y-3">

                    <!-- Session stats -->
                    <div class="rounded-xl border border-sidebar-border/60 bg-card p-4 shadow-sm">
                        <p class="mb-3 text-[10px] font-bold uppercase tracking-widest text-muted-foreground">Session Stats</p>
                        <div class="space-y-2 text-sm">
                            <div class="flex items-center justify-between">
                                <span class="text-muted-foreground">Messages</span>
                                <span class="font-semibold tabular-nums">{{ totalRequests }}</span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-muted-foreground">Tokens used</span>
                                <span class="font-semibold tabular-nums">{{ totalTokens.toLocaleString() }}</span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-muted-foreground">Cache hits</span>
                                <span class="font-semibold tabular-nums text-emerald-600 dark:text-emerald-400">{{ cacheHits }}</span>
                            </div>
                        </div>
                    </div>


                    <!-- Tips -->
                    <div class="rounded-xl border border-sidebar-border/60 bg-card p-4 shadow-sm">
                        <p class="mb-2 text-[10px] font-bold uppercase tracking-widest text-muted-foreground">Tips</p>
                        <ul class="space-y-1.5 text-xs text-muted-foreground">
                            <li>• Ask questions your customers might ask</li>
                            <li>• Check if all your knowledge sources are indexed</li>
                            <li>• Cached responses are instant &amp; free</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
