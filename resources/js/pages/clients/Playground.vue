<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import DOMPurify from 'dompurify';
import { ArrowLeft, Bot, Info, RotateCcw, Send } from 'lucide-vue-next';
import { marked } from 'marked';
import { computed, nextTick, ref } from 'vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader } from '@/components/ui/card';
import AppLayout from '@/layouts/AppLayout.vue';
import { dashboard } from '@/routes';
import type { BreadcrumbItem, ClientWorkspace } from '@/types';

type Props = {
    client: ClientWorkspace;
    api_base_url: string;
};

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: dashboard() },
    { title: 'Clients', href: '/clients' },
    { title: props.client.name, href: `/clients/${props.client.id}` },
    {
        title: 'Chat Playground',
        href: `/clients/${props.client.id}/playground`,
    },
];

interface ChatMessage {
    role: 'user' | 'assistant' | 'system';
    content: string;
    cached?: boolean;
    tokens?: number;
    cost?: number;
    timestamp: Date;
    error?: boolean;
    debug?: Record<string, unknown>;
}

const messages = ref<ChatMessage[]>([]);
const input = ref('');
const loading = ref(false);
const messagesEl = ref<HTMLElement | null>(null);
const sessionId = ref(crypto.randomUUID());
const totalTokens = ref(0);
const totalCost = ref(0);
const requestCount = ref(0);
const cacheHits = ref(0);

const primaryColor = computed(
    () => props.client.widget_settings?.primary_color || '#6366f1',
);
const accentColor = computed(
    () => props.client.widget_settings?.accent_color || '#8b5cf6',
);
const leadCaptureIntroMessage = computed(
    () =>
        props.client.widget_settings?.lead_capture_intro_message ||
        'I can help with that! May I get your **name** first so our team can follow up with you?',
);

// Add welcome message on mount
const welcomeMessage = props.client.widget_settings?.welcome_message;

if (welcomeMessage) {
    messages.value.push({
        role: 'assistant',
        content: welcomeMessage,
        timestamp: new Date(),
    });
}

// Lead capture state
type LeadStep = null | 'ask_name' | 'ask_contact' | 'ask_notes' | 'done';
const leadStep = ref<LeadStep>(null);
const leadCapturedThisSession = ref(false);
const leadData = ref({ name: '', contact: '', notes: '', triggerMessage: '' });

function userIsRefusing(text: string): boolean {
    const lower = text.toLowerCase().trim();
    const refusalPatterns = [
        'no',
        'nah',
        'nope',
        'not',
        "don't",
        'dont',
        'do not',
        'i refuse',
        'skip',
        'pass',
        'never mind',
        'nevermind',
        'forget it',
        'stop',
        'cancel',
        "i'd rather not",
        'i rather not',
        'no thanks',
        'no thank',
        'not interested',
        'leave me alone',
        "i don't want",
        'i do not want',
        'i dont want',
        'prefer not',
        'rather not',
        'not now',
    ];

    return refusalPatterns.some((pattern) => lower.includes(pattern));
}

function cancelLeadCapture(): void {
    leadStep.value = null;
    leadData.value = { name: '', contact: '', notes: '', triggerMessage: '' };
    messages.value.push({
        role: 'assistant',
        content: `No problem at all. Feel free to keep chatting and ask anything else.`,
        timestamp: new Date(),
    });
    scrollToBottom();
}

async function handleLeadStep(text: string): Promise<boolean> {
    if (!leadStep.value || leadStep.value === 'done') {
        return false;
    }

    // Add user response to chat
    messages.value.push({ role: 'user', content: text, timestamp: new Date() });
    scrollToBottom();

    if (userIsRefusing(text)) {
        cancelLeadCapture();
        return true;
    }

    if (leadStep.value === 'ask_name') {
        leadData.value.name = text;
        leadStep.value = 'ask_contact';
        messages.value.push({
            role: 'assistant',
            content: `Thanks ${leadData.value.name}! 📱 What's the best phone number or email address to reach you?`,
            timestamp: new Date(),
        });
        return true;
    }

    if (leadStep.value === 'ask_contact') {
        leadData.value.contact = text;
        leadStep.value = 'ask_notes';
        messages.value.push({
            role: 'assistant',
            content: `Got it! One last thing — can you briefly describe what you need help with?`,
            timestamp: new Date(),
        });
        return true;
    }

    if (leadStep.value === 'ask_notes') {
        leadData.value.notes = text;
        leadStep.value = 'done';
        loading.value = true;
        scrollToBottom();

        try {
            await fetch(`${props.api_base_url}/api/v1/leads`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                },
                body: JSON.stringify({
                    client_code: props.client.unique_code,
                    session_token: sessionId.value, // Usually the widget gets a session_token string back from ChatController, but here we just pass the Playground UUID for testing
                    name: leadData.value.name,
                    contact: leadData.value.contact,
                    user_request: leadData.value.triggerMessage,
                    notes: leadData.value.notes,
                    trigger: 'intent',
                }),
            });

            leadCapturedThisSession.value = true;
            messages.value.push({
                role: 'assistant',
                content: `✅ **Thank you!** Our team will contact you soon.`,
                timestamp: new Date(),
            });
        } catch {
            messages.value.push({
                role: 'assistant',
                content: `Sorry, there was a problem saving your details.`,
                timestamp: new Date(),
            });
        } finally {
            loading.value = false;
            setTimeout(() => {
                leadStep.value = null;
                leadData.value = {
                    name: '',
                    contact: '',
                    notes: '',
                    triggerMessage: '',
                };
            }, 4000);
        }

        return true;
    }

    return false;
}

async function send() {
    const text = input.value.trim();

    if (!text || loading.value) {
        return;
    }

    input.value = '';

    if (leadStep.value && leadStep.value !== 'done') {
        await handleLeadStep(text);

        return;
    }

    messages.value.push({
        role: 'user',
        content: text,
        timestamp: new Date(),
    });

    loading.value = true;
    requestCount.value++;
    scrollToBottom();

    const startTime = performance.now();

    try {
        const res = await fetch(`${props.api_base_url}/api/v1/chat`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
            },
            body: JSON.stringify({
                client_code: props.client.unique_code,
                message: text,
                session_id: sessionId.value,
            }),
        });

        const data = await res.json();
        const elapsed = Math.round(performance.now() - startTime);

        if (res.ok && data.answer) {
            if (data.cached) {
                cacheHits.value++;
            }

            // If the live widget detects lead_capture, it waits 600ms gracefully and intercepts.
            // Let's do the exact same thing here.
            messages.value.push({
                role: 'assistant',
                content: data.answer,
                cached: data.cached ?? false,
                timestamp: new Date(),
                debug: {
                    response_time_ms: elapsed,
                    cached: data.cached ?? false,
                    status: res.status,
                    lead_capture: data.lead_capture ?? false,
                },
            });

            if (
                data.lead_capture &&
                !leadStep.value &&
                !leadCapturedThisSession.value
            ) {
                leadData.value.triggerMessage = text;
                leadStep.value = 'ask_name';

                setTimeout(() => {
                    messages.value.push({
                        role: 'assistant',
                        content: leadCaptureIntroMessage.value,
                        timestamp: new Date(),
                    });
                    scrollToBottom();
                }, 600);
            }
        } else {
            messages.value.push({
                role: 'assistant',
                content: data.error || data.message || 'Something went wrong.',
                timestamp: new Date(),
                error: true,
                debug: {
                    response_time_ms: elapsed,
                    status: res.status,
                    error: data.error || data.message,
                    errors: data.errors,
                },
            });
        }
    } catch (err) {
        messages.value.push({
            role: 'assistant',
            content:
                'Failed to connect to the chat API. Make sure your server is running.',
            timestamp: new Date(),
            error: true,
            debug: { error: String(err) },
        });
    } finally {
        loading.value = false;
        scrollToBottom();
    }
}

function scrollToBottom() {
    nextTick(() => {
        if (messagesEl.value) {
            messagesEl.value.scrollTop = messagesEl.value.scrollHeight;
        }
    });
}

function clearChat() {
    messages.value = [];
    sessionId.value = crypto.randomUUID();
    totalTokens.value = 0;
    totalCost.value = 0;
    requestCount.value = 0;
    cacheHits.value = 0;
    leadStep.value = null;
    leadCapturedThisSession.value = false;

    if (welcomeMessage) {
        messages.value.push({
            role: 'assistant',
            content: welcomeMessage,
            timestamp: new Date(),
        });
    }
}

function formatTime(date: Date): string {
    return date.toLocaleTimeString(undefined, {
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit',
    });
}

const showDebug = ref(true);

marked.setOptions({ breaks: true, gfm: true });

function parseMessage(text: string): string {
    if (!text) return '';
    const rawHtml = marked.parse(text) as string;
    return DOMPurify.sanitize(rawHtml, {
        ALLOWED_TAGS: [
            'b',
            'i',
            'em',
            'strong',
            'a',
            'p',
            'br',
            'ul',
            'ol',
            'li',
            'code',
            'pre',
            'blockquote',
            'h1',
            'h2',
            'h3',
            'h4',
            'h5',
            'h6',
            'hr',
            'table',
            'thead',
            'tbody',
            'tr',
            'th',
            'td',
        ],
    });
}
</script>

<template>
    <Head :title="`${client.name} — Chat Playground`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-1 flex-col gap-6 p-4 md:p-6">
            <!-- Header -->
            <div
                class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between"
            >
                <div class="min-w-0">
                    <div class="flex flex-wrap items-center gap-2">
                        <h1 class="text-2xl font-semibold tracking-tight">
                            Chat Playground
                        </h1>
                        <Badge variant="outline">{{ client.name }}</Badge>
                        <Badge variant="secondary">{{
                            client.chat_model
                        }}</Badge>
                        <Badge
                            v-if="client.semantic_cache_enabled"
                            variant="outline"
                            class="text-xs"
                        >
                            Cache ON
                        </Badge>
                    </div>
                    <p class="mt-1 text-sm text-muted-foreground">
                        Test your chatbot's responses live. See how it reasons
                        with the knowledge base.
                    </p>
                </div>
                <div class="flex shrink-0 gap-2">
                    <Button
                        variant="outline"
                        size="sm"
                        @click="showDebug = !showDebug"
                    >
                        <Info class="mr-1 size-3.5" />
                        {{ showDebug ? 'Hide' : 'Show' }} debug
                    </Button>
                    <Button variant="outline" size="sm" @click="clearChat">
                        <RotateCcw class="mr-1 size-3.5" />
                        Clear chat
                    </Button>
                    <Button variant="outline" size="sm" as-child>
                        <Link :href="`/clients/${client.id}`">
                            <ArrowLeft class="mr-1 size-3.5" />
                            Back to workspace
                        </Link>
                    </Button>
                </div>
            </div>

            <!-- Stats bar -->
            <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
                <div
                    class="rounded-lg border border-sidebar-border/70 px-4 py-2.5"
                >
                    <p class="text-xs text-muted-foreground">Requests</p>
                    <p class="text-lg font-semibold tabular-nums">
                        {{ requestCount }}
                    </p>
                </div>
                <div
                    class="rounded-lg border border-sidebar-border/70 px-4 py-2.5"
                >
                    <p class="text-xs text-muted-foreground">Cache hits</p>
                    <p class="text-lg font-semibold tabular-nums">
                        {{ cacheHits }}
                    </p>
                </div>
                <div
                    class="rounded-lg border border-sidebar-border/70 px-4 py-2.5"
                >
                    <p class="text-xs text-muted-foreground">Session ID</p>
                    <p class="truncate font-mono text-xs text-muted-foreground">
                        {{ sessionId.slice(0, 8) }}...
                    </p>
                </div>
                <div
                    class="rounded-lg border border-sidebar-border/70 px-4 py-2.5"
                >
                    <p class="text-xs text-muted-foreground">Model</p>
                    <p class="text-sm font-medium">{{ client.chat_model }}</p>
                </div>
            </div>

            <!-- Chat area -->
            <div
                class="grid gap-6"
                :class="showDebug ? 'xl:grid-cols-[1fr_360px]' : ''"
            >
                <!-- Chat panel -->
                <Card class="flex flex-col gap-0 border-sidebar-border/70">
                    <!-- Chat header -->
                    <div
                        class="flex items-center gap-3 rounded-t-xl px-4 py-3"
                        :style="{ background: primaryColor, color: '#fff' }"
                    >
                        <Bot class="size-5" />
                        <span class="font-semibold">{{ client.name }}</span>
                        <Badge
                            v-if="client.status !== 'active'"
                            variant="secondary"
                            class="ml-auto text-xs"
                        >
                            {{ client.status }} — chat may not work
                        </Badge>
                    </div>

                    <!-- Messages -->
                    <div
                        ref="messagesEl"
                        class="flex-1 space-y-4 overflow-y-auto p-4"
                        style="min-height: 400px; max-height: 520px"
                    >
                        <div
                            v-for="(msg, i) in messages"
                            :key="i"
                            class="flex"
                            :class="
                                msg.role === 'user'
                                    ? 'justify-end'
                                    : 'justify-start'
                            "
                        >
                            <div class="max-w-[80%]">
                                <div
                                    class="rounded-2xl px-4 py-2.5 text-sm"
                                    :class="
                                        msg.error
                                            ? 'border border-red-200 bg-red-50 text-red-700 dark:border-red-500/20 dark:bg-red-500/10 dark:text-red-300'
                                            : msg.role === 'user'
                                              ? 'text-white'
                                              : 'bg-muted text-foreground'
                                    "
                                    :style="
                                        msg.role === 'user' && !msg.error
                                            ? { background: accentColor }
                                            : {}
                                    "
                                >
                                    <div
                                        v-if="msg.role === 'assistant'"
                                        class="playground-markdown"
                                        v-html="parseMessage(msg.content)"
                                    ></div>
                                    <p v-else class="whitespace-pre-wrap">
                                        {{ msg.content }}
                                    </p>
                                </div>
                                <div
                                    class="mt-1 flex items-center gap-2 px-1 text-[10px] text-muted-foreground"
                                >
                                    <span>{{ formatTime(msg.timestamp) }}</span>
                                    <Badge
                                        v-if="msg.cached"
                                        variant="secondary"
                                        class="h-4 text-[9px]"
                                        >cached</Badge
                                    >
                                    <span
                                        v-if="msg.debug?.response_time_ms"
                                        class="tabular-nums"
                                    >
                                        {{ msg.debug.response_time_ms }}ms
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Typing indicator -->
                        <div v-if="loading" class="flex justify-start">
                            <div class="rounded-2xl bg-muted px-4 py-3">
                                <div class="flex gap-1">
                                    <span
                                        class="inline-block size-2 animate-bounce rounded-full bg-muted-foreground/40"
                                        style="animation-delay: 0ms"
                                    />
                                    <span
                                        class="inline-block size-2 animate-bounce rounded-full bg-muted-foreground/40"
                                        style="animation-delay: 150ms"
                                    />
                                    <span
                                        class="inline-block size-2 animate-bounce rounded-full bg-muted-foreground/40"
                                        style="animation-delay: 300ms"
                                    />
                                </div>
                            </div>
                        </div>

                        <!-- Empty state -->
                        <div
                            v-if="messages.length === 0"
                            class="flex h-full flex-col items-center justify-center gap-2 py-16 text-center text-muted-foreground"
                        >
                            <Bot class="size-10 opacity-30" />
                            <p class="text-sm">
                                Send a message to test this client's chatbot
                            </p>
                            <p class="text-xs">
                                Responses come from the
                                <strong>{{ client.chat_model }}</strong> model
                                using the knowledge base
                            </p>
                        </div>
                    </div>

                    <!-- Input -->
                    <div
                        class="flex items-center gap-2 border-t border-sidebar-border/70 p-4"
                    >
                        <input
                            v-model="input"
                            type="text"
                            class="flex-1 rounded-lg border border-input bg-transparent px-4 py-2.5 text-sm transition-colors outline-none focus:border-ring focus:ring-2 focus:ring-ring/20"
                            placeholder="Type a message to test..."
                            :disabled="loading"
                            @keydown.enter="send"
                        />
                        <Button
                            :disabled="!input.trim() || loading"
                            :style="{ background: primaryColor }"
                            class="shrink-0"
                            @click="send"
                        >
                            <Send class="size-4" />
                        </Button>
                    </div>
                </Card>

                <!-- Debug sidebar -->
                <div v-if="showDebug" class="space-y-4">
                    <!-- Client config -->
                    <Card class="gap-0 border-sidebar-border/70">
                        <CardHeader
                            class="border-b border-sidebar-border/70 pb-3"
                        >
                            <h3 class="text-sm font-semibold">Client Config</h3>
                        </CardHeader>
                        <CardContent class="space-y-2 pt-4 text-xs">
                            <div class="flex justify-between">
                                <span class="text-muted-foreground"
                                    >Status</span
                                >
                                <Badge
                                    :variant="
                                        client.status === 'active'
                                            ? 'default'
                                            : 'secondary'
                                    "
                                    class="h-5 text-[10px]"
                                >
                                    {{ client.status }}
                                </Badge>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-muted-foreground"
                                    >Chat model</span
                                >
                                <span class="font-mono">{{
                                    client.chat_model
                                }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-muted-foreground"
                                    >Embedding model</span
                                >
                                <span class="font-mono">{{
                                    client.embedding_model
                                }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-muted-foreground"
                                    >Retrieval chunks</span
                                >
                                <span>{{ client.retrieval_chunk_count }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-muted-foreground"
                                    >Semantic cache</span
                                >
                                <span>{{
                                    client.semantic_cache_enabled
                                        ? `ON (${client.cache_ttl_hours}h)`
                                        : 'OFF'
                                }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-muted-foreground"
                                    >Token limit</span
                                >
                                <span>{{
                                    client.monthly_token_limit.toLocaleString()
                                }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-muted-foreground"
                                    >Widget style</span
                                >
                                <span>{{ client.widget_style }}</span>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- System prompt -->
                    <Card class="gap-0 border-sidebar-border/70">
                        <CardHeader
                            class="border-b border-sidebar-border/70 pb-3"
                        >
                            <h3 class="text-sm font-semibold">System Prompt</h3>
                        </CardHeader>
                        <CardContent class="pt-4">
                            <p
                                class="max-h-40 overflow-y-auto text-xs whitespace-pre-wrap text-muted-foreground"
                            >
                                {{
                                    client.system_prompt ||
                                    'No system prompt configured.'
                                }}
                            </p>
                        </CardContent>
                    </Card>

                    <!-- Response log -->
                    <Card class="gap-0 border-sidebar-border/70">
                        <CardHeader
                            class="border-b border-sidebar-border/70 pb-3"
                        >
                            <h3 class="text-sm font-semibold">Response Log</h3>
                        </CardHeader>
                        <CardContent class="max-h-64 overflow-y-auto p-0">
                            <div
                                v-if="
                                    messages.filter((m) => m.debug).length === 0
                                "
                                class="p-4 text-center text-xs text-muted-foreground"
                            >
                                No responses yet.
                            </div>
                            <div
                                v-for="(msg, i) in messages.filter(
                                    (m) => m.debug,
                                )"
                                :key="i"
                                class="border-b border-sidebar-border/50 px-4 py-2.5 text-xs last:border-0"
                            >
                                <div class="flex items-center justify-between">
                                    <span class="font-medium">{{
                                        formatTime(msg.timestamp)
                                    }}</span>
                                    <div class="flex gap-1">
                                        <Badge
                                            v-if="msg.cached"
                                            variant="secondary"
                                            class="h-4 text-[9px]"
                                            >cached</Badge
                                        >
                                        <Badge
                                            v-if="msg.error"
                                            variant="outline"
                                            class="h-4 text-[9px] text-red-500"
                                            >error</Badge
                                        >
                                    </div>
                                </div>
                                <div
                                    class="mt-1 space-y-0.5 text-muted-foreground"
                                >
                                    <p
                                        v-for="(value, key) in msg.debug"
                                        :key="String(key)"
                                    >
                                        <span
                                            class="font-mono text-foreground"
                                            >{{ key }}</span
                                        >:
                                        {{
                                            typeof value === 'object'
                                                ? JSON.stringify(value)
                                                : value
                                        }}
                                    </p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
.playground-markdown :deep(p) {
    margin: 0.25em 0;
}
.playground-markdown :deep(ul),
.playground-markdown :deep(ol) {
    margin: 0.5em 0;
    padding-left: 1.5em;
}
.playground-markdown :deep(li) {
    margin: 0.2em 0;
}
.playground-markdown :deep(strong) {
    font-weight: 600;
}
.playground-markdown :deep(code) {
    font-size: 0.85em;
    background: rgba(0, 0, 0, 0.06);
    padding: 0.15em 0.35em;
    border-radius: 4px;
}
.playground-markdown :deep(a) {
    color: #6366f1;
    text-decoration: underline;
}
</style>
