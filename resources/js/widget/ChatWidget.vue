<template>
    <div
        :class="['davey-widget', `davey-${config.widget_style}`]"
        :style="positionStyle"
    >
        <!-- Modern Style Pill -->
        <button
            v-if="!isOpen && config.widget_style === 'modern'"
            class="davey-toggle-pill"
            @click="toggleOpen()"
        >
            <div class="davey-pill-dot" :style="{ background: accentColor }"></div>
            <span class="davey-pill-text">{{ config.widget_settings?.toggle_text || 'Ask anything about this business' }}</span>
        </button>

        <!-- Classic & Glass Toggle Button -->
        <button
            v-else-if="!isOpen"
            :class="['davey-toggle', config.widget_style === 'glass' ? 'davey-glass-toggle' : '']"
            :style="config.widget_style === 'glass' 
                ? { 
                    background: primaryColor + 'cc', 
                    backdropFilter: 'blur(12px)',
                    WebkitBackdropFilter: 'blur(12px)',
                    border: '1px solid rgba(255,255,255,0.4)',
                    boxShadow: '0 8px 32px rgba(0, 0, 0, 0.15)'
                  }
                : { background: primaryColor }"
            @click="toggleOpen()"
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
            <div class="davey-header" :style="config.widget_style === 'glass'
                ? { 
                    background: primaryColor + '99', 
                    backdropFilter: 'blur(12px)',
                    WebkitBackdropFilter: 'blur(12px)',
                    borderBottom: '1px solid rgba(255,255,255,0.2)'
                  }
                : { background: primaryColor }">
                <span class="davey-header-title">{{
                    config.name || 'Chat'
                }}</span>
                <button class="davey-close" @click="toggleOpen()">
                    &times;
                </button>
            </div>

            <!-- Messages -->
            <div ref="messagesEl" class="davey-messages">
                <div
                    v-for="(msg, i) in messages"
                    :key="i"
                    :class="['davey-msg', `davey-msg-${msg.role}`, msg.isLead ? 'davey-msg-lead' : '']"
                >
                    <div
                        class="davey-msg-bubble"
                        :style="
                            msg.role === 'assistant'
                                ? msg.isLead
                                    ? { background: 'linear-gradient(135deg, #f0f7ff 0%, #e8f4fd 100%)', border: '1px solid #bdd8f5', color: '#1a3a5c' }
                                    : {}
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
                    :placeholder="inputPlaceholder"
                    :disabled="loading || leadStep === 'done'"
                    @keydown.enter="send"
                />
                <button
                    class="davey-send"
                    :style="{ background: primaryColor }"
                    :disabled="!input.trim() || loading || leadStep === 'done'"
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
                Powered By zaochat.com
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
        toggle_text?: string;
        position?: string;
        show_branding?: boolean;
    };
    welcome_message: string;
}

interface Message {
    role: 'user' | 'assistant';
    content: string;
    isLead?: boolean; // marks lead-capture bot prompts for special styling
}

// Lead capture state
type LeadStep = null | 'ask_name' | 'ask_contact' | 'ask_notes' | 'done';

const props = defineProps<{
    clientCode: string;
    apiBase: string;
    config: WidgetConfig;
}>();

const isOpen = ref(sessionStorage.getItem('davey_is_open') === 'true');
const input = ref('');
const loading = ref(false);
const messagesEl = ref<HTMLElement | null>(null);

const storedMessages = sessionStorage.getItem('davey_messages');
const messages = ref<Message[]>(storedMessages ? JSON.parse(storedMessages) : []);

const sessionToken = ref<string | null>(sessionStorage.getItem('davey_session_token'));

// --- Lead Capture State ---
const leadStep = ref<LeadStep>(null);
const leadData = ref({ name: '', contact: '', notes: '', triggerMessage: '' });

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

const inputPlaceholder = computed(() => {
    if (leadStep.value === 'ask_name') {
        return 'Enter your name…';
    }

    if (leadStep.value === 'ask_contact') {
        return 'Phone number or email…';
    }

    if (leadStep.value === 'ask_notes') {
        return 'Briefly describe what you need…';
    }

    if (leadStep.value === 'done') {
        return 'Thank you! ✅';
    }

    return 'Type a message…';
});

function toggleOpen() {
    isOpen.value = !isOpen.value;
    sessionStorage.setItem('davey_is_open', String(isOpen.value));
}

function saveMessages() {
    sessionStorage.setItem('davey_messages', JSON.stringify(messages.value));
}

function addBotMessage(content: string, isLead = false) {
    messages.value.push({ role: 'assistant', content, isLead });
    saveMessages();
    scrollToBottom();
}

onMounted(() => {
    const welcome =
        props.config.welcome_message ||
        props.config.widget_settings?.welcome_message;

    if (welcome && messages.value.length === 0) {
        messages.value.push({ role: 'assistant', content: welcome });
        saveMessages();
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
            'b', 'i', 'em', 'strong', 'a', 'p', 'br',
            'ul', 'ol', 'li', 'code', 'pre', 'blockquote',
            'h1', 'h2', 'h3', 'h4', 'h5', 'h6',
            'hr', 'table', 'thead', 'tbody', 'tr', 'th', 'td',
        ],
    });
}

// -------------------------------------------------------------------
// Lead capture flow — intercepts input when leadStep is active
// -------------------------------------------------------------------
async function handleLeadStep(text: string): Promise<boolean> {
    if (!leadStep.value || leadStep.value === 'done') {
        return false;
    }

    messages.value.push({ role: 'user', content: text });
    saveMessages();
    scrollToBottom();

    if (leadStep.value === 'ask_name') {
        leadData.value.name = text;
        leadStep.value = 'ask_contact';
        addBotMessage(
            `Thanks ${leadData.value.name}! 📱 What's the best phone number or email address to reach you?`,
            true,
        );

        return true;
    }

    if (leadStep.value === 'ask_contact') {
        leadData.value.contact = text;
        leadStep.value = 'ask_notes';
        addBotMessage(
            `Got it! One last thing — can you briefly describe what you need help with?`,
            true,
        );

        return true;
    }

    if (leadStep.value === 'ask_notes') {
        leadData.value.notes = text;
        leadStep.value = 'done';
        loading.value = true;
        scrollToBottom();

        try {
            await saveLead();
            addBotMessage(
                `✅ **Thank you!** Our team will contact you soon.`,
                true,
            );
        } catch {
            addBotMessage(
                `Sorry, there was a problem saving your details. Please try again or contact us directly.`,
                true,
            );
        } finally {
            loading.value = false;
            // Resume normal chat after 4s
            setTimeout(() => {
                leadStep.value = null;
                leadData.value = { name: '', contact: '', notes: '', triggerMessage: '' };
            }, 4000);
        }

        return true;
    }

    return false;
}

async function saveLead() {
    const body: Record<string, string> = {
        client_code:  props.clientCode,
        name:         leadData.value.name,
        contact:      leadData.value.contact,
        user_request: leadData.value.triggerMessage,
        notes:        leadData.value.notes,
        trigger:      'intent',
    };

    if (sessionToken.value) {
        body.session_token = sessionToken.value;
    }

    const res = await fetch(`${props.apiBase}/api/v1/leads`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            Accept: 'application/json',
        },
        body: JSON.stringify(body),
    });

    if (!res.ok) {
        throw new Error('Lead save failed');
    }
}

// -------------------------------------------------------------------
// Main send handler
// -------------------------------------------------------------------
async function send() {
    const text = input.value.trim();

    if (!text || loading.value) {
        return;
    }

    input.value = '';

    // If we're in a lead capture step, handle it directly
    if (leadStep.value && leadStep.value !== 'done') {
        await handleLeadStep(text);

        return;
    }

    messages.value.push({ role: 'user', content: text });
    saveMessages();
    loading.value = true;
    scrollToBottom();

    try {
        const body: Record<string, string> = {
            client_code: props.clientCode,
            message: text,
            page_url: window.location.href,
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

        if (res.ok && data.answer) {
            messages.value.push({ role: 'assistant', content: data.answer });
            saveMessages();

            // Store session token
            if (data.session_token) {
                sessionToken.value = data.session_token;
                sessionStorage.setItem('davey_session_token', data.session_token);
            }

            // Trigger lead capture flow if backend signals it
            if (data.lead_capture && !leadStep.value) {
                leadData.value.triggerMessage = text;
                leadStep.value = 'ask_name';

                setTimeout(() => {
                    addBotMessage(
                        `I can help with that! 😊 May I get your **name** first so our team can follow up with you?`,
                        true,
                    );
                }, 600);
            }
        } else {
            messages.value.push({
                role: 'assistant',
                content: data.error || 'Sorry, something went wrong. Please try again.',
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
