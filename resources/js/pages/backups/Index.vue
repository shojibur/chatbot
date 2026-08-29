<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import { DatabaseBackup, RefreshCw, Trash2 } from 'lucide-vue-next';
import { ref } from 'vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader } from '@/components/ui/card';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';

type BackupRecord = {
    filename: string;
    size: string;
    size_bytes: number;
    created_at: string;
};

const props = defineProps<{
    backups: BackupRecord[];
    status?: string;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Backups', href: '/backups' },
];

const running = ref(false);
const deletingFile = ref<string | null>(null);

function runBackup() {
    running.value = true;
    router.post('/backups/run', {}, {
        onFinish: () => { running.value = false; },
    });
}

function deleteBackup(filename: string) {
    if (!confirm(`Delete backup ${filename}?`)) return;
    deletingFile.value = filename;
    router.delete(`/backups/${encodeURIComponent(filename)}`, {
        onFinish: () => { deletingFile.value = null; },
    });
}
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Backups" />

        <div class="space-y-6 p-6">
            <Card class="gap-0 border-sidebar-border/70">
                <CardHeader class="border-b border-sidebar-border/70">
                    <div class="flex items-center justify-between">
                        <div class="flex items-start gap-3">
                            <div class="rounded-2xl border border-sidebar-border/70 p-3">
                                <DatabaseBackup class="size-5" />
                            </div>
                            <div>
                                <h2 class="text-xl font-semibold">Database backups</h2>
                                <p class="text-sm text-muted-foreground">
                                    Daily backups — last 7 days kept automatically.
                                </p>
                            </div>
                        </div>
                        <Button :disabled="running" @click="runBackup">
                            <RefreshCw :class="['size-4 mr-2', running ? 'animate-spin' : '']" />
                            {{ running ? 'Running…' : 'Run backup now' }}
                        </Button>
                    </div>
                    <div
                        v-if="status === 'backup-started'"
                        class="mt-3 rounded-lg bg-green-50 px-4 py-2 text-sm text-green-700 dark:bg-green-950 dark:text-green-300"
                    >
                        Backup started successfully.
                    </div>
                    <div
                        v-if="status === 'backup-deleted'"
                        class="mt-3 rounded-lg bg-amber-50 px-4 py-2 text-sm text-amber-700 dark:bg-amber-950 dark:text-amber-300"
                    >
                        Backup deleted.
                    </div>
                </CardHeader>
                <CardContent class="pt-4">
                    <div v-if="backups.length === 0" class="py-12 text-center text-sm text-muted-foreground">
                        No backups yet. Click "Run backup now" to create the first one.
                    </div>
                    <table v-else class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-sidebar-border/70 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">
                                <th class="pb-3 pr-4">Filename</th>
                                <th class="pb-3 pr-4">Created</th>
                                <th class="pb-3 pr-4">Size</th>
                                <th class="pb-3 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-sidebar-border/40">
                            <tr v-for="backup in backups" :key="backup.filename" class="py-3">
                                <td class="py-3 pr-4 font-mono text-xs">{{ backup.filename }}</td>
                                <td class="py-3 pr-4 text-muted-foreground">{{ backup.created_at }}</td>
                                <td class="py-3 pr-4 text-muted-foreground">{{ backup.size }}</td>
                                <td class="py-3 text-right">
                                    <Button
                                        variant="ghost"
                                        size="sm"
                                        :disabled="deletingFile === backup.filename"
                                        class="text-destructive hover:text-destructive"
                                        @click="deleteBackup(backup.filename)"
                                    >
                                        <Trash2 class="size-4" />
                                    </Button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
