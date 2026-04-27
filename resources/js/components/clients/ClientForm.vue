<script setup lang="ts">
import { Link, useForm } from '@inertiajs/vue3';
import {
    Bot,
    BrainCircuit,
    DatabaseZap,
    Globe,
    Package,
    Sparkles,
} from 'lucide-vue-next';
import { computed } from 'vue';
import InputError from '@/components/InputError.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type { ClientFormRecord, PlanRecord } from '@/types';

type Props = {
    client: ClientFormRecord;
    plans: PlanRecord[];
    widgetStyles: string[];
    clientStatuses: string[];
    widgetPositions: string[];
    widgetThemeModes: string[];
    chatModels: string[];
    embeddingModels: string[];
    heading: string;
    description: string;
    cancelHref: string;
};

const props = defineProps<Props>();

const form = useForm<ClientFormRecord>({ ...props.client });
const CUSTOM_MODEL_OPTION = '__custom__';

const selectClass =
    'h-10 w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs transition-[color,box-shadow] outline-none focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px]';

const textAreaClass =
    'min-h-28 w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs transition-[color,box-shadow] outline-none focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px]';

const statusLabels: Record<string, string> = {
    draft: 'Draft',
    active: 'Active',
    paused: 'Paused',
};

const widgetLabels: Record<string, string> = {
    classic: 'Classic',
    modern: 'Modern',
    glass: 'Glass',
};

const themeModeLabels: Record<string, string> = {
    system: 'System',
    light: 'Light',
    dark: 'Dark',
};

const selectedPlan = computed(
    () => props.plans.find((plan) => plan.id === Number(form.plan_id)) ?? null,
);

const selectedChatModelSuggestion = computed({
    get: () =>
        props.chatModels.includes(form.chat_model)
            ? form.chat_model
            : CUSTOM_MODEL_OPTION,
    set: (value: string) => {
        if (value !== CUSTOM_MODEL_OPTION) {
            form.chat_model = value;
        }
    },
});

function submit(): void {
    if (props.client.id) {
        form.patch(`/clients/${props.client.id}`, {
            preserveScroll: true,
        });

        return;
    }

    form.post('/clients', {
        preserveScroll: true,
    });
}
</script>

<template>
    <div class="grid gap-6 xl:grid-cols-[minmax(0,2fr)_360px]">
        <div class="space-y-6">
            <section
                class="overflow-hidden rounded-3xl border border-sidebar-border/70 bg-[radial-gradient(circle_at_top_right,_rgba(15,118,110,0.16),_transparent_40%),linear-gradient(135deg,_rgba(15,23,42,0.96),_rgba(15,118,110,0.92))] p-6 text-white"
            >
                <div
                    class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between"
                >
                    <div class="max-w-2xl space-y-3">
                        <Badge
                            variant="secondary"
                            class="border-white/10 bg-white/10 text-white"
                        >
                            Client setup workspace
                        </Badge>
                        <div class="space-y-2">
                            <h1 class="text-3xl font-semibold tracking-tight">
                                {{ heading }}
                            </h1>
                            <p class="max-w-2xl text-sm text-white/75">
                                {{ description }}
                            </p>
                        </div>
                    </div>

                    <Button
                        variant="secondary"
                        as-child
                        class="bg-white text-slate-950 hover:bg-white/90"
                    >
                        <Link :href="cancelHref">Cancel</Link>
                    </Button>
                </div>
            </section>

            <form class="space-y-6" @submit.prevent="submit">
                <Card class="gap-0 border-sidebar-border/70">
                    <CardHeader class="border-b border-sidebar-border/70">
                        <div class="flex items-start gap-3">
                            <div
                                class="rounded-2xl border border-sidebar-border/70 p-3"
                            >
                                <Package class="size-5" />
                            </div>
                            <div>
                                <h2 class="text-xl font-semibold">
                                    Client profile
                                </h2>
                                <p class="text-sm text-muted-foreground">
                                    The business identity, website, and internal
                                    context used by the admin team.
                                </p>
                            </div>
                        </div>
                    </CardHeader>
                    <CardContent class="grid gap-4 pt-6 md:grid-cols-2">
                        <div class="grid gap-2">
                            <Label for="name">Client name</Label>
                            <Input
                                id="name"
                                v-model="form.name"
                                placeholder="Bright Dental"
                            />
                            <InputError :message="form.errors.name" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="contact_email">Contact email</Label>
                            <Input
                                id="contact_email"
                                v-model="form.contact_email"
                                type="email"
                                placeholder="owner@client.test"
                            />
                            <InputError :message="form.errors.contact_email" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="website_url">Website URL</Label>
                            <Input
                                id="website_url"
                                v-model="form.website_url"
                                placeholder="https://client.test"
                            />
                            <InputError :message="form.errors.website_url" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="status">Status</Label>
                            <select
                                id="status"
                                v-model="form.status"
                                :class="selectClass"
                            >
                                <option
                                    v-for="status in clientStatuses"
                                    :key="status"
                                    :value="status"
                                >
                                    {{ statusLabels[status] ?? status }}
                                </option>
                            </select>
                            <InputError :message="form.errors.status" />
                        </div>

                        <div class="grid gap-2 md:col-span-2">
                            <Label for="business_description"
                                >Business description</Label
                            >
                            <textarea
                                id="business_description"
                                v-model="form.business_description"
                                :class="textAreaClass"
                                placeholder="Explain what this client does, what the chatbot should help with, and what should stay out of scope."
                            />
                            <InputError
                                :message="form.errors.business_description"
                            />
                        </div>

                        <div class="grid gap-2 md:col-span-2">
                            <Label for="notes">Internal notes</Label>
                            <textarea
                                id="notes"
                                v-model="form.notes"
                                :class="textAreaClass"
                                placeholder="Onboarding notes, sales context, or implementation reminders."
                            />
                            <InputError :message="form.errors.notes" />
                        </div>
                    </CardContent>
                </Card>

                <Card class="gap-0 border-sidebar-border/70">
                    <CardHeader class="border-b border-sidebar-border/70">
                        <div class="flex items-start gap-3">
                            <div
                                class="rounded-2xl border border-sidebar-border/70 p-3"
                            >
                                <Sparkles class="size-5" />
                            </div>
                            <div>
                                <h2 class="text-xl font-semibold">
                                    Subscription and limits
                                </h2>
                                <p class="text-sm text-muted-foreground">
                                    Assign the SaaS package and define the
                                    per-client billing guardrails.
                                </p>
                            </div>
                        </div>
                    </CardHeader>
                    <CardContent class="grid gap-4 pt-6 md:grid-cols-2">
                        <div class="grid gap-2">
                            <Label for="plan_id">Package</Label>
                            <select
                                id="plan_id"
                                v-model="form.plan_id"
                                :class="selectClass"
                            >
                                <option
                                    v-for="plan in plans"
                                    :key="plan.id"
                                    :value="plan.id"
                                >
                                    {{ plan.name }} - ${{
                                        plan.price_monthly.toFixed(2)
                                    }}/mo
                                </option>
                            </select>
                            <InputError :message="form.errors.plan_id" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="monthly_token_limit"
                                >Monthly token limit</Label
                            >
                            <Input
                                id="monthly_token_limit"
                                v-model="form.monthly_token_limit"
                                type="number"
                                min="1000"
                                step="1000"
                            />
                            <InputError
                                :message="form.errors.monthly_token_limit"
                            />
                        </div>

                        <div
                            v-if="selectedPlan"
                            class="rounded-2xl border border-sidebar-border/70 bg-accent/20 p-4 md:col-span-2"
                        >
                            <div
                                class="flex flex-wrap items-center justify-between gap-3"
                            >
                                <div>
                                    <p class="font-medium">
                                        {{ selectedPlan.name }}
                                    </p>
                                    <p class="text-sm text-muted-foreground">
                                        {{ selectedPlan.description }}
                                    </p>
                                </div>
                                <Badge variant="outline">
                                    {{ selectedPlan.max_knowledge_sources }}
                                    sources /
                                    {{ selectedPlan.max_file_upload_mb }} MB
                                    files
                                </Badge>
                            </div>
                            <div
                                class="mt-3 grid gap-2 text-sm text-muted-foreground md:grid-cols-3"
                            >
                                <p>
                                    Price:
                                    <span class="text-foreground"
                                        >${{
                                            selectedPlan.price_monthly.toFixed(
                                                2,
                                            )
                                        }}</span
                                    >
                                </p>
                                <p>
                                    Plan token pool:
                                    <span class="text-foreground">{{
                                        selectedPlan.monthly_token_limit.toLocaleString()
                                    }}</span>
                                </p>
                                <p>
                                    Message limit:
                                    <span class="text-foreground">{{
                                        selectedPlan.monthly_message_limit?.toLocaleString() ??
                                        'Custom'
                                    }}</span>
                                </p>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <Card class="gap-0 border-sidebar-border/70">
                    <CardHeader class="border-b border-sidebar-border/70">
                        <div class="flex items-start gap-3">
                            <div
                                class="rounded-2xl border border-sidebar-border/70 p-3"
                            >
                                <BrainCircuit class="size-5" />
                            </div>
                            <div>
                                <h2 class="text-xl font-semibold">
                                    AI and retrieval config
                                </h2>
                                <p class="text-sm text-muted-foreground">
                                    These settings shape the future RAG flow,
                                    prompt caching strategy, and token usage
                                    behavior.
                                </p>
                            </div>
                        </div>
                    </CardHeader>
                    <CardContent class="grid gap-4 pt-6 md:grid-cols-2">
                        <div
                            class="grid gap-4 rounded-2xl border border-sidebar-border/70 p-4 md:col-span-2 md:grid-cols-2"
                        >
                            <div class="grid content-start gap-2">
                                <Label for="chat_model_suggestion"
                                    >Chat model suggestions</Label
                                >
                                <select
                                    id="chat_model_suggestion"
                                    v-model="selectedChatModelSuggestion"
                                    :class="selectClass"
                                >
                                    <option
                                        v-for="model in chatModels"
                                        :key="model"
                                        :value="model"
                                    >
                                        {{ model }}
                                    </option>
                                    <option :value="CUSTOM_MODEL_OPTION">
                                        Custom model
                                    </option>
                                </select>
                                <Label for="chat_model">Chat model</Label>
                                <Input
                                    id="chat_model"
                                    v-model="form.chat_model"
                                    placeholder="openai/gpt-4o-mini"
                                />
                                <p
                                    class="text-xs leading-5 text-muted-foreground"
                                >
                                    Pick from the dropdown or type any other
                                    model ID manually.
                                    <a
                                        href="https://openrouter.ai/models"
                                        target="_blank"
                                        rel="noreferrer"
                                        class="underline underline-offset-2"
                                    >
                                        View all models
                                    </a>
                                </p>
                                <InputError :message="form.errors.chat_model" />
                            </div>

                            <div class="grid content-start gap-2">
                                <Label for="embedding_model"
                                    >Embedding model</Label
                                >
                                <select
                                    id="embedding_model"
                                    v-model="form.embedding_model"
                                    :class="selectClass"
                                >
                                    <option
                                        v-for="model in embeddingModels"
                                        :key="model"
                                        :value="model"
                                    >
                                        {{ model }}
                                    </option>
                                </select>
                                <p
                                    class="text-xs leading-5 text-muted-foreground"
                                >
                                    Use the approved embedding options for this
                                    workspace.
                                </p>
                                <InputError
                                    :message="form.errors.embedding_model"
                                />
                            </div>
                        </div>

                        <div class="grid gap-2">
                            <Label for="retrieval_chunk_count"
                                >Retrieved chunks per answer</Label
                            >
                            <Input
                                id="retrieval_chunk_count"
                                v-model="form.retrieval_chunk_count"
                                type="number"
                                min="1"
                                max="8"
                            />
                            <InputError
                                :message="form.errors.retrieval_chunk_count"
                            />
                        </div>

                        <div class="grid gap-2">
                            <Label for="cache_ttl_hours"
                                >Semantic cache TTL (hours)</Label
                            >
                            <Input
                                id="cache_ttl_hours"
                                v-model="form.cache_ttl_hours"
                                type="number"
                                min="1"
                                max="720"
                            />
                            <InputError
                                :message="form.errors.cache_ttl_hours"
                            />
                        </div>

                        <div class="grid gap-2 md:col-span-2">
                            <Label for="system_prompt">System prompt</Label>
                            <textarea
                                id="system_prompt"
                                v-model="form.system_prompt"
                                :class="textAreaClass"
                                placeholder="Tell the assistant how to answer, what to avoid, and how to use the client knowledge base."
                            />
                            <InputError :message="form.errors.system_prompt" />
                        </div>

                        <div class="grid gap-2 md:col-span-2">
                            <Label for="allowed_domains">Allowed domains</Label>
                            <textarea
                                id="allowed_domains"
                                v-model="form.allowed_domains"
                                :class="textAreaClass"
                                placeholder="client.test&#10;www.client.test"
                            />
                            <p class="text-xs text-muted-foreground">
                                One domain per line. These domains can later be
                                used for widget install allowlists or crawl
                                targets.
                            </p>
                            <InputError
                                :message="form.errors.allowed_domains"
                            />
                        </div>

                        <div class="grid gap-2">
                            <Label class="text-sm">Prompt caching</Label>
                            <label
                                class="flex min-h-10 items-center gap-3 rounded-md border border-input px-3 py-2 text-sm"
                            >
                                <input
                                    v-model="form.prompt_caching_enabled"
                                    type="checkbox"
                                    class="size-4 rounded border-input"
                                />
                                Keep stable prefixes so OpenAI prompt caching
                                can reduce repeated input cost.
                            </label>
                            <InputError
                                :message="form.errors.prompt_caching_enabled"
                            />
                        </div>

                        <div class="grid gap-2">
                            <Label class="text-sm">Semantic cache</Label>
                            <label
                                class="flex min-h-10 items-center gap-3 rounded-md border border-input px-3 py-2 text-sm"
                            >
                                <input
                                    v-model="form.semantic_cache_enabled"
                                    type="checkbox"
                                    class="size-4 rounded border-input"
                                />
                                Reuse previous answers for near-identical
                                questions inside the configured TTL window.
                            </label>
                            <InputError
                                :message="form.errors.semantic_cache_enabled"
                            />
                        </div>
                    </CardContent>
                </Card>

                <Card class="gap-0 border-sidebar-border/70">
                    <CardHeader class="border-b border-sidebar-border/70">
                        <div class="flex items-start gap-3">
                            <div
                                class="rounded-2xl border border-sidebar-border/70 p-3"
                            >
                                <Bot class="size-5" />
                            </div>
                            <div>
                                <h2 class="text-xl font-semibold">
                                    Widget identity
                                </h2>
                                <p class="text-sm text-muted-foreground">
                                    Configure the widget preset and brand
                                    surface that the public script will use.
                                </p>
                            </div>
                        </div>
                    </CardHeader>
                    <CardContent class="grid gap-4 pt-6 md:grid-cols-2">
                        <div class="grid gap-2">
                            <Label for="widget_style">Widget preset</Label>
                            <select
                                id="widget_style"
                                v-model="form.widget_style"
                                :class="selectClass"
                            >
                                <option
                                    v-for="style in widgetStyles"
                                    :key="style"
                                    :value="style"
                                >
                                    {{ widgetLabels[style] ?? style }}
                                </option>
                            </select>
                            <InputError :message="form.errors.widget_style" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="position">Widget position</Label>
                            <select
                                id="position"
                                v-model="form.position"
                                :class="selectClass"
                            >
                                <option
                                    v-for="position in widgetPositions"
                                    :key="position"
                                    :value="position"
                                >
                                    {{
                                        position === 'right'
                                            ? 'Bottom right'
                                            : 'Bottom left'
                                    }}
                                </option>
                            </select>
                            <InputError :message="form.errors.position" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="theme_mode">Theme mode</Label>
                            <select
                                id="theme_mode"
                                v-model="form.theme_mode"
                                :class="selectClass"
                            >
                                <option
                                    v-for="mode in widgetThemeModes"
                                    :key="mode"
                                    :value="mode"
                                >
                                    {{ themeModeLabels[mode] ?? mode }}
                                </option>
                            </select>
                            <InputError :message="form.errors.theme_mode" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="primary_color">Primary color</Label>
                            <Input
                                id="primary_color"
                                v-model="form.primary_color"
                                type="color"
                                class="h-12"
                            />
                            <InputError :message="form.errors.primary_color" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="accent_color">Accent color</Label>
                            <Input
                                id="accent_color"
                                v-model="form.accent_color"
                                type="color"
                                class="h-12"
                            />
                            <InputError :message="form.errors.accent_color" />
                        </div>

                        <div class="grid gap-2 md:col-span-2">
                            <Label for="welcome_message">Welcome message</Label>
                            <textarea
                                id="welcome_message"
                                v-model="form.welcome_message"
                                :class="textAreaClass"
                                placeholder="Ask us about pricing, hours, support, and service details."
                            />
                            <InputError
                                :message="form.errors.welcome_message"
                            />
                        </div>

                        <div class="grid gap-2 md:col-span-2">
                            <Label for="lead_capture_intro_message"
                                >Lead capture intro message</Label
                            >
                            <textarea
                                id="lead_capture_intro_message"
                                v-model="form.lead_capture_intro_message"
                                :class="textAreaClass"
                                placeholder="I can help with that! May I get your **name** first so our team can follow up with you?"
                            />
                            <InputError
                                :message="
                                    form.errors.lead_capture_intro_message
                                "
                            />
                        </div>

                        <div class="grid gap-2 md:col-span-2">
                            <Label for="toggle_text"
                                >Pill toggle text (Modern style)</Label
                            >
                            <Input
                                id="toggle_text"
                                v-model="form.toggle_text"
                                placeholder="Ask anything about this business"
                                maxlength="50"
                            />
                            <InputError :message="form.errors.toggle_text" />
                        </div>

                        <div class="grid gap-2 md:col-span-2">
                            <Label class="text-sm">Branding</Label>
                            <label
                                class="flex min-h-10 items-center gap-3 rounded-md border border-input px-3 py-2 text-sm"
                            >
                                <input
                                    v-model="form.show_branding"
                                    type="checkbox"
                                    class="size-4 rounded border-input"
                                />
                                Show platform branding in the widget shell
                            </label>
                            <InputError :message="form.errors.show_branding" />
                        </div>
                    </CardContent>
                </Card>

                <div class="flex flex-wrap items-center gap-3">
                    <Button type="submit" :disabled="form.processing">
                        {{
                            form.processing
                                ? 'Saving...'
                                : client.id
                                  ? 'Update client'
                                  : 'Create client'
                        }}
                    </Button>
                    <Button variant="outline" as-child>
                        <Link :href="cancelHref">Cancel</Link>
                    </Button>
                </div>
            </form>
        </div>

        <div class="space-y-6">
            <Card class="gap-0 border-sidebar-border/70">
                <CardHeader class="border-b border-sidebar-border/70">
                    <h2 class="text-lg font-semibold">Preview and rollout</h2>
                    <p class="text-sm text-muted-foreground">
                        Quick reference for the current package, widget surface,
                        and cost controls.
                    </p>
                </CardHeader>
                <CardContent class="space-y-4 pt-6">
                    <div
                        class="rounded-3xl border border-sidebar-border/70 p-4"
                    >
                        <div class="mb-3 flex items-center justify-between">
                            <p class="text-sm font-medium">Widget preview</p>
                            <div class="flex items-center gap-1.5">
                                <Badge variant="outline">
                                    {{
                                        widgetLabels[form.widget_style] ??
                                        form.widget_style
                                    }}
                                </Badge>
                                <Badge variant="outline">
                                    {{
                                        themeModeLabels[form.theme_mode] ??
                                        form.theme_mode
                                    }}
                                </Badge>
                            </div>
                        </div>

                        <div
                            class="rounded-[1.75rem] border border-sidebar-border/70 p-4"
                            :style="{
                                background:
                                    form.widget_style === 'glass'
                                        ? `linear-gradient(135deg, ${form.primary_color}22, ${form.accent_color}2b)`
                                        : 'linear-gradient(135deg, rgba(15,23,42,0.04), rgba(15,118,110,0.08))',
                            }"
                        >
                            <div
                                v-if="form.widget_style === 'classic'"
                                class="flex min-h-32 items-end justify-end"
                            >
                                <div
                                    class="flex h-14 w-14 items-center justify-center rounded-full text-sm font-medium text-white shadow-lg"
                                    :style="{ background: form.primary_color }"
                                >
                                    Chat
                                </div>
                            </div>

                            <div
                                v-else-if="form.widget_style === 'modern'"
                                class="flex min-h-32 items-end"
                            >
                                <div
                                    class="flex w-full items-center gap-3 rounded-full border border-white/70 bg-white/90 px-4 py-3 shadow-sm"
                                >
                                    <div
                                        class="size-3 rounded-full"
                                        :style="{
                                            background: form.accent_color,
                                        }"
                                    ></div>
                                    <span class="text-sm text-slate-700">{{
                                        form.toggle_text ||
                                        'Ask anything about this business'
                                    }}</span>
                                </div>
                            </div>

                            <div
                                v-else
                                class="min-h-32 rounded-[1.75rem] border border-white/25 bg-white/10 p-4 text-white shadow-lg backdrop-blur-md"
                            >
                                <div
                                    class="max-w-[80%] rounded-2xl px-3 py-2 text-sm"
                                    :style="{ background: form.primary_color }"
                                >
                                    {{ form.welcome_message }}
                                </div>
                            </div>
                        </div>
                    </div>

                    <div
                        class="rounded-2xl border border-sidebar-border/70 p-4 text-sm"
                    >
                        <div class="flex items-center gap-2 font-medium">
                            <DatabaseZap class="size-4" />
                            Cost strategy
                        </div>
                        <div class="mt-3 grid gap-2 text-muted-foreground">
                            <p>
                                Prompt caching:
                                <span class="text-foreground">{{
                                    form.prompt_caching_enabled
                                        ? 'Enabled'
                                        : 'Disabled'
                                }}</span>
                            </p>
                            <p>
                                Semantic cache TTL:
                                <span class="text-foreground"
                                    >{{ form.cache_ttl_hours }} hours</span
                                >
                            </p>
                            <p>
                                Retrieved chunks:
                                <span class="text-foreground">{{
                                    form.retrieval_chunk_count
                                }}</span>
                            </p>
                            <p>
                                Model pair:
                                <span class="text-foreground"
                                    >{{ form.chat_model }} /
                                    {{ form.embedding_model }}</span
                                >
                            </p>
                        </div>
                    </div>

                    <div
                        v-if="selectedPlan"
                        class="rounded-2xl border border-sidebar-border/70 p-4 text-sm"
                    >
                        <div class="flex items-center gap-2 font-medium">
                            <Globe class="size-4" />
                            Package snapshot
                        </div>
                        <div class="mt-3 grid gap-2 text-muted-foreground">
                            <p>
                                Selected plan:
                                <span class="text-foreground">{{
                                    selectedPlan.name
                                }}</span>
                            </p>
                            <p>
                                Plan price:
                                <span class="text-foreground"
                                    >${{
                                        selectedPlan.price_monthly.toFixed(2)
                                    }}/mo</span
                                >
                            </p>
                            <p>
                                Knowledge capacity:
                                <span class="text-foreground"
                                    >{{
                                        selectedPlan.max_knowledge_sources
                                    }}
                                    sources</span
                                >
                            </p>
                            <p>
                                Upload limit:
                                <span class="text-foreground"
                                    >{{
                                        selectedPlan.max_file_upload_mb
                                    }}
                                    MB</span
                                >
                            </p>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>
    </div>
</template>
