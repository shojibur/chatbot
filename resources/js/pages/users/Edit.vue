<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ChevronLeft, Loader2 } from 'lucide-vue-next';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';

type Props = {
    user: {
        id: number;
        name: string;
        email: string;
        user_type: string;
        client_id: number | null;
    };
    clients: { id: number; name: string }[];
    user_types: string[];
};

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Users', href: '/users' },
    { title: `Edit ${props.user.name}`, href: `/users/${props.user.id}/edit` },
];

const form = useForm({
    name: props.user.name,
    email: props.user.email,
    password: '',
    password_confirmation: '',
    user_type: props.user.user_type,
    client_id: props.user.client_id || '',
});

function submit() {
    form.patch(`/users/${props.user.id}`);
}
</script>

<template>
    <Head :title="`Edit User: ${user.name}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-1 flex-col gap-4 p-4 md:p-6">
            <div class="flex items-center gap-4">
                <Button variant="outline" size="icon" as-child>
                    <Link href="/users">
                        <ChevronLeft class="h-4 w-4" />
                    </Link>
                </Button>
                <div>
                    <h1 class="text-2xl font-bold tracking-tight">Edit User</h1>
                    <p class="text-sm text-muted-foreground">Modify account details or reset passwords.</p>
                </div>
            </div>

            <Card class="mx-auto w-full max-w-2xl">
                <CardHeader>
                    <h2 class="text-lg font-semibold">User Details</h2>
                </CardHeader>
                <CardContent>
                    <form @submit.prevent="submit" class="grid gap-6">
                        <div class="grid gap-2">
                            <Label for="name">Name</Label>
                            <Input id="name" v-model="form.name" />
                            <InputError :message="form.errors.name" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="email">Email Address</Label>
                            <Input id="email" type="email" v-model="form.email" />
                            <InputError :message="form.errors.email" />
                        </div>

                        <div class="rounded-lg bg-muted/50 p-4">
                            <h3 class="mb-3 text-sm font-semibold">Security</h3>
                            <div class="grid gap-4 sm:grid-cols-2">
                                <div class="grid gap-2">
                                    <Label for="password">New Password (Optional)</Label>
                                    <Input id="password" type="password" v-model="form.password" placeholder="Leave blank to keep current" />
                                    <InputError :message="form.errors.password" />
                                </div>
                                <div class="grid gap-2">
                                    <Label for="password_confirmation">Confirm Password</Label>
                                    <Input id="password_confirmation" type="password" v-model="form.password_confirmation" />
                                </div>
                            </div>
                        </div>

                        <div class="grid gap-4 sm:grid-cols-2">
                            <div class="grid gap-2">
                                <Label for="user_type">User Type</Label>
                                <select
                                    id="user_type"
                                    v-model="form.user_type"
                                    class="h-10 rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-sm transition-colors outline-none focus:ring-1 focus:ring-ring"
                                >
                                    <option v-for="type in user_types" :key="type" :value="type" class="capitalize">{{ type }}</option>
                                </select>
                                <InputError :message="form.errors.user_type" />
                            </div>

                            <div v-if="form.user_type === 'client'" class="grid gap-2">
                                <Label for="client_id">Assign to Client</Label>
                                <select
                                    id="client_id"
                                    v-model="form.client_id"
                                    class="h-10 rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-sm transition-colors outline-none focus:ring-1 focus:ring-ring"
                                >
                                    <option value="">Select a client...</option>
                                    <option v-for="client in clients" :key="client.id" :value="client.id">{{ client.name }}</option>
                                </select>
                                <InputError :message="form.errors.client_id" />
                            </div>
                        </div>

                        <div class="flex justify-end gap-3">
                            <Button variant="outline" type="button" as-child>
                                <Link href="/users">Cancel</Link>
                            </Button>
                            <Button type="submit" :disabled="form.processing">
                                <Loader2 v-if="form.processing" class="mr-2 h-4 w-4 animate-spin" />
                                Save Changes
                            </Button>
                        </div>
                    </form>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
