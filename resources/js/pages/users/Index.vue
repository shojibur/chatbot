<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import {
    Crown,
    Loader2,
    Pencil,
    Plus,
    Search,
    Shield,
    Trash2,
    User as UserIcon,
    Users,
    X,
} from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';
import ClientCombobox from '@/components/ClientCombobox.vue';
import ConfirmDeleteDialog from '@/components/ConfirmDeleteDialog.vue';
import InputError from '@/components/InputError.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
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
import type { BreadcrumbItem } from '@/types';

interface UserRecord {
    id: number;
    name: string;
    email: string;
    user_type: string;
    client_name: string | null;
    created_at: string;
}

interface ClientOption {
    id: number;
    name: string;
}

type Props = {
    users: UserRecord[];
    clients: ClientOption[];
    user_types: string[];
    status?: string;
};

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Users', href: '/users' },
];

// ── Search ────────────────────────────────────────────────────────────────────
const search = ref('');
const filteredUsers = computed(() => {
    if (!search.value.trim()) return props.users;
    const q = search.value.toLowerCase();
    return props.users.filter(
        (u) =>
            u.name.toLowerCase().includes(q) ||
            u.email.toLowerCase().includes(q) ||
            u.user_type.toLowerCase().includes(q) ||
            (u.client_name?.toLowerCase().includes(q) ?? false),
    );
});

const totalAdmins = computed(() => props.users.filter((u) => u.user_type === 'admin').length);
const totalClients = computed(() => props.users.filter((u) => u.user_type === 'client').length);

// ── Status banner ─────────────────────────────────────────────────────────────
const statusMessage = computed(() => {
    if (props.status === 'user-created') return { text: 'User created successfully.', type: 'success' };
    if (props.status === 'user-updated') return { text: 'User updated successfully.', type: 'success' };
    if (props.status === 'user-deleted') return { text: 'User deleted successfully.', type: 'success' };
    if (props.status === 'error-cannot-delete-self') return { text: 'You cannot delete your own account.', type: 'error' };
    return null;
});

// ── Modal state ───────────────────────────────────────────────────────────────
type ModalMode = 'create' | 'edit';
const modalOpen = ref(false);
const modalMode = ref<ModalMode>('create');
const editingUser = ref<UserRecord | null>(null);

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
    user_type: 'client',
    client_id: null as number | null,
});

function openCreate() {
    modalMode.value = 'create';
    editingUser.value = null;
    form.reset();
    form.user_type = 'client';
    form.client_id = null;
    modalOpen.value = true;
}

function openEdit(user: UserRecord) {
    modalMode.value = 'edit';
    editingUser.value = user;
    form.name = user.name;
    form.email = user.email;
    form.password = '';
    form.password_confirmation = '';
    form.user_type = user.user_type;
    const matchedClient = props.clients.find((c) => c.name === user.client_name);
    form.client_id = matchedClient?.id ?? null;
    modalOpen.value = true;
}

function closeModal() {
    modalOpen.value = false;
    form.reset();
    form.clearErrors();
}

function submitForm() {
    if (modalMode.value === 'create') {
        form.post('/users', {
            onSuccess: () => closeModal(),
        });
    } else if (editingUser.value) {
        form.patch(`/users/${editingUser.value.id}`, {
            onSuccess: () => closeModal(),
        });
    }
}

// Auto-clear client_id when switching to admin
watch(() => form.user_type, (type) => {
    if (type === 'admin') form.client_id = '';
});

// ── Delete ─────────────────────────────────────────────────────────────────
const deleteTarget = ref<UserRecord | null>(null);
const deleting = ref(false);

function confirmDelete(user: UserRecord) {
    deleteTarget.value = user;
}

function executeDelete() {
    if (!deleteTarget.value) return;
    deleting.value = true;
    router.delete(`/users/${deleteTarget.value.id}`, {
        onFinish: () => {
            deleting.value = false;
            deleteTarget.value = null;
        },
    });
}

// ── Avatar helpers ────────────────────────────────────────────────────────────
function getInitials(name: string): string {
    return name
        .split(' ')
        .slice(0, 2)
        .map((w) => w[0])
        .join('')
        .toUpperCase();
}

const avatarColors = [
    'from-violet-500 to-purple-600',
    'from-blue-500 to-indigo-600',
    'from-emerald-500 to-teal-600',
    'from-amber-500 to-orange-600',
    'from-rose-500 to-pink-600',
    'from-cyan-500 to-sky-600',
];

function getAvatarColor(id: number): string {
    return avatarColors[id % avatarColors.length];
}
</script>

<template>
    <Head title="Users" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-1 flex-col gap-6 p-4 md:p-6">

            <!-- Header -->
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h1 class="text-2xl font-bold tracking-tight">User Management</h1>
                    <p class="mt-1 text-sm text-muted-foreground">
                        {{ users.length }} total &middot;
                        {{ totalAdmins }} admin{{ totalAdmins !== 1 ? 's' : '' }} &middot;
                        {{ totalClients }} client{{ totalClients !== 1 ? 's' : '' }}
                    </p>
                </div>
                <Button class="shrink-0" @click="openCreate">
                    <Plus class="mr-2 h-4 w-4" />
                    Add User
                </Button>
            </div>

            <!-- Status banner -->
            <div
                v-if="statusMessage"
                class="flex items-center gap-3 rounded-xl border px-4 py-3 text-sm shadow-sm"
                :class="statusMessage.type === 'error'
                    ? 'border-red-200 bg-red-50 text-red-700 dark:border-red-900/30 dark:bg-red-900/10 dark:text-red-400'
                    : 'border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-900/30 dark:bg-emerald-900/10 dark:text-emerald-400'"
            >
                {{ statusMessage.text }}
            </div>

            <!-- Summary cards -->
            <div class="grid gap-4 sm:grid-cols-3">
                <div class="rounded-xl border border-sidebar-border/60 bg-card p-5 shadow-sm">
                    <div class="flex items-center justify-between">
                        <p class="text-xs font-semibold uppercase tracking-wider text-muted-foreground">Total Users</p>
                        <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-primary/10">
                            <Users class="h-4 w-4 text-primary" />
                        </div>
                    </div>
                    <p class="mt-3 text-3xl font-bold">{{ users.length }}</p>
                    <p class="mt-1 text-xs text-muted-foreground">Active logins on the platform</p>
                </div>
                <div class="rounded-xl border border-sidebar-border/60 bg-card p-5 shadow-sm">
                    <div class="flex items-center justify-between">
                        <p class="text-xs font-semibold uppercase tracking-wider text-muted-foreground">Admins</p>
                        <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-amber-500/10">
                            <Crown class="h-4 w-4 text-amber-600 dark:text-amber-400" />
                        </div>
                    </div>
                    <p class="mt-3 text-3xl font-bold">{{ totalAdmins }}</p>
                    <p class="mt-1 text-xs text-muted-foreground">Full system access</p>
                </div>
                <div class="rounded-xl border border-sidebar-border/60 bg-card p-5 shadow-sm">
                    <div class="flex items-center justify-between">
                        <p class="text-xs font-semibold uppercase tracking-wider text-muted-foreground">Clients</p>
                        <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-emerald-500/10">
                            <Shield class="h-4 w-4 text-emerald-600 dark:text-emerald-400" />
                        </div>
                    </div>
                    <p class="mt-3 text-3xl font-bold">{{ totalClients }}</p>
                    <p class="mt-1 text-xs text-muted-foreground">Portal-only access</p>
                </div>
            </div>

            <!-- User list card -->
            <div class="overflow-hidden rounded-xl border border-sidebar-border/60 bg-card shadow-sm">
                <!-- Search bar -->
                <div class="border-b border-sidebar-border/60 px-4 py-3">
                    <div class="relative max-w-sm">
                        <Search class="absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-muted-foreground/60" />
                        <Input v-model="search" placeholder="Search by name, email or role..." class="pl-9 h-8 text-sm" />
                    </div>
                </div>

                <!-- Column headers -->
                <div class="hidden border-b border-sidebar-border/50 bg-muted/20 px-6 py-2.5 text-[10px] font-bold tracking-widest text-muted-foreground uppercase md:grid md:grid-cols-[2.5fr_1fr_1.5fr_1.5fr_auto]">
                    <span>User</span>
                    <span>Role</span>
                    <span>Client</span>
                    <span>Joined</span>
                    <span class="text-right w-20">Actions</span>
                </div>

                <!-- Rows -->
                <div class="divide-y divide-sidebar-border/40">
                    <div
                        v-for="user in filteredUsers"
                        :key="user.id"
                        class="group grid grid-cols-1 items-center gap-4 px-6 py-4 transition-colors hover:bg-muted/20 md:grid-cols-[2.5fr_1fr_1.5fr_1.5fr_auto]"
                    >
                        <!-- Avatar + name -->
                        <div class="flex items-center gap-3 min-w-0">
                            <div
                                class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-gradient-to-br text-xs font-bold text-white shadow-sm"
                                :class="getAvatarColor(user.id)"
                            >
                                {{ getInitials(user.name) }}
                            </div>
                            <div class="min-w-0">
                                <p class="truncate text-sm font-semibold">{{ user.name }}</p>
                                <p class="truncate text-xs text-muted-foreground">{{ user.email }}</p>
                            </div>
                        </div>

                        <!-- Role -->
                        <div>
                            <Badge
                                variant="outline"
                                class="h-5 px-2 text-[9px] font-bold uppercase tracking-widest gap-1"
                                :class="user.user_type === 'admin'
                                    ? 'border-amber-200 bg-amber-50 text-amber-700 dark:border-amber-900/40 dark:bg-amber-900/20 dark:text-amber-400'
                                    : 'border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-900/40 dark:bg-emerald-900/20 dark:text-emerald-400'"
                            >
                                <Crown v-if="user.user_type === 'admin'" class="h-2.5 w-2.5" />
                                <Shield v-else class="h-2.5 w-2.5" />
                                {{ user.user_type }}
                            </Badge>
                        </div>

                        <!-- Client -->
                        <div class="text-sm min-w-0">
                            <span v-if="user.client_name" class="truncate block font-medium">{{ user.client_name }}</span>
                            <span v-else class="text-xs italic text-muted-foreground/60">System-wide</span>
                        </div>

                        <!-- Joined -->
                        <div class="text-xs text-muted-foreground tabular-nums">{{ user.created_at }}</div>

                        <!-- Actions -->
                        <div class="flex w-20 items-center justify-end gap-1">
                            <Button
                                variant="ghost"
                                size="icon"
                                class="h-8 w-8 opacity-0 transition-opacity group-hover:opacity-100 text-muted-foreground hover:text-foreground"
                                @click="openEdit(user)"
                            >
                                <Pencil class="h-3.5 w-3.5" />
                            </Button>
                            <Button
                                variant="ghost"
                                size="icon"
                                class="h-8 w-8 opacity-0 transition-opacity group-hover:opacity-100 text-muted-foreground hover:bg-red-50 hover:text-red-600 dark:hover:bg-red-900/20"
                                @click="confirmDelete(user)"
                            >
                                <Trash2 class="h-3.5 w-3.5" />
                            </Button>
                        </div>
                    </div>

                    <!-- Empty state -->
                    <div v-if="filteredUsers.length === 0" class="flex flex-col items-center justify-center py-16 text-center">
                        <div class="flex h-14 w-14 items-center justify-center rounded-full bg-muted mb-4">
                            <UserIcon class="h-6 w-6 text-muted-foreground/40" />
                        </div>
                        <p class="text-sm font-medium text-muted-foreground">
                            {{ search ? 'No users match your search.' : 'No users yet.' }}
                        </p>
                        <p v-if="!search" class="mt-1 text-xs text-muted-foreground/70">Click "Add User" to get started.</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- ── Create / Edit Modal ──────────────────────────────────────────── -->
        <Dialog :open="modalOpen" @update:open="modalOpen = $event">
            <DialogContent class="sm:max-w-lg">
                <DialogHeader>
                    <DialogTitle class="flex items-center gap-2">
                        <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-primary/10">
                            <UserIcon class="h-4 w-4 text-primary" />
                        </div>
                        {{ modalMode === 'create' ? 'Add New User' : `Edit ${editingUser?.name}` }}
                    </DialogTitle>
                    <DialogDescription>
                        {{ modalMode === 'create'
                            ? 'Create a new admin or client portal account.'
                            : 'Update account details. Leave the password blank to keep the current one.' }}
                    </DialogDescription>
                </DialogHeader>

                <form @submit.prevent="submitForm" class="grid gap-5 py-2">
                    <!-- Name + Email -->
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div class="grid gap-1.5">
                            <Label for="modal-name">Full Name</Label>
                            <Input id="modal-name" v-model="form.name" placeholder="Jane Smith" autocomplete="off" />
                            <InputError :message="form.errors.name" />
                        </div>
                        <div class="grid gap-1.5">
                            <Label for="modal-email">Email Address</Label>
                            <Input id="modal-email" type="email" v-model="form.email" placeholder="jane@example.com" autocomplete="off" />
                            <InputError :message="form.errors.email" />
                        </div>
                    </div>

                    <!-- Passwords -->
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div class="grid gap-1.5">
                            <Label for="modal-password">
                                {{ modalMode === 'create' ? 'Password' : 'New Password' }}
                            </Label>
                            <Input id="modal-password" type="password" v-model="form.password"
                                :placeholder="modalMode === 'edit' ? 'Leave blank to keep current' : ''"
                                autocomplete="new-password" />
                            <InputError :message="form.errors.password" />
                        </div>
                        <div class="grid gap-1.5">
                            <Label for="modal-confirm">Confirm Password</Label>
                            <Input id="modal-confirm" type="password" v-model="form.password_confirmation" autocomplete="new-password" />
                        </div>
                    </div>

                    <!-- Divider -->
                    <div class="border-t border-sidebar-border/40 pt-1">
                        <p class="text-xs font-semibold uppercase tracking-wider text-muted-foreground mb-3">Access & Permissions</p>
                        <div class="grid gap-4 sm:grid-cols-2">
                            <!-- Role selector -->
                            <div class="grid gap-1.5">
                                <Label for="modal-type">User Role</Label>
                                <div class="grid grid-cols-2 gap-2">
                                    <button
                                        type="button"
                                        @click="form.user_type = 'admin'"
                                        class="flex items-center gap-2 rounded-lg border px-3 py-2.5 text-sm transition-all"
                                        :class="form.user_type === 'admin'
                                            ? 'border-amber-400 bg-amber-50 text-amber-700 dark:border-amber-600 dark:bg-amber-900/20 dark:text-amber-300'
                                            : 'border-sidebar-border/60 text-muted-foreground hover:border-sidebar-border'"
                                    >
                                        <Crown class="h-4 w-4" />
                                        <span class="font-medium">Admin</span>
                                    </button>
                                    <button
                                        type="button"
                                        @click="form.user_type = 'client'"
                                        class="flex items-center gap-2 rounded-lg border px-3 py-2.5 text-sm transition-all"
                                        :class="form.user_type === 'client'
                                            ? 'border-emerald-400 bg-emerald-50 text-emerald-700 dark:border-emerald-600 dark:bg-emerald-900/20 dark:text-emerald-300'
                                            : 'border-sidebar-border/60 text-muted-foreground hover:border-sidebar-border'"
                                    >
                                        <Shield class="h-4 w-4" />
                                        <span class="font-medium">Client</span>
                                    </button>
                                </div>
                                <InputError :message="form.errors.user_type" />
                            </div>

                            <!-- Client selector — searchable combobox for large lists -->
                            <Transition enter-from-class="opacity-0 translate-x-2" enter-active-class="transition-all duration-200" leave-to-class="opacity-0 translate-x-2" leave-active-class="transition-all duration-150">
                                <div v-if="form.user_type === 'client'" class="grid gap-1.5">
                                    <Label>Assign to Client</Label>
                                    <ClientCombobox
                                        v-model="form.client_id"
                                        :clients="clients"
                                        placeholder="Search and select a client…"
                                    />
                                    <p class="text-[10px] text-muted-foreground">Type to search across all clients.</p>
                                    <InputError :message="form.errors.client_id" />
                                </div>
                            </Transition>
                        </div>
                    </div>
                </form>

                <DialogFooter class="gap-2">
                    <Button variant="outline" type="button" @click="closeModal">
                        <X class="mr-1.5 h-4 w-4" />
                        Cancel
                    </Button>
                    <Button type="submit" :disabled="form.processing" @click="submitForm">
                        <Loader2 v-if="form.processing" class="mr-1.5 h-4 w-4 animate-spin" />
                        {{ modalMode === 'create' ? 'Create User' : 'Save Changes' }}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <!-- Delete confirm -->
        <ConfirmDeleteDialog
            :open="!!deleteTarget"
            title="Delete User Account"
            :description="`Are you sure you want to permanently remove '${deleteTarget?.name}'? They will immediately lose all access.`"
            :processing="deleting"
            @close="deleteTarget = null"
            @confirm="executeDelete"
        />
    </AppLayout>
</template>
