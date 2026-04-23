<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { Bot, Check, Palette, RotateCcw, Save } from 'lucide-vue-next';
import { computed, onMounted, onUnmounted, reactive, ref, watch } from 'vue';
import InputError from '@/components/InputError.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';

const props = defineProps<{
    widget_styles: string[];
    widget_positions: string[];
    widget_theme_modes: string[];
    form: {
        widget_style: string;
        primary_color: string;
        accent_color: string;
        welcome_message: string;
        lead_capture_intro_message: string;
        toggle_text: string;
        position: string;
        theme_mode: 'system' | 'light' | 'dark';
        show_branding: boolean;
    };
    status?: string;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/portal/dashboard' },
    { title: 'Widget', href: '/portal/widget' },
];

// Local form state (reactive copy so preview updates live)
const form = reactive({ ...props.form });
const errors = ref<Record<string, string>>({});
const saving = ref(false);
const systemPrefersDark = ref(false);
let previewMedia: MediaQueryList | null = null;

const saved = computed(() => props.status === 'widget-updated');

// ── Live preview state ────────────────────────────────────────────────────────
const chatOpen = ref(true);
const previewMessages = [
    { role: 'assistant', content: form.welcome_message },
    { role: 'user', content: 'Can you tell me more?' },
    { role: 'assistant', content: 'Of course! I\'m here to help with any questions you have.' },
];

// Keep welcome message in sync with preview
watch(() => form.welcome_message, (v) => {
    previewMessages[0].content = v;
});

// ── Save ─────────────────────────────────────────────────────────────────────
function save() {
    saving.value = true;
    errors.value = {};
    router.patch('/portal/widget', { ...form }, {
        preserveScroll: true,
        onError: (e) => { errors.value = e; },
        onFinish: () => { saving.value = false; },
    });
}

function reset() {
    Object.assign(form, props.form);
}

function setThemeMode(mode: string) {
    if (mode === 'system' || mode === 'light' || mode === 'dark') {
        form.theme_mode = mode;
    }
}

const styleLabels: Record<string, string> = {
    classic: 'Classic',
    modern:  'Modern',
    glass:   'Glass',
};

const themeModeLabels: Record<string, string> = {
    system: 'System',
    light: 'Light',
    dark: 'Dark',
};

const previewIsDark = computed(() => {
    if (form.theme_mode === 'dark') {
        return true;
    }

    if (form.theme_mode === 'light') {
        return false;
    }

    return systemPrefersDark.value;
});

function syncPreviewDarkMode() {
    systemPrefersDark.value = !!previewMedia?.matches;
}

onMounted(() => {
    if (typeof window === 'undefined' || typeof window.matchMedia !== 'function') {
        return;
    }

    previewMedia = window.matchMedia('(prefers-color-scheme: dark)');
    syncPreviewDarkMode();

    if (typeof previewMedia.addEventListener === 'function') {
        previewMedia.addEventListener('change', syncPreviewDarkMode);
    } else {
        previewMedia.addListener(syncPreviewDarkMode);
    }
});

onUnmounted(() => {
    if (!previewMedia) {
        return;
    }

    if (typeof previewMedia.removeEventListener === 'function') {
        previewMedia.removeEventListener('change', syncPreviewDarkMode);
    } else {
        previewMedia.removeListener(syncPreviewDarkMode);
    }
});
</script>

<template>
    <Head title="Widget Customization" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-1 flex-col gap-6 p-4 md:p-6">

            <!-- Header -->
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h1 class="text-2xl font-bold tracking-tight">Widget Customization</h1>
                    <p class="mt-1 text-sm text-muted-foreground">
                        Customise the look and feel of your chat widget.
                    </p>
                </div>
                <div class="flex gap-2">
                    <Button variant="outline" size="sm" @click="reset">
                        <RotateCcw class="mr-1.5 h-3.5 w-3.5" />
                        Reset
                    </Button>
                    <Button size="sm" :disabled="saving" @click="save">
                        <Check v-if="saved && !saving" class="mr-1.5 h-3.5 w-3.5" />
                        <Save v-else class="mr-1.5 h-3.5 w-3.5" />
                        {{ saving ? 'Saving…' : saved ? 'Saved!' : 'Save Changes' }}
                    </Button>
                </div>
            </div>

            <!-- Success banner -->
            <div
                v-if="saved"
                class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700 dark:border-emerald-900/30 dark:bg-emerald-900/10 dark:text-emerald-400"
            >
                ✅ Widget settings saved successfully.
            </div>

            <div class="grid gap-6 lg:grid-cols-[1fr_360px]">

                <!-- ── Left: Settings ──────────────────────────────────────── -->
                <div class="space-y-5">

                    <!-- Style & Position -->
                    <Card class="border-sidebar-border/60 shadow-sm">
                        <CardHeader class="pb-3">
                            <div class="flex items-center gap-2">
                                <Palette class="h-4 w-4 text-muted-foreground" />
                                <p class="font-semibold">Appearance</p>
                            </div>
                        </CardHeader>
                        <CardContent class="space-y-5">
                            <!-- Widget style -->
                            <div class="space-y-2">
                                <Label>Widget Style</Label>
                                <div class="grid grid-cols-3 gap-3">
                                    <button
                                        v-for="s in widget_styles"
                                        :key="s"
                                        class="rounded-xl border-2 p-3 text-sm font-medium transition-all"
                                        :class="form.widget_style === s
                                            ? 'border-primary bg-primary/5 text-primary'
                                            : 'border-sidebar-border/50 text-muted-foreground hover:border-sidebar-border'"
                                        @click="form.widget_style = s"
                                    >
                                        {{ styleLabels[s] ?? s }}
                                    </button>
                                </div>
                            </div>

                            <!-- Position -->
                            <div class="space-y-2">
                                <Label>Position</Label>
                                <div class="flex gap-3">
                                    <button
                                        v-for="p in widget_positions"
                                        :key="p"
                                        class="flex-1 rounded-xl border-2 py-2.5 text-sm font-medium capitalize transition-all"
                                        :class="form.position === p
                                            ? 'border-primary bg-primary/5 text-primary'
                                            : 'border-sidebar-border/50 text-muted-foreground hover:border-sidebar-border'"
                                        @click="form.position = p"
                                    >
                                        {{ p }}
                                    </button>
                                </div>
                            </div>

                            <div class="space-y-2">
                                <Label>Theme Mode</Label>
                                <div class="grid grid-cols-3 gap-3">
                                    <button
                                        v-for="mode in widget_theme_modes"
                                        :key="mode"
                                        class="rounded-xl border-2 p-3 text-sm font-medium transition-all"
                                        :class="form.theme_mode === mode
                                            ? 'border-primary bg-primary/5 text-primary'
                                            : 'border-sidebar-border/50 text-muted-foreground hover:border-sidebar-border'"
                                        @click="setThemeMode(mode)"
                                    >
                                        {{ themeModeLabels[mode] ?? mode }}
                                    </button>
                                </div>
                                <InputError :message="errors.theme_mode" />
                            </div>

                            <!-- Colors -->
                            <div class="grid grid-cols-2 gap-4">
                                <div class="space-y-2">
                                    <Label for="primary_color">Primary Color</Label>
                                    <div class="flex items-center gap-2">
                                        <input
                                            id="primary_color"
                                            v-model="form.primary_color"
                                            type="color"
                                            class="h-10 w-12 cursor-pointer rounded-lg border border-input"
                                        />
                                        <Input v-model="form.primary_color" class="font-mono uppercase" maxlength="7" />
                                    </div>
                                    <InputError :message="errors.primary_color" />
                                </div>
                                <div class="space-y-2">
                                    <Label for="accent_color">Accent / Button Color</Label>
                                    <div class="flex items-center gap-2">
                                        <input
                                            id="accent_color"
                                            v-model="form.accent_color"
                                            type="color"
                                            class="h-10 w-12 cursor-pointer rounded-lg border border-input"
                                        />
                                        <Input v-model="form.accent_color" class="font-mono uppercase" maxlength="7" />
                                    </div>
                                    <InputError :message="errors.accent_color" />
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Text & Branding -->
                    <Card class="border-sidebar-border/60 shadow-sm">
                        <CardHeader class="pb-3">
                            <p class="font-semibold">Text & Branding</p>
                        </CardHeader>
                        <CardContent class="space-y-4">
                            <div class="space-y-2">
                                <Label for="welcome_message">Welcome Message</Label>
                                <textarea
                                    id="welcome_message"
                                    v-model="form.welcome_message"
                                    rows="3"
                                    maxlength="300"
                                    class="w-full resize-none rounded-xl border border-input bg-transparent px-3 py-2.5 text-sm outline-none focus:border-ring focus:ring-1 focus:ring-ring"
                                    placeholder="Ask us anything."
                                />
                                <div class="flex justify-between">
                                    <InputError :message="errors.welcome_message" />
                                    <span class="text-[10px] text-muted-foreground">{{ form.welcome_message.length }}/300</span>
                                </div>
                            </div>

                            <div class="space-y-2">
                                <Label for="toggle_text">Toggle Button Text</Label>
                                <Input
                                    id="toggle_text"
                                    v-model="form.toggle_text"
                                    placeholder="Ask anything about this business"
                                    maxlength="150"
                                />
                                <InputError :message="errors.toggle_text" />
                            </div>

                            <div class="space-y-2">
                                <Label for="lead_capture_intro_message">Lead Capture Intro Message</Label>
                                <textarea
                                    id="lead_capture_intro_message"
                                    v-model="form.lead_capture_intro_message"
                                    rows="3"
                                    maxlength="500"
                                    class="w-full resize-none rounded-xl border border-input bg-transparent px-3 py-2.5 text-sm outline-none focus:border-ring focus:ring-1 focus:ring-ring"
                                    placeholder="I can help with that! May I get your **name** first so our team can follow up with you?"
                                />
                                <div class="flex justify-between">
                                    <InputError :message="errors.lead_capture_intro_message" />
                                    <span class="text-[10px] text-muted-foreground">{{ form.lead_capture_intro_message.length }}/500</span>
                                </div>
                            </div>

                            <div class="flex items-center justify-between rounded-xl border border-sidebar-border/50 bg-muted/20 px-4 py-3">
                                <div>
                                    <p class="text-sm font-medium">Show Branding</p>
                                    <p class="text-xs text-muted-foreground">Display "Powered by" text in the widget footer.</p>
                                </div>
                                <button
                                    class="relative h-6 w-11 rounded-full transition-colors focus:outline-none"
                                    :class="form.show_branding ? 'bg-primary' : 'bg-muted-foreground/30'"
                                    @click="form.show_branding = !form.show_branding"
                                >
                                    <span
                                        class="absolute top-0.5 h-5 w-5 rounded-full bg-white shadow transition-transform"
                                        :class="form.show_branding ? 'translate-x-5' : 'translate-x-0.5'"
                                    />
                                </button>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <!-- ── Right: Live Preview ─────────────────────────────────── -->
                <div class="relative">
                    <div class="sticky top-6">
                        <p class="mb-3 text-xs font-bold uppercase tracking-widest text-muted-foreground">Live Preview</p>

                        <div class="relative h-[520px] w-full overflow-hidden rounded-2xl border border-sidebar-border/60 bg-muted/20">

                            <!-- Fake website bg -->
                            <div class="absolute inset-0 space-y-3 p-4 opacity-20 pointer-events-none select-none">
                                <div class="h-4 w-3/4 rounded bg-foreground/20" />
                                <div class="h-3 w-full rounded bg-foreground/15" />
                                <div class="h-3 w-5/6 rounded bg-foreground/15" />
                                <div class="h-3 w-4/6 rounded bg-foreground/15" />
                            </div>

                            <!-- Widget toggle button -->
                            <button
                                class="absolute bottom-4 rounded-full px-4 py-2.5 text-sm font-semibold text-white shadow-lg transition-all hover:opacity-90"
                                :class="form.position === 'right' ? 'right-4' : 'left-4'"
                                :style="{ background: form.accent_color }"
                                @click="chatOpen = !chatOpen"
                            >
                                <Bot class="inline h-4 w-4 mr-1.5" />
                                {{ chatOpen ? 'Close' : (form.toggle_text.slice(0, 20) + (form.toggle_text.length > 20 ? '…' : '')) }}
                            </button>

                            <!-- Chat window -->
                            <transition
                                enter-active-class="transition-all duration-200"
                                enter-from-class="opacity-0 translate-y-4"
                                enter-to-class="opacity-100 translate-y-0"
                                leave-active-class="transition-all duration-150"
                                leave-from-class="opacity-100 translate-y-0"
                                leave-to-class="opacity-0 translate-y-4"
                            >
                                <div
                                    v-if="chatOpen"
                                    class="absolute bottom-16 w-72 overflow-hidden rounded-2xl shadow-2xl"
                                    :class="form.position === 'right' ? 'right-4' : 'left-4'"
                                    :style="{
                                        backdropFilter: form.widget_style === 'glass' ? 'blur(12px)' : 'none',
                                        background: form.widget_style === 'glass'
                                            ? (previewIsDark ? 'rgba(15,23,42,0.75)' : 'rgba(255,255,255,0.75)')
                                            : (previewIsDark ? '#0f172a' : '#ffffff'),
                                        borderRadius: form.widget_style === 'modern' ? '1rem' : '0.75rem',
                                    }"
                                >
                                    <!-- Header -->
                                    <div
                                        class="px-4 py-3 text-sm font-semibold text-white"
                                        :style="{ background: form.primary_color }"
                                    >
                                        <div class="flex items-center gap-2">
                                            <div class="flex h-6 w-6 items-center justify-center rounded-full bg-white/20">
                                                <Bot class="h-3.5 w-3.5 text-white" />
                                            </div>
                                            Chat Assistant
                                        </div>
                                    </div>

                                    <!-- Messages -->
                                    <div
                                        class="space-y-3 p-3 text-xs max-h-52 overflow-y-auto"
                                        :class="previewIsDark ? 'bg-[#0b1220]' : 'bg-zinc-50'"
                                    >
                                        <div
                                            v-for="(msg, i) in previewMessages"
                                            :key="i"
                                            class="flex"
                                            :class="msg.role === 'user' ? 'justify-end' : 'justify-start'"
                                        >
                                            <div
                                                class="max-w-[85%] rounded-2xl px-3 py-1.5 text-xs"
                                                :class="msg.role === 'user'
                                                    ? 'text-white rounded-br-sm'
                                                    : (previewIsDark
                                                        ? 'bg-[#1e293b] border border-[#334155] text-white rounded-bl-sm'
                                                        : 'bg-white border border-zinc-200 text-zinc-900 rounded-bl-sm')"
                                                :style="msg.role === 'user'
                                                    ? { background: form.accent_color }
                                                    : {}"
                                            >
                                                {{ msg.content }}
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Input -->
                                    <div
                                        class="flex gap-1.5 border-t px-2 py-2"
                                        :class="previewIsDark ? 'border-[#1f2937] bg-[#0f172a]' : 'bg-white'"
                                    >
                                        <div
                                            class="flex-1 rounded-lg border px-2.5 py-1.5 text-[10px]"
                                            :class="previewIsDark
                                                ? 'border-[#334155] bg-[#111827] text-[#94a3b8]'
                                                : 'bg-zinc-50 text-muted-foreground'"
                                        >
                                            Type a message…
                                        </div>
                                        <button
                                            class="rounded-lg px-2.5 py-1.5 text-[10px] font-semibold text-white"
                                            :style="{ background: form.accent_color }"
                                        >
                                            Send
                                        </button>
                                    </div>

                                    <!-- Branding -->
                                    <div
                                        v-if="form.show_branding"
                                        class="py-1.5 text-center text-[9px]"
                                        :class="previewIsDark ? 'bg-[#0f172a] text-[#94a3b8]' : 'bg-white text-muted-foreground/60'"
                                    >
                                        Powered by Davey AI
                                    </div>
                                </div>
                            </transition>
                        </div>

                        <!-- Style / Position badges -->
                        <div class="mt-3 flex items-center gap-2">
                            <Badge variant="outline" class="text-[10px]">{{ styleLabels[form.widget_style] }}</Badge>
                            <Badge variant="outline" class="text-[10px] capitalize">{{ form.position }}</Badge>
                            <Badge variant="outline" class="text-[10px]">{{ themeModeLabels[form.theme_mode] }}</Badge>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
