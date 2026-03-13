<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import {
    Bot,
    Building2,
    Code2,
    Gauge,
    Globe,
    Plus,
    Search,
} from 'lucide-vue-next';
import { computed, ref } from 'vue';
import InputError from '@/components/InputError.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader } from '@/components/ui/card';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';

type WidgetSettings = {
    primary_color: string;
    accent_color: string;
    welcome_message: string;
    position: string;
    show_branding: boolean;
};

type Client = {
    id: number;
    name: string;
    unique_code: string;
    contact_email: string | null;
    website_url: string | null;
    monthly_token_limit: number;
    status: string;
    widget_style: string;
    notes: string | null;
    widget_settings: WidgetSettings;
    created_at: string | null;
};

type Props = {
    clients: Client[];
    summary: {
        total_clients: number;
        active_clients: number;
        paused_clients: number;
        monthly_token_capacity: number;
    };
    status?: string;
    widget_script_url: string;
    widget_styles: string[];
    client_statuses: string[];
    widget_positions: string[];
};

type ClientForm = {
    name: string;
    contact_email: string;
    website_url: string;
    monthly_token_limit: number | string;
    status: string;
    widget_style: string;
    primary_color: string;
    accent_color: string;
    welcome_message: string;
    position: string;
    show_branding: boolean;
    notes: string;
};

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Admin clients',
        href: dashboard(),
    },
];

const search = ref('');
const isDialogOpen = ref(false);
const editingClientId = ref<number | null>(null);

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

const widgetDescriptions: Record<string, string> = {
    classic: 'Floating bubble with a familiar support chat shape.',
    modern: 'Minimal input bar that expands into a clean support panel.',
    glass: 'High-end glassmorphism shell for premium brand sites.',
};

const controlClass =
    'w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs transition-[color,box-shadow] outline-none focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px]';

const fieldClass = `h-10 ${controlClass}`;

const textAreaClass = `min-h-24 resize-y ${controlClass}`;

const createDefaultForm = (): ClientForm => ({
    name: '',
    contact_email: '',
    website_url: '',
    monthly_token_limit: 500000,
    status: 'draft',
    widget_style: 'classic',
    primary_color: '#111827',
    accent_color: '#0f766e',
    welcome_message: 'Ask us anything about pricing, services, and support.',
    position: 'right',
    show_branding: true,
    notes: '',
});

const form = useForm<ClientForm>(createDefaultForm());

const isEditing = computed(() => editingClientId.value !== null);

const filteredClients = computed(() => {
    const term = search.value.trim().toLowerCase();

    if (!term) {
        return props.clients;
    }

    return props.clients.filter((client) => {
        const haystack = [
            client.name,
            client.contact_email ?? '',
            client.website_url ?? '',
            client.unique_code,
            client.widget_style,
            client.status,
        ]
            .join(' ')
            .toLowerCase();

        return haystack.includes(term);
    });
});

const statusMessage = computed(() => {
    if (props.status === 'client-created') {
        return 'Client added to the admin workspace.';
    }

    if (props.status === 'client-updated') {
        return 'Client settings updated.';
    }

    return null;
});

const metricCards = computed(() => [
    {
        label: 'Clients',
        value: props.summary.total_clients,
        helper: 'Tenants configured in the admin dashboard',
        icon: Building2,
    },
    {
        label: 'Active bots',
        value: props.summary.active_clients,
        helper: 'Ready for widget installation',
        icon: Bot,
    },
    {
        label: 'Paused',
        value: props.summary.paused_clients,
        helper: 'Temporarily disabled tenants',
        icon: Globe,
    },
    {
        label: 'Monthly token pool',
        value: formatNumber(props.summary.monthly_token_capacity),
        helper: 'Combined allowance across all clients',
        icon: Gauge,
    },
]);

function formatNumber(value: number): string {
    return new Intl.NumberFormat().format(value);
}

function formatDate(value: string | null): string {
    if (!value) {
        return 'Just created';
    }

    return new Intl.DateTimeFormat(undefined, {
        dateStyle: 'medium',
        timeStyle: 'short',
    }).format(new Date(value));
}

function formatWebsite(url: string | null): string {
    if (!url) {
        return 'Not connected yet';
    }

    return url.replace(/^https?:\/\//, '');
}

function hydrateForm(values: ClientForm): void {
    form.name = values.name;
    form.contact_email = values.contact_email;
    form.website_url = values.website_url;
    form.monthly_token_limit = values.monthly_token_limit;
    form.status = values.status;
    form.widget_style = values.widget_style;
    form.primary_color = values.primary_color;
    form.accent_color = values.accent_color;
    form.welcome_message = values.welcome_message;
    form.position = values.position;
    form.show_branding = values.show_branding;
    form.notes = values.notes;
}

function resetForm(): void {
    hydrateForm(createDefaultForm());
    form.clearErrors();
}

function openCreateDialog(): void {
    editingClientId.value = null;
    resetForm();
    isDialogOpen.value = true;
}

function openEditDialog(client: Client): void {
    editingClientId.value = client.id;
    hydrateForm({
        name: client.name,
        contact_email: client.contact_email ?? '',
        website_url: client.website_url ?? '',
        monthly_token_limit: client.monthly_token_limit,
        status: client.status,
        widget_style: client.widget_style,
        primary_color: client.widget_settings.primary_color,
        accent_color: client.widget_settings.accent_color,
        welcome_message: client.widget_settings.welcome_message,
        position: client.widget_settings.position,
        show_branding: client.widget_settings.show_branding,
        notes: client.notes ?? '',
    });
    form.clearErrors();
    isDialogOpen.value = true;
}

function closeDialog(): void {
    isDialogOpen.value = false;
    editingClientId.value = null;
    resetForm();
}

function handleDialogChange(isOpen: boolean): void {
    if (isOpen) {
        isDialogOpen.value = true;

        return;
    }

    closeDialog();
}

function submitClient(): void {
    form.transform((data) => ({
        ...data,
        monthly_token_limit: Number(data.monthly_token_limit),
        show_branding: data.show_branding,
    }));

    const options = {
        preserveScroll: true,
        onSuccess: () => closeDialog(),
    };

    if (editingClientId.value) {
        form.patch(`/clients/${editingClientId.value}`, options);

        return;
    }

    form.post('/clients', options);
}

function badgeVariant(status: string): 'default' | 'secondary' | 'outline' {
    if (status === 'active') {
        return 'default';
    }

    if (status === 'paused') {
        return 'secondary';
    }

    return 'outline';
}

function embedSnippet(client: Client): string {
    return `<script src="${props.widget_script_url}" data-client-id="${client.unique_code}"><\/script>`;
}

function previewContainerStyle(client: Client): string {
    if (client.widget_style === 'glass') {
        return `background: linear-gradient(135deg, ${client.widget_settings.primary_color}1a, ${client.widget_settings.accent_color}26);`;
    }

    return 'background: linear-gradient(135deg, rgba(15, 23, 42, 0.04), rgba(15, 118, 110, 0.08));';
}
</script>

<template>
    <Head title="Admin clients" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-1 flex-col gap-6 p-4 md:p-6">
            <section
                class="overflow-hidden rounded-3xl border border-sidebar-border/70 bg-[radial-gradient(circle_at_top_right,_rgba(15,118,110,0.18),_transparent_36%),linear-gradient(135deg,_rgba(15,23,42,0.96),_rgba(15,118,110,0.92))] p-6 text-white shadow-sm"
            >
                <div
                    class="flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between"
                >
                    <div class="max-w-2xl space-y-3">
                        <Badge
                            variant="secondary"
                            class="border-white/10 bg-white/10 text-white"
                        >
                            Multi-tenant admin dashboard
                        </Badge>
                        <div class="space-y-2">
                            <h1 class="text-3xl font-semibold tracking-tight">
                                Set up clients, widget presets, and installation
                                codes.
                            </h1>
                            <p class="max-w-xl text-sm text-white/75">
                                This first milestone focuses on tenant setup for
                                the SaaS chatbot: create clients, define widget
                                styling, assign token limits, and hand off a
                                client-specific embed script.
                            </p>
                        </div>
                    </div>

                    <div class="flex flex-wrap gap-3">
                        <Button
                            variant="secondary"
                            class="gap-2 bg-white text-slate-900 hover:bg-white/90"
                            @click="openCreateDialog"
                        >
                            <Plus class="size-4" />
                            Add client
                        </Button>
                    </div>
                </div>
            </section>

            <div
                v-if="statusMessage"
                class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700 dark:border-emerald-300/10 dark:bg-emerald-500/10 dark:text-emerald-200"
            >
                {{ statusMessage }}
            </div>

            <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                <Card
                    v-for="metric in metricCards"
                    :key="metric.label"
                    class="gap-0 border-sidebar-border/70"
                >
                    <CardHeader class="gap-4 pb-3">
                        <div class="flex items-center justify-between gap-4">
                            <div>
                                <p class="text-sm text-muted-foreground">
                                    {{ metric.label }}
                                </p>
                                <p
                                    class="text-2xl font-semibold tracking-tight"
                                >
                                    {{ metric.value }}
                                </p>
                            </div>
                            <div
                                class="flex size-11 items-center justify-center rounded-2xl border border-sidebar-border/70 bg-accent/40"
                            >
                                <component :is="metric.icon" class="size-5" />
                            </div>
                        </div>
                    </CardHeader>
                    <CardContent class="pt-0">
                        <p class="text-sm text-muted-foreground">
                            {{ metric.helper }}
                        </p>
                    </CardContent>
                </Card>
            </section>

            <section
                class="grid gap-4 xl:grid-cols-[minmax(0,2fr)_minmax(320px,1fr)]"
            >
                <Card class="gap-0 border-sidebar-border/70">
                    <CardHeader class="gap-4 border-b border-sidebar-border/70">
                        <div
                            class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between"
                        >
                            <div>
                                <h2 class="text-xl font-semibold">
                                    Client workspace
                                </h2>
                                <p class="text-sm text-muted-foreground">
                                    Manage tenant setup before knowledge
                                    ingestion and public widget rollout.
                                </p>
                            </div>
                            <div class="relative w-full lg:w-[320px]">
                                <Search
                                    class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground"
                                />
                                <Input
                                    v-model="search"
                                    class="pl-9"
                                    placeholder="Search clients, code, or domain"
                                />
                            </div>
                        </div>
                    </CardHeader>

                    <CardContent class="pt-6">
                        <div
                            v-if="filteredClients.length === 0"
                            class="rounded-2xl border border-dashed border-sidebar-border/70 bg-accent/20 px-6 py-12 text-center"
                        >
                            <div class="mx-auto max-w-sm space-y-3">
                                <p class="text-lg font-medium">
                                    No clients match the current filter.
                                </p>
                                <p class="text-sm text-muted-foreground">
                                    Create the first tenant to generate a unique
                                    widget code and start configuring the future
                                    chatbot rollout.
                                </p>
                                <Button class="gap-2" @click="openCreateDialog">
                                    <Plus class="size-4" />
                                    Create first client
                                </Button>
                            </div>
                        </div>

                        <div v-else class="grid gap-4 2xl:grid-cols-2">
                            <article
                                v-for="client in filteredClients"
                                :key="client.id"
                                class="rounded-2xl border border-sidebar-border/70 bg-card/70 p-5"
                            >
                                <div
                                    class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between"
                                >
                                    <div class="space-y-2">
                                        <div
                                            class="flex flex-wrap items-center gap-2"
                                        >
                                            <h3
                                                class="text-lg font-semibold tracking-tight"
                                            >
                                                {{ client.name }}
                                            </h3>
                                            <Badge
                                                :variant="
                                                    badgeVariant(client.status)
                                                "
                                            >
                                                {{
                                                    statusLabels[
                                                        client.status
                                                    ] ?? client.status
                                                }}
                                            </Badge>
                                            <Badge variant="outline">
                                                {{
                                                    widgetLabels[
                                                        client.widget_style
                                                    ] ?? client.widget_style
                                                }}
                                            </Badge>
                                        </div>

                                        <div
                                            class="flex flex-col gap-1 text-sm text-muted-foreground"
                                        >
                                            <p>
                                                Client code:
                                                <span
                                                    class="font-mono text-xs text-foreground"
                                                >
                                                    {{ client.unique_code }}
                                                </span>
                                            </p>
                                            <p>
                                                {{
                                                    formatDate(
                                                        client.created_at,
                                                    )
                                                }}
                                            </p>
                                        </div>
                                    </div>

                                    <Button
                                        variant="outline"
                                        @click="openEditDialog(client)"
                                    >
                                        Edit client
                                    </Button>
                                </div>

                                <div class="mt-5 grid gap-4 md:grid-cols-2">
                                    <div class="space-y-3">
                                        <div
                                            class="rounded-2xl border border-sidebar-border/70 p-4"
                                            :style="
                                                previewContainerStyle(client)
                                            "
                                        >
                                            <div
                                                class="mb-4 flex items-center justify-between text-xs tracking-[0.2em] text-muted-foreground uppercase"
                                            >
                                                <span>Widget preview</span>
                                                <span>
                                                    {{
                                                        client.widget_settings
                                                            .position
                                                    }}
                                                </span>
                                            </div>

                                            <div
                                                v-if="
                                                    client.widget_style ===
                                                    'classic'
                                                "
                                                class="flex min-h-32 items-end justify-end"
                                            >
                                                <div
                                                    class="flex h-14 w-14 items-center justify-center rounded-full text-sm font-medium text-white shadow-lg"
                                                    :style="{
                                                        background:
                                                            client
                                                                .widget_settings
                                                                .primary_color,
                                                    }"
                                                >
                                                    Chat
                                                </div>
                                            </div>

                                            <div
                                                v-else-if="
                                                    client.widget_style ===
                                                    'modern'
                                                "
                                                class="flex min-h-32 items-end"
                                            >
                                                <div
                                                    class="flex w-full items-center gap-3 rounded-full border border-white/70 bg-white/90 px-4 py-3 shadow-sm"
                                                >
                                                    <div
                                                        class="size-3 rounded-full"
                                                        :style="{
                                                            background:
                                                                client
                                                                    .widget_settings
                                                                    .accent_color,
                                                        }"
                                                    ></div>
                                                    <span
                                                        class="text-sm text-slate-700"
                                                    >
                                                        Ask anything about this
                                                        business
                                                    </span>
                                                </div>
                                            </div>

                                            <div
                                                v-else
                                                class="min-h-32 rounded-[1.75rem] border border-white/25 bg-white/10 p-4 text-white shadow-lg backdrop-blur-md"
                                            >
                                                <div
                                                    class="mb-4 flex items-center justify-between text-xs text-white/70"
                                                >
                                                    <span>Premium support</span>
                                                    <span>Live</span>
                                                </div>
                                                <div class="space-y-2">
                                                    <div
                                                        class="max-w-[80%] rounded-2xl px-3 py-2 text-sm"
                                                        :style="{
                                                            background:
                                                                client
                                                                    .widget_settings
                                                                    .primary_color,
                                                        }"
                                                    >
                                                        {{
                                                            client
                                                                .widget_settings
                                                                .welcome_message
                                                        }}
                                                    </div>
                                                    <div
                                                        class="ml-auto max-w-[75%] rounded-2xl bg-white/85 px-3 py-2 text-sm text-slate-800"
                                                    >
                                                        Tell me about your
                                                        offers.
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <div
                                            class="grid gap-3 text-sm text-muted-foreground"
                                        >
                                            <div
                                                class="rounded-xl border border-sidebar-border/70 px-4 py-3"
                                            >
                                                <p
                                                    class="font-medium text-foreground"
                                                >
                                                    Widget behavior
                                                </p>
                                                <p class="mt-1">
                                                    {{
                                                        widgetDescriptions[
                                                            client.widget_style
                                                        ]
                                                    }}
                                                </p>
                                                <p class="mt-2 text-foreground">
                                                    Branding:
                                                    <span class="font-medium">
                                                        {{
                                                            client
                                                                .widget_settings
                                                                .show_branding
                                                                ? 'Visible'
                                                                : 'Hidden'
                                                        }}
                                                    </span>
                                                </p>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="space-y-3">
                                        <div
                                            class="rounded-xl border border-sidebar-border/70 px-4 py-3 text-sm"
                                        >
                                            <p
                                                class="font-medium text-foreground"
                                            >
                                                Tenant details
                                            </p>
                                            <div
                                                class="mt-3 grid gap-2 text-muted-foreground"
                                            >
                                                <p>
                                                    Contact:
                                                    <span
                                                        class="text-foreground"
                                                    >
                                                        {{
                                                            client.contact_email ??
                                                            'Not set'
                                                        }}
                                                    </span>
                                                </p>
                                                <p>
                                                    Website:
                                                    <span
                                                        class="text-foreground"
                                                    >
                                                        {{
                                                            formatWebsite(
                                                                client.website_url,
                                                            )
                                                        }}
                                                    </span>
                                                </p>
                                                <p>
                                                    Monthly token limit:
                                                    <span
                                                        class="text-foreground"
                                                    >
                                                        {{
                                                            formatNumber(
                                                                client.monthly_token_limit,
                                                            )
                                                        }}
                                                    </span>
                                                </p>
                                            </div>
                                        </div>

                                        <div
                                            class="rounded-xl border border-sidebar-border/70 px-4 py-3 text-sm"
                                        >
                                            <div
                                                class="mb-2 flex items-center gap-2"
                                            >
                                                <Code2 class="size-4" />
                                                <p
                                                    class="font-medium text-foreground"
                                                >
                                                    Embed script
                                                </p>
                                            </div>
                                            <pre
                                                class="overflow-x-auto rounded-lg bg-slate-950 px-3 py-3 text-xs text-slate-100"
                                            ><code>{{ embedSnippet(client) }}</code></pre>
                                        </div>

                                        <div
                                            v-if="client.notes"
                                            class="rounded-xl border border-sidebar-border/70 px-4 py-3 text-sm text-muted-foreground"
                                        >
                                            <p
                                                class="font-medium text-foreground"
                                            >
                                                Internal notes
                                            </p>
                                            <p class="mt-2 whitespace-pre-wrap">
                                                {{ client.notes }}
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </article>
                        </div>
                    </CardContent>
                </Card>

                <div class="grid gap-4">
                    <Card class="gap-0 border-sidebar-border/70">
                        <CardHeader
                            class="gap-2 border-b border-sidebar-border/70"
                        >
                            <h2 class="text-xl font-semibold">
                                Admin rollout checklist
                            </h2>
                            <p class="text-sm text-muted-foreground">
                                This dashboard covers the first phase from the
                                docs: tenant creation, widget setup, and usage
                                limits.
                            </p>
                        </CardHeader>
                        <CardContent class="space-y-4 pt-6 text-sm">
                            <div class="rounded-xl border px-4 py-4">
                                <p class="font-medium">1. Create a client</p>
                                <p class="mt-1 text-muted-foreground">
                                    Generate a unique tenant code and connect
                                    the brand website.
                                </p>
                            </div>
                            <div class="rounded-xl border px-4 py-4">
                                <p class="font-medium">
                                    2. Define widget identity
                                </p>
                                <p class="mt-1 text-muted-foreground">
                                    Pick one of the three presets and store the
                                    color palette and greeting message.
                                </p>
                            </div>
                            <div class="rounded-xl border px-4 py-4">
                                <p class="font-medium">3. Assign limits</p>
                                <p class="mt-1 text-muted-foreground">
                                    Set monthly token ceilings so the future RAG
                                    chat runtime can enforce billing guardrails.
                                </p>
                            </div>
                            <div class="rounded-xl border px-4 py-4">
                                <p class="font-medium">
                                    4. Hand off the script
                                </p>
                                <p class="mt-1 text-muted-foreground">
                                    Each client gets an embed snippet ready for
                                    the widget build that comes next.
                                </p>
                            </div>
                        </CardContent>
                    </Card>

                    <Card class="gap-0 border-sidebar-border/70">
                        <CardHeader
                            class="gap-2 border-b border-sidebar-border/70"
                        >
                            <h2 class="text-xl font-semibold">
                                Widget presets
                            </h2>
                            <p class="text-sm text-muted-foreground">
                                The style system follows the docs strategy so
                                the future public widget can reuse the same
                                saved settings.
                            </p>
                        </CardHeader>
                        <CardContent class="space-y-4 pt-6 text-sm">
                            <div
                                v-for="style in widget_styles"
                                :key="style"
                                class="rounded-xl border px-4 py-4"
                            >
                                <div
                                    class="flex items-center justify-between gap-3"
                                >
                                    <p class="font-medium">
                                        {{ widgetLabels[style] ?? style }}
                                    </p>
                                    <Badge variant="outline">
                                        {{
                                            style === 'classic'
                                                ? 'Bubble'
                                                : style === 'modern'
                                                  ? 'Input bar'
                                                  : 'Glass'
                                        }}
                                    </Badge>
                                </div>
                                <p class="mt-1 text-muted-foreground">
                                    {{ widgetDescriptions[style] }}
                                </p>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </section>
        </div>

        <Dialog :open="isDialogOpen" @update:open="handleDialogChange">
            <DialogContent class="sm:max-w-4xl">
                <DialogHeader class="space-y-3">
                    <DialogTitle>
                        {{
                            isEditing
                                ? 'Edit client workspace'
                                : 'Create a new client'
                        }}
                    </DialogTitle>
                    <DialogDescription>
                        Capture the tenant information needed for the admin
                        setup now, then reuse this data when we add ingestion,
                        conversations, and the public widget runtime.
                    </DialogDescription>
                </DialogHeader>

                <form class="space-y-6" @submit.prevent="submitClient">
                    <div class="grid gap-4 md:grid-cols-2">
                        <div class="grid gap-2">
                            <Label for="name">Client name</Label>
                            <Input
                                id="name"
                                v-model="form.name"
                                placeholder="Acme Dental"
                            />
                            <InputError :message="form.errors.name" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="contact_email">Contact email</Label>
                            <Input
                                id="contact_email"
                                v-model="form.contact_email"
                                type="email"
                                placeholder="ops@acme.com"
                            />
                            <InputError :message="form.errors.contact_email" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="website_url">Website URL</Label>
                            <Input
                                id="website_url"
                                v-model="form.website_url"
                                placeholder="https://acme.com"
                            />
                            <InputError :message="form.errors.website_url" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="monthly_token_limit">
                                Monthly token limit
                            </Label>
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

                        <div class="grid gap-2">
                            <Label for="status">Status</Label>
                            <select
                                id="status"
                                v-model="form.status"
                                :class="fieldClass"
                            >
                                <option
                                    v-for="status in client_statuses"
                                    :key="status"
                                    :value="status"
                                >
                                    {{ statusLabels[status] ?? status }}
                                </option>
                            </select>
                            <InputError :message="form.errors.status" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="widget_style">Widget preset</Label>
                            <select
                                id="widget_style"
                                v-model="form.widget_style"
                                :class="fieldClass"
                            >
                                <option
                                    v-for="style in widget_styles"
                                    :key="style"
                                    :value="style"
                                >
                                    {{ widgetLabels[style] ?? style }}
                                </option>
                            </select>
                            <InputError :message="form.errors.widget_style" />
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

                        <div class="grid gap-2">
                            <Label for="position">Widget position</Label>
                            <select
                                id="position"
                                v-model="form.position"
                                :class="fieldClass"
                            >
                                <option
                                    v-for="position in widget_positions"
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
                            <Label for="show_branding">Branding</Label>
                            <label
                                for="show_branding"
                                class="flex min-h-10 items-center gap-3 rounded-md border border-input px-3 py-2 text-sm"
                            >
                                <input
                                    id="show_branding"
                                    v-model="form.show_branding"
                                    type="checkbox"
                                    class="size-4 rounded border-input"
                                />
                                Show platform branding in the widget
                            </label>
                            <InputError :message="form.errors.show_branding" />
                        </div>
                    </div>

                    <div class="grid gap-4">
                        <div class="grid gap-2">
                            <Label for="welcome_message">
                                Welcome message
                            </Label>
                            <textarea
                                id="welcome_message"
                                v-model="form.welcome_message"
                                :class="textAreaClass"
                                placeholder="Ask us anything about appointments, pricing, and support."
                            />
                            <InputError
                                :message="form.errors.welcome_message"
                            />
                        </div>

                        <div class="grid gap-2">
                            <Label for="notes">Internal notes</Label>
                            <textarea
                                id="notes"
                                v-model="form.notes"
                                :class="textAreaClass"
                                placeholder="Sales notes, onboarding details, or implementation reminders."
                            />
                            <InputError :message="form.errors.notes" />
                        </div>
                    </div>

                    <DialogFooter class="gap-2">
                        <Button
                            type="button"
                            variant="outline"
                            @click="closeDialog"
                        >
                            Cancel
                        </Button>
                        <Button type="submit" :disabled="form.processing">
                            {{
                                form.processing
                                    ? 'Saving...'
                                    : isEditing
                                      ? 'Update client'
                                      : 'Create client'
                            }}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    </AppLayout>
</template>
