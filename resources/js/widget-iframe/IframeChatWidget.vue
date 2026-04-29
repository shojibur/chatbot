<template>
    <div
        :class="[
            'davey-iframe-widget',
            `davey-iframe-style-${iframeStyle}`,
            isDarkMode ? 'davey-iframe-dark' : '',
        ]"
    >
        <div class="davey-iframe-panel">
            <div class="davey-iframe-header" :style="{ background: primaryColor }">
                <div class="davey-iframe-header-info">
                    <span class="davey-iframe-header-title">{{ config.name || 'Chat' }}</span>
                    <span class="davey-iframe-header-status">
                        <span class="davey-iframe-header-dot"></span>
                        Online now
                    </span>
                </div>
            </div>

            <div ref="messagesEl" class="davey-iframe-messages">
                <div
                    v-for="(msg, i) in messages"
                    :key="i"
                    :class="['davey-iframe-msg', `davey-iframe-msg-${msg.role}`]"
                >
                    <div
                        class="davey-iframe-msg-bubble"
                        :style="msg.role === 'assistant' ? {} : { background: accentColor, color: '#fff' }"
                    >
                        <div
                            v-if="msg.role === 'assistant'"
                            class="davey-iframe-markdown"
                            v-html="parseMessage(msg.content)"
                        ></div>
                        <div v-else class="davey-iframe-user-text">
                            {{ msg.content }}
                        </div>
                    </div>
                </div>

                <div v-if="loading" class="davey-iframe-msg davey-iframe-msg-assistant">
                    <div class="davey-iframe-msg-bubble davey-iframe-typing">
                        <span></span><span></span><span></span>
                    </div>
                </div>
            </div>

            <div class="davey-iframe-input-area">
                <input
                    v-model="input"
                    type="text"
                    class="davey-iframe-input"
                    :placeholder="inputPlaceholder"
                    :disabled="loading"
                    @keydown.enter="send"
                />
                <button
                    class="davey-iframe-send"
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

            <div
                v-if="config.widget_settings?.show_branding !== false"
                class="davey-iframe-branding"
            >
                Powered By zaochat.com
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import DOMPurify from 'dompurify';
import { marked } from 'marked';
import { computed, nextTick, onMounted, onUnmounted, ref, watch } from 'vue';

interface WidgetConfig {
    name: string;
    widget_style: string;
    widget_settings: {
        primary_color?: string;
        accent_color?: string;
        welcome_message?: string;
        lead_capture_intro_message?: string;
        toggle_text?: string;
        position?: string;
        iframe?: {
            widget_style?: 'classic' | 'modern' | 'glass';
            theme_mode?: 'system' | 'light' | 'dark';
        };
        theme_mode?: 'system' | 'light' | 'dark';
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
    parentPageUrl: string;
}>();

const input = ref('');
const loading = ref(false);
const messagesEl = ref<HTMLElement | null>(null);
const isDarkMode = ref(false);
let darkModeMedia: MediaQueryList | null = null;

const storagePrefix = `davey_iframe_${props.clientCode}`;
const messagesStorageKey = `${storagePrefix}_messages`;
const sessionStorageKey = `${storagePrefix}_session_token`;

const messages = ref<Message[]>(readMessages());
const sessionToken = ref<string | null>(readStorage(sessionStorageKey));

const primaryColor = computed(
    () => props.config.widget_settings?.primary_color || '#1f2937',
);
const accentColor = computed(
    () => props.config.widget_settings?.accent_color || '#0f766e',
);
const iframeStyle = computed<'classic' | 'modern' | 'glass'>(() => {
    const style = props.config.widget_settings?.iframe?.widget_style;

    return style === 'modern' || style === 'glass' ? style : 'classic';
});
const themeMode = computed<'system' | 'light' | 'dark'>(() => {
    const mode =
        props.config.widget_settings?.iframe?.theme_mode ??
        props.config.widget_settings?.theme_mode;

    return mode === 'dark' || mode === 'light' ? mode : 'system';
});
const inputPlaceholder = computed(() => 'Type a message...');

onMounted(() => {
    if (typeof window !== 'undefined' && typeof window.matchMedia === 'function') {
        darkModeMedia = window.matchMedia('(prefers-color-scheme: dark)');
        if (typeof darkModeMedia.addEventListener === 'function') {
            darkModeMedia.addEventListener('change', syncDarkModePreference);
        } else {
            darkModeMedia.addListener(syncDarkModePreference);
        }
    }

    syncDarkModePreference();

    const welcome =
        props.config.welcome_message ||
        props.config.widget_settings?.welcome_message;

    if (welcome && messages.value.length === 0) {
        messages.value.push({ role: 'assistant', content: welcome });
        saveMessages();
    }
});

onUnmounted(() => {
    if (!darkModeMedia) {
        return;
    }

    if (typeof darkModeMedia.removeEventListener === 'function') {
        darkModeMedia.removeEventListener('change', syncDarkModePreference);
    } else {
        darkModeMedia.removeListener(syncDarkModePreference);
    }
});

watch(themeMode, syncDarkModePreference);

marked.setOptions({
    breaks: true,
    gfm: true,
});

function readStorage(key: string): string | null {
    try {
        return sessionStorage.getItem(key);
    } catch {
        return null;
    }
}

function writeStorage(key: string, value: string): void {
    try {
        sessionStorage.setItem(key, value);
    } catch {
        // no-op when storage is blocked
    }
}

function readMessages(): Message[] {
    const raw = readStorage(messagesStorageKey);
    if (!raw) return [];

    try {
        const parsed = JSON.parse(raw);
        return Array.isArray(parsed) ? parsed : [];
    } catch {
        return [];
    }
}

function saveMessages() {
    writeStorage(messagesStorageKey, JSON.stringify(messages.value));
}

function parseMessage(text: string) {
    if (!text) return '';

    const rawHtml = marked.parse(text) as string;

    return DOMPurify.sanitize(rawHtml, {
        ALLOWED_TAGS: [
            'b', 'i', 'em', 'strong', 'a', 'p', 'br',
            'ul', 'ol', 'li', 'code', 'pre', 'blockquote',
            'h1', 'h2', 'h3', 'h4', 'h5', 'h6',
            'hr', 'table', 'thead', 'tbody', 'tr', 'th', 'td',
        ],
    });
}

function syncDarkModePreference() {
    if (themeMode.value === 'dark') {
        isDarkMode.value = true;
        return;
    }

    if (themeMode.value === 'light') {
        isDarkMode.value = false;
        return;
    }

    isDarkMode.value = !!darkModeMedia?.matches;
}

function addBotMessage(content: string) {
    messages.value.push({ role: 'assistant', content });
    saveMessages();
    scrollToBottom();
}

async function send() {
    const text = input.value.trim();
    if (!text || loading.value) return;

    input.value = '';

    messages.value.push({ role: 'user', content: text });
    saveMessages();
    loading.value = true;
    scrollToBottom();

    try {
        const body: Record<string, string> = {
            client_code: props.clientCode,
            message: text,
            page_url: props.parentPageUrl || window.location.href,
        };

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

        if (res.ok) {
            if (data.answer) {
                messages.value.push({ role: 'assistant', content: data.answer });
                saveMessages();
            }

            if (data.session_token) {
                sessionToken.value = data.session_token;
                writeStorage(sessionStorageKey, data.session_token);
            }

        } else {
            messages.value.push({
                role: 'assistant',
                content: data.error || 'Something went wrong. Please try again.',
            });
            saveMessages();
        }
    } catch {
        messages.value.push({
            role: 'assistant',
            content: 'Unable to connect. Please check your internet connection.',
        });
        saveMessages();
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

<style scoped>
.davey-iframe-widget {
    width: 100%;
    height: 100%;
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica,
        Arial, sans-serif;
    color: #111827;
    background: #ffffff;
}

.davey-iframe-panel {
    width: 100%;
    height: 100%;
    display: flex;
    flex-direction: column;
    border: 0;
    overflow: hidden;
    background: #ffffff;
}

.davey-iframe-style-modern .davey-iframe-panel {
    border: 0;
    background: #f8fafc;
    box-shadow: 0 12px 30px rgba(15, 23, 42, 0.12);
}

.davey-iframe-style-modern .davey-iframe-messages {
    background: #eef2f7;
}

.davey-iframe-style-modern .davey-iframe-msg-bubble {
    border-radius: 16px;
    border-color: #dbe3ee;
    background: #ffffff;
}

.davey-iframe-style-glass .davey-iframe-panel {
    background: rgba(255, 255, 255, 0.72);
    border: 0;
    box-shadow: 0 10px 32px rgba(2, 6, 23, 0.16);
    backdrop-filter: blur(14px);
    -webkit-backdrop-filter: blur(14px);
}

.davey-iframe-style-glass .davey-iframe-messages {
    background: rgba(248, 250, 252, 0.65);
}

.davey-iframe-style-glass .davey-iframe-msg-bubble {
    background: rgba(255, 255, 255, 0.68);
    border-color: rgba(203, 213, 225, 0.8);
}

.davey-iframe-style-glass .davey-iframe-input-area {
    background: rgba(255, 255, 255, 0.78);
}

.davey-iframe-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 14px 16px;
    color: #ffffff;
    flex-shrink: 0;
}

.davey-iframe-header-info {
    display: flex;
    flex-direction: column;
    gap: 2px;
}

.davey-iframe-header-title {
    font-size: 14px;
    font-weight: 700;
}

.davey-iframe-header-status {
    font-size: 12px;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    opacity: 0.92;
}

.davey-iframe-header-dot {
    width: 8px;
    height: 8px;
    border-radius: 999px;
    background: #22c55e;
}

.davey-iframe-messages {
    flex: 1;
    overflow-y: auto;
    padding: 14px;
    background: #f9fafb;
}

.davey-iframe-msg {
    display: flex;
    margin-bottom: 10px;
}

.davey-iframe-msg-user {
    justify-content: flex-end;
}

.davey-iframe-msg-assistant {
    justify-content: flex-start;
}

.davey-iframe-msg-bubble {
    max-width: 85%;
    padding: 10px 12px;
    border-radius: 12px;
    font-size: 14px;
    line-height: 1.45;
    background: #ffffff;
    border: 1px solid #e5e7eb;
    white-space: normal;
    word-wrap: break-word;
}

.davey-iframe-user-text {
    white-space: pre-wrap;
}

.davey-iframe-input-area {
    display: flex;
    gap: 8px;
    padding: 10px;
    border-top: 1px solid #e5e7eb;
    background: #ffffff;
    flex-shrink: 0;
}

.davey-iframe-input {
    flex: 1;
    border: 1px solid #d1d5db;
    border-radius: 10px;
    padding: 10px 12px;
    font-size: 14px;
    outline: none;
}

.davey-iframe-input:focus {
    border-color: #9ca3af;
}

.davey-iframe-send {
    width: 40px;
    height: 40px;
    border: 0;
    border-radius: 10px;
    color: #ffffff;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.davey-iframe-send:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.davey-iframe-branding {
    padding: 6px 10px 8px;
    text-align: center;
    font-size: 11px;
    color: #6b7280;
    border-top: 1px solid #f1f5f9;
    background: #ffffff;
}

.davey-iframe-typing span {
    display: inline-block;
    width: 6px;
    height: 6px;
    margin: 0 2px;
    border-radius: 999px;
    background: #9ca3af;
    animation: davey-iframe-dot 1s infinite ease-in-out;
}

.davey-iframe-typing span:nth-child(2) {
    animation-delay: 0.15s;
}

.davey-iframe-typing span:nth-child(3) {
    animation-delay: 0.3s;
}

@keyframes davey-iframe-dot {
    0%,
    80%,
    100% {
        opacity: 0.35;
        transform: translateY(0);
    }
    40% {
        opacity: 1;
        transform: translateY(-2px);
    }
}

.davey-iframe-markdown {
    font-size: inherit;
    line-height: 1.5;
}

.davey-iframe-markdown p {
    margin: 0 0 10px;
}

.davey-iframe-markdown p:last-child {
    margin-bottom: 0;
}

.davey-iframe-markdown ul {
    margin: 0 0 10px;
    padding-left: 20px;
    list-style-type: disc;
}

.davey-iframe-markdown ol {
    margin: 0 0 10px;
    padding-left: 20px;
    list-style-type: decimal;
}

.davey-iframe-markdown li {
    margin-bottom: 4px;
}

.davey-iframe-markdown a {
    text-decoration: underline;
}

.davey-iframe-markdown code {
    font-family: inherit;
    font-size: 0.9em;
    background: rgba(0, 0, 0, 0.06);
    padding: 2px 4px;
    border-radius: 4px;
}

.davey-iframe-markdown pre {
    background: rgba(0, 0, 0, 0.06);
    padding: 8px;
    border-radius: 6px;
    overflow-x: auto;
    margin: 8px 0;
}

.davey-iframe-markdown pre code {
    background: transparent;
    padding: 0;
}

.davey-iframe-dark {
    color: #f8fafc;
    background: #0f172a;
}

.davey-iframe-dark .davey-iframe-panel {
    background: #0f172a;
    border: 0;
    box-shadow: 0 12px 36px rgba(2, 6, 23, 0.55);
}

.davey-iframe-dark .davey-iframe-messages {
    background: #0b1220;
}

.davey-iframe-dark .davey-iframe-msg-bubble {
    background: #1e293b;
    border-color: #334155;
    color: #f8fafc;
}

.davey-iframe-dark .davey-iframe-msg-user .davey-iframe-msg-bubble {
    color: #fff;
    border-color: transparent;
}

.davey-iframe-dark .davey-iframe-input-area {
    background: #0f172a;
    border-top-color: #1f2937;
}

.davey-iframe-dark .davey-iframe-input {
    background: #111827;
    border-color: #334155;
    color: #f8fafc;
}

.davey-iframe-dark .davey-iframe-input::placeholder {
    color: #94a3b8;
}

.davey-iframe-dark .davey-iframe-input:focus {
    border-color: #64748b;
}

.davey-iframe-dark .davey-iframe-branding {
    border-top-color: #1f2937;
    color: #94a3b8;
    background: #0f172a;
}

.davey-iframe-dark .davey-iframe-markdown code,
.davey-iframe-dark .davey-iframe-markdown pre {
    background: rgba(148, 163, 184, 0.15);
}

.davey-iframe-dark .davey-iframe-markdown,
.davey-iframe-dark .davey-iframe-markdown a {
    color: #f8fafc;
}

.davey-iframe-dark .davey-iframe-markdown a {
    text-decoration-color: rgba(248, 250, 252, 0.65);
}

.davey-iframe-dark .davey-iframe-typing span {
    background: #cbd5e1;
}

.davey-iframe-dark.davey-iframe-style-modern .davey-iframe-panel {
    background: #0b1220;
    border: 0;
}

.davey-iframe-dark.davey-iframe-style-modern .davey-iframe-messages {
    background: #020817;
}

.davey-iframe-dark.davey-iframe-style-glass .davey-iframe-panel {
    background: rgba(15, 23, 42, 0.8);
    border: 0;
    backdrop-filter: blur(14px);
    -webkit-backdrop-filter: blur(14px);
}

.davey-iframe-dark.davey-iframe-style-glass .davey-iframe-messages {
    background: rgba(2, 6, 23, 0.75);
}

.davey-iframe-dark.davey-iframe-style-glass .davey-iframe-msg-bubble {
    background: rgba(30, 41, 59, 0.88);
    border-color: #334155;
}

.davey-iframe-dark.davey-iframe-style-glass .davey-iframe-input-area {
    background: rgba(15, 23, 42, 0.82);
}
</style>
