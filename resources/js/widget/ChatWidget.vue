<template>
    <div
        :class="[
            'davey-widget',
            `davey-${config.widget_style}`,
            isOpen && isExpanded ? 'davey-expanded' : '',
            isDarkMode ? 'davey-dark' : '',
        ]"
        :style="widgetStyle"
    >
        <!-- Modern Style Pill -->
        <button
            v-if="!isOpen && config.widget_style === 'modern'"
            class="davey-toggle-pill"
            @click="toggleOpen()"
        >
            <div class="davey-pill-dot" :style="{ background: '#22c55e' }"></div>
            <span class="davey-pill-text">{{ config.widget_settings?.toggle_text || 'Ask anything about this business' }}</span>
        </button>

        <!-- Classic & Glass Toggle Button -->
        <div v-else-if="!isOpen" class="davey-toggle-wrapper">
            <span class="davey-online-dot"></span>
            <button
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
        </div>

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
                <div class="davey-header-info">
                    <span class="davey-header-title">{{ config.name || 'Chat' }}</span>
                    <span class="davey-header-status">
                        <span class="davey-header-dot"></span>
                        Online now
                    </span>
                </div>
                <div class="davey-header-actions">
                    <button
                        class="davey-header-action"
                        :aria-label="isExpanded ? 'Shrink chat widget' : 'Expand chat widget'"
                        :title="isExpanded ? 'Shrink' : 'Expand'"
                        @click="toggleExpanded()"
                    >
                        <svg
                            v-if="!isExpanded"
                            width="18"
                            height="18"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                        >
                            <polyline points="15 3 21 3 21 9" />
                            <polyline points="9 21 3 21 3 15" />
                            <line x1="21" y1="3" x2="14" y2="10" />
                            <line x1="3" y1="21" x2="10" y2="14" />
                        </svg>
                        <svg
                            v-else
                            width="18"
                            height="18"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                        >
                            <polyline points="14 10 21 3" />
                            <polyline points="3 21 10 14" />
                            <polyline points="21 9 21 3 15 3" />
                            <polyline points="3 15 3 21 9 21" />
                        </svg>
                    </button>
                    <button class="davey-close" aria-label="Close chat widget" @click="toggleOpen()">
                        &times;
                    </button>
                </div>
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
                                    ? leadMessageStyle
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
import { ref, nextTick, onMounted, onUnmounted, computed, watch } from 'vue';

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
        theme_mode?: 'system' | 'light' | 'dark';
        show_branding?: boolean;
        default_expanded?: boolean;
    };
    welcome_message: string;
}

interface Message {
    role: 'user' | 'assistant';
    content: string;
    isLead?: boolean; // marks lead-capture bot prompts for special styling
}

// Lead capture state
type LeadStep = null | 'ask_name' | 'ask_contact' | 'done';

const props = defineProps<{
    clientCode: string;
    apiBase: string;
    config: WidgetConfig;
}>();

const expandedStorageKey = `davey_is_expanded_${props.clientCode}`;
const isOpen = ref(sessionStorage.getItem('davey_is_open') === 'true');
const isExpanded = ref(
    sessionStorage.getItem(expandedStorageKey) === null
        ? props.config.widget_settings?.default_expanded !== false
        : sessionStorage.getItem(expandedStorageKey) === 'true',
);
const input = ref('');
const loading = ref(false);
const messagesEl = ref<HTMLElement | null>(null);

const storedMessages = sessionStorage.getItem('davey_messages');
const messages = ref<Message[]>(storedMessages ? JSON.parse(storedMessages) : []);

const sessionToken = ref<string | null>(sessionStorage.getItem('davey_session_token'));

// --- Lead Capture State ---
const leadStep = ref<LeadStep>(null);
const leadData = ref({
    name: '',
    contact: '',
    notes: '',
    triggerMessage: '',
    trigger: 'ai',
});
const leadCapturedThisSession = ref(sessionStorage.getItem('davey_lead_captured') === 'true');
const isDarkMode = ref(false);
let darkModeMedia: MediaQueryList | null = null;
const defaultLeadCaptureIntroMessage =
    'I can help with that! May I get your **name** first so our team can follow up with you?';

const primaryColor = computed(
    () => props.config.widget_settings?.primary_color || '#6366f1',
);
const accentColor = computed(
    () => props.config.widget_settings?.accent_color || '#8b5cf6',
);
const themeMode = computed<'system' | 'light' | 'dark'>(() => {
    const mode = props.config.widget_settings?.theme_mode;

    return mode === 'dark' || mode === 'light' ? mode : 'system';
});
const leadCaptureIntroMessage = computed(
    () => props.config.widget_settings?.lead_capture_intro_message || defaultLeadCaptureIntroMessage,
);
const leadMessageStyle = computed(() =>
    isDarkMode.value
        ? {
            background:
                'linear-gradient(135deg, rgba(30,41,59,0.98) 0%, rgba(15,23,42,0.98) 100%)',
            border: '1px solid #334155',
            color: '#f8fafc',
        }
        : {
            background:
                'linear-gradient(135deg, #f0f7ff 0%, #e8f4fd 100%)',
            border: '1px solid #bdd8f5',
            color: '#1a3a5c',
        },
);

const positionStyle = computed(() => {
    const pos = props.config.widget_settings?.position || 'right';

    return pos === 'left'
        ? { left: '20px', right: 'auto' }
        : { right: '20px', left: 'auto' };
});

const expandedStyle = computed(() => ({
    top: '32px',
    right: '32px',
    bottom: '32px',
    left: '32px',
}));

const widgetStyle = computed(() => (
    isOpen.value && isExpanded.value
        ? expandedStyle.value
        : positionStyle.value
));

const inputPlaceholder = computed(() => {
    if (leadStep.value === 'ask_name') {
        return 'Enter your name…';
    }

    if (leadStep.value === 'ask_contact') {
        return 'Phone number or email…';
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

function toggleExpanded() {
    isExpanded.value = !isExpanded.value;
    sessionStorage.setItem(expandedStorageKey, String(isExpanded.value));
    scrollToBottom();
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

function saveMessages() {
    sessionStorage.setItem('davey_messages', JSON.stringify(messages.value));
}

function addBotMessage(content: string, isLead = false) {
    messages.value.push({ role: 'assistant', content, isLead });
    saveMessages();
    scrollToBottom();
}

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
function userIsRefusing(text: string): boolean {
    const lower = text.toLowerCase().trim();
    const refusalPatterns = [
        'no', 'nah', 'nope', 'not', "don't", 'dont', 'do not',
        'i refuse', 'skip', 'pass', 'never mind', 'nevermind',
        'forget it', 'stop', 'cancel', "i'd rather not", 'i rather not',
        'no thanks', 'no thank', 'not interested', 'leave me alone',
        'i don\'t want', 'i do not want', 'i dont want',
        'prefer not', 'rather not', 'not now',
    ];
    return refusalPatterns.some((p) => lower.includes(p));
}

function cancelLeadCapture() {
    leadStep.value = null;
    leadData.value = {
        name: '',
        contact: '',
        notes: '',
        triggerMessage: '',
        trigger: 'ai',
    };
    addBotMessage(
        `No problem at all! Feel free to continue chatting — I'm here if you need anything.`,
        true,
    );
}

async function finalizeLeadCapture() {
    leadStep.value = 'done';
    loading.value = true;
    scrollToBottom();

    try {
        await saveLead();
        leadCapturedThisSession.value = true;
        sessionStorage.setItem('davey_lead_captured', 'true');
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
        setTimeout(() => {
            leadStep.value = null;
            leadData.value = {
                name: '',
                contact: '',
                notes: '',
                triggerMessage: '',
                trigger: 'ai',
            };
        }, 4000);
    }
}

async function handleLeadStep(text: string): Promise<boolean> {
    if (!leadStep.value || leadStep.value === 'done') {
        return false;
    }

    messages.value.push({ role: 'user', content: text });
    saveMessages();
    scrollToBottom();

    // If the user refuses at any step, gracefully exit the lead capture flow
    if (userIsRefusing(text)) {
        cancelLeadCapture();
        return true;
    }

    const res = await fetch(`${props.apiBase}/api/v1/leads/process`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            Accept: 'application/json',
        },
        body: JSON.stringify({
            client_code: props.clientCode,
            step: leadStep.value,
            message: text,
            lead_data: {
                name: leadData.value.name,
                contact: leadData.value.contact,
            },
        }),
    });

    const data = await res.json();

    if (!res.ok) {
        addBotMessage(
            'Sorry, I had trouble processing that. Please try again.',
            true,
        );
        return true;
    }

    leadData.value.name = data.name ?? leadData.value.name;
    leadData.value.contact = data.contact ?? leadData.value.contact;

    if (data.cancel_capture) {
        leadStep.value = null;
        addBotMessage(
            data.assistant_message || 'No problem at all. Feel free to keep chatting if you need anything else.',
            true,
        );
        return true;
    }

    if (data.next_step === 'done') {
        if (data.assistant_message) {
            addBotMessage(data.assistant_message, true);
        }
        await finalizeLeadCapture();
        return true;
    }

    leadStep.value = data.next_step ?? leadStep.value;
    addBotMessage(
        data.assistant_message || 'Could you share that one more time?',
        true,
    );

    return false;
}

async function saveLead() {
    const body: Record<string, string> = {
        client_code:  props.clientCode,
        name:         leadData.value.name,
        contact:      leadData.value.contact,
        user_request: leadData.value.triggerMessage,
        notes:        leadData.value.notes,
        trigger:      leadData.value.trigger,
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

            // Trigger lead capture flow if backend signals it (only once per session)
            if (data.lead_capture && !leadStep.value && !leadCapturedThisSession.value) {
                leadData.value.triggerMessage = text;
                leadData.value.trigger = data.lead_trigger ?? 'ai';
                leadStep.value = 'ask_name';

                setTimeout(() => {
                    addBotMessage(
                        data.lead_capture_prompt || leadCaptureIntroMessage.value,
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
