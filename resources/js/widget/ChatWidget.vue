<template>
    <div
        :class="['davey-widget', `davey-${config.widget_style}`]"
        :style="positionStyle"
    >
        <!-- Toggle Button -->
        <button
            v-if="!isOpen"
            class="davey-toggle"
            :style="{ background: primaryColor }"
            @click="isOpen = true"
        >
            <svg
                width="24"
                height="24"
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-width="2"
            >
                <path
                    d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"
                />
            </svg>
        </button>

        <!-- Chat Panel -->
        <div v-if="isOpen" class="davey-panel">
            <!-- Header -->
            <div class="davey-header" :style="{ background: primaryColor }">
                <span class="davey-header-title">{{
                    config.name || 'Chat'
                }}</span>
                <button class="davey-close" @click="isOpen = false">
                    &times;
                </button>
            </div>

            <!-- Messages -->
            <div ref="messagesEl" class="davey-messages">
                <div
                    v-for="(msg, i) in messages"
                    :key="i"
                    :class="['davey-msg', `davey-msg-${msg.role}`]"
                >
                    <div
                        class="davey-msg-bubble"
                        :style="
                            msg.role === 'assistant'
                                ? {}
                                : { background: accentColor, color: '#fff' }
                        "
                    >
                        <div
                            v-if="msg.role === 'assistant'"
                            class="davey-markdown"
                            v-html="parseMessage(msg.content)"
                        ></div>
                        <div v-else style="white-space: pre-wrap">
                            {{ msg.content }}
                        </div>
                    </div>
                </div>
                <div v-if="loading" class="davey-msg davey-msg-assistant">
                    <div class="davey-msg-bubble davey-typing">
                        <span></span><span></span><span></span>
                    </div>
                </div>
            </div>

            <!-- Input -->
            <div class="davey-input-area">
                <input
                    v-model="input"
                    type="text"
                    class="davey-input"
                    placeholder="Type a message..."
                    :disabled="loading"
                    @keydown.enter="send"
                />
                <button
                    class="davey-send"
                    :style="{ background: primaryColor }"
                    :disabled="!input.trim() || loading"
                    @click="send"
                >
                    <svg
                        width="18"
                        height="18"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                    >
                        <line x1="22" y1="2" x2="11" y2="13" />
                        <polygon points="22 2 15 22 11 13 2 9 22 2" />
                    </svg>
                </button>
            </div>

            <!-- Branding -->
            <div
                v-if="config.widget_settings?.show_branding !== false"
                class="davey-branding"
            >
                Powered by Davey
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import DOMPurify from 'dompurify';
import { marked } from 'marked';
import { ref, nextTick, onMounted, computed } from 'vue';

interface WidgetConfig {
    name: string;
    widget_style: string;
    widget_settings: {
        primary_color?: string;
        accent_color?: string;
        welcome_message?: string;
        position?: string;
        show_branding?: boolean;
    };
    welcome_message: string;
}

interface Message {
    role: 'user' | 'assistant';
    content: string;
}

const props = defineProps<{
    clientCode: string;
    apiBase: string;
    config: WidgetConfig;
}>();

const isOpen = ref(false);
const input = ref('');
const loading = ref(false);
const messagesEl = ref<HTMLElement | null>(null);
const messages = ref<Message[]>([]);
const sessionToken = ref<string | null>(null);

const primaryColor = computed(
    () => props.config.widget_settings?.primary_color || '#6366f1',
);
const accentColor = computed(
    () => props.config.widget_settings?.accent_color || '#8b5cf6',
);

const positionStyle = computed(() => {
    const pos = props.config.widget_settings?.position || 'right';

    return pos === 'left'
        ? { left: '20px', right: 'auto' }
        : { right: '20px', left: 'auto' };
});

onMounted(() => {
    const welcome =
        props.config.welcome_message ||
        props.config.widget_settings?.welcome_message;

    if (welcome) {
        messages.value.push({ role: 'assistant', content: welcome });
    }
});

marked.setOptions({
    breaks: true,
    gfm: true,
});

function parseMessage(text: string) {
    if (!text) {
        return '';
    }

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

async function send() {
    const text = input.value.trim();

    if (!text || loading.value) {
        return;
    }

    messages.value.push({ role: 'user', content: text });
    input.value = '';
    loading.value = true;
    scrollToBottom();

    try {
        const body: Record<string, string> = {
            client_code: props.clientCode,
            message: text,
            page_url: window.location.href,
        };

        // Send session_token on subsequent messages so they group into the same session
        if (sessionToken.value) {
            body.session_token = sessionToken.value;
        }

        const res = await fetch(`${props.apiBase}/api/v1/chat`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
            },
            body: JSON.stringify(body),
        });

        const data = await res.json();

        if (res.ok && data.answer) {
            messages.value.push({ role: 'assistant', content: data.answer });

            // Store session_token from server so all messages in this conversation are linked
            if (data.session_token) {
                sessionToken.value = data.session_token;
            }
        } else {
            messages.value.push({
                role: 'assistant',
                content:
                    data.error ||
                    'Sorry, something went wrong. Please try again.',
            });
        }
    } catch {
        messages.value.push({
            role: 'assistant',
            content:
                'Unable to connect. Please check your internet connection.',
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
</script>
