<template>
  <div :class="['davey-widget', `davey-${config.widget_style}`]" :style="positionStyle">
    <!-- Toggle Button -->
    <button
      v-if="!isOpen"
      class="davey-toggle"
      :style="{ background: primaryColor }"
      @click="isOpen = true"
    >
      <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z" />
      </svg>
    </button>

    <!-- Chat Panel -->
    <div v-if="isOpen" class="davey-panel">
      <!-- Header -->
      <div class="davey-header" :style="{ background: primaryColor }">
        <span class="davey-header-title">{{ config.name || 'Chat' }}</span>
        <button class="davey-close" @click="isOpen = false">&times;</button>
      </div>

      <!-- Messages -->
      <div ref="messagesEl" class="davey-messages">
        <div
          v-for="(msg, i) in messages"
          :key="i"
          :class="['davey-msg', `davey-msg-${msg.role}`]"
        >
          <div class="davey-msg-bubble" :style="msg.role === 'assistant' ? {} : { background: accentColor, color: '#fff' }">
            {{ msg.content }}
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
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <line x1="22" y1="2" x2="11" y2="13" />
            <polygon points="22 2 15 22 11 13 2 9 22 2" />
          </svg>
        </button>
      </div>

      <!-- Branding -->
      <div v-if="config.widget_settings?.show_branding !== false" class="davey-branding">
        Powered by Davey
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
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
const sessionId = ref(crypto.randomUUID());

const primaryColor = computed(() => props.config.widget_settings?.primary_color || '#6366f1');
const accentColor = computed(() => props.config.widget_settings?.accent_color || '#8b5cf6');

const positionStyle = computed(() => {
  const pos = props.config.widget_settings?.position || 'right';
  return pos === 'left' ? { left: '20px', right: 'auto' } : { right: '20px', left: 'auto' };
});

onMounted(() => {
  const welcome = props.config.welcome_message || props.config.widget_settings?.welcome_message;
  if (welcome) {
    messages.value.push({ role: 'assistant', content: welcome });
  }
});

async function send() {
  const text = input.value.trim();
  if (!text || loading.value) return;

  messages.value.push({ role: 'user', content: text });
  input.value = '';
  loading.value = true;
  scrollToBottom();

  try {
    const res = await fetch(`${props.apiBase}/api/v1/chat`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
      body: JSON.stringify({
        client_code: props.clientCode,
        message: text,
        session_id: sessionId.value,
      }),
    });

    const data = await res.json();

    if (res.ok && data.answer) {
      messages.value.push({ role: 'assistant', content: data.answer });
    } else {
      messages.value.push({
        role: 'assistant',
        content: data.error || 'Sorry, something went wrong. Please try again.',
      });
    }
  } catch {
    messages.value.push({
      role: 'assistant',
      content: 'Unable to connect. Please check your internet connection.',
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

<style>
.davey-widget {
  position: fixed;
  bottom: 20px;
  z-index: 99999;
  font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
  font-size: 14px;
  line-height: 1.5;
}

.davey-toggle {
  width: 56px;
  height: 56px;
  border-radius: 50%;
  border: none;
  color: #fff;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
  transition: transform 0.2s;
}
.davey-toggle:hover { transform: scale(1.05); }

.davey-panel {
  width: 380px;
  max-height: 560px;
  display: flex;
  flex-direction: column;
  border-radius: 12px;
  overflow: hidden;
  box-shadow: 0 8px 30px rgba(0, 0, 0, 0.12);
}

/* Classic style */
.davey-classic .davey-panel { background: #fff; border: 1px solid #e5e7eb; }
.davey-classic .davey-msg-bubble { background: #f3f4f6; color: #111; border-radius: 12px; }

/* Modern style */
.davey-modern .davey-panel { background: #fafafa; border: none; }
.davey-modern .davey-msg-bubble { background: #f0f0f0; color: #111; border-radius: 16px; }
.davey-modern .davey-toggle { border-radius: 16px; }

/* Glass style */
.davey-glass .davey-panel { background: rgba(255, 255, 255, 0.85); backdrop-filter: blur(12px); border: 1px solid rgba(255, 255, 255, 0.3); }
.davey-glass .davey-msg-bubble { background: rgba(243, 244, 246, 0.7); color: #111; border-radius: 12px; }

.davey-header {
  padding: 14px 16px;
  color: #fff;
  display: flex;
  align-items: center;
  justify-content: space-between;
}
.davey-header-title { font-weight: 600; font-size: 15px; }
.davey-close {
  background: none;
  border: none;
  color: #fff;
  font-size: 22px;
  cursor: pointer;
  padding: 0 4px;
  line-height: 1;
}

.davey-messages {
  flex: 1;
  overflow-y: auto;
  padding: 16px;
  min-height: 300px;
  max-height: 380px;
}

.davey-msg { margin-bottom: 12px; display: flex; }
.davey-msg-user { justify-content: flex-end; }
.davey-msg-assistant { justify-content: flex-start; }
.davey-msg-bubble {
  max-width: 80%;
  padding: 10px 14px;
  word-break: break-word;
}

.davey-typing span {
  display: inline-block;
  width: 6px;
  height: 6px;
  border-radius: 50%;
  background: #999;
  margin: 0 2px;
  animation: davey-bounce 1.4s infinite ease-in-out;
}
.davey-typing span:nth-child(2) { animation-delay: 0.2s; }
.davey-typing span:nth-child(3) { animation-delay: 0.4s; }

@keyframes davey-bounce {
  0%, 80%, 100% { transform: translateY(0); }
  40% { transform: translateY(-6px); }
}

.davey-input-area {
  display: flex;
  padding: 12px;
  gap: 8px;
  border-top: 1px solid #e5e7eb;
}
.davey-input {
  flex: 1;
  padding: 10px 14px;
  border: 1px solid #d1d5db;
  border-radius: 8px;
  outline: none;
  font-size: 14px;
  font-family: inherit;
}
.davey-input:focus { border-color: #6366f1; }

.davey-send {
  width: 40px;
  height: 40px;
  border: none;
  border-radius: 8px;
  color: #fff;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}
.davey-send:disabled { opacity: 0.5; cursor: not-allowed; }

.davey-branding {
  text-align: center;
  padding: 6px;
  font-size: 11px;
  color: #9ca3af;
}
</style>
