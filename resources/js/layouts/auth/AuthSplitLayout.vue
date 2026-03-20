<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import { home } from '@/routes';

defineProps<{
    title?: string;
    description?: string;
}>();
</script>

<template>
    <div class="flex min-h-svh">
        <!-- Left Panel - Branding -->
        <div class="relative hidden w-1/2 overflow-hidden bg-gradient-to-br from-slate-950 via-blue-950 to-slate-950 lg:flex lg:flex-col lg:justify-between">
            <!-- Background decoration -->
            <div class="absolute inset-0 overflow-hidden">
                <div class="absolute -top-32 -left-32 h-[500px] w-[500px] rounded-full bg-blue-500/8 blur-3xl"></div>
                <div class="absolute bottom-0 right-0 h-[400px] w-[400px] rounded-full bg-cyan-500/6 blur-3xl"></div>
                <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 h-[300px] w-[300px] rounded-full bg-indigo-500/5 blur-3xl"></div>
            </div>

            <!-- Top: Logo -->
            <div class="relative z-10 p-10">
                <Link :href="home()" class="flex items-center gap-3">
                    <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-gradient-to-br from-blue-500 to-cyan-400 shadow-lg shadow-blue-500/20">
                        <AppLogoIcon class="size-5 text-white" />
                    </div>
                    <span class="text-xl font-bold tracking-tight text-white">HawaiiChatBot</span>
                </Link>
            </div>

            <!-- Center: Tagline -->
            <div class="relative z-10 px-10">
                <h2 class="text-3xl font-bold leading-tight tracking-tight text-white xl:text-4xl">
                    Turn your knowledge into an
                    <span class="bg-gradient-to-r from-blue-400 via-cyan-400 to-blue-300 bg-clip-text text-transparent">
                        instant AI assistant
                    </span>
                </h2>
                <p class="mt-4 max-w-md text-base leading-relaxed text-slate-400">
                    Your custom chatbot that answers customer questions 24/7 using your own business content.
                </p>

                <!-- Mini chat preview -->
                <div class="mt-10 max-w-sm">
                    <div class="rounded-xl border border-white/10 bg-white/[0.04] p-4 backdrop-blur-sm">
                        <div class="space-y-3">
                            <div class="flex justify-end">
                                <div class="rounded-xl rounded-br-sm bg-gradient-to-r from-blue-500 to-blue-600 px-3.5 py-2 text-xs text-white shadow-sm">
                                    What are your hours?
                                </div>
                            </div>
                            <div class="flex justify-start">
                                <div class="rounded-xl rounded-bl-sm border border-white/5 bg-slate-800/60 px-3.5 py-2 text-xs leading-relaxed text-slate-300">
                                    We're open Mon-Fri, 9 AM to 5 PM HST.
                                </div>
                            </div>
                            <div class="flex items-center gap-1.5">
                                <div class="h-1.5 w-1.5 animate-pulse rounded-full bg-green-400"></div>
                                <span class="text-[10px] text-slate-500">Powered by your knowledge base</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Bottom: Footer -->
            <div class="relative z-10 p-10">
                <p class="text-xs text-slate-500">
                    &copy; {{ new Date().getFullYear() }} HawaiiChatBot. All rights reserved.
                </p>
            </div>
        </div>

        <!-- Right Panel - Form -->
        <div class="flex w-full flex-col items-center justify-center bg-background p-6 md:p-10 lg:w-1/2">
            <!-- Mobile logo -->
            <div class="mb-8 lg:hidden">
                <Link :href="home()" class="flex items-center gap-3">
                    <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-gradient-to-br from-blue-500 to-cyan-400 shadow-lg shadow-blue-500/20">
                        <AppLogoIcon class="size-5 text-white" />
                    </div>
                    <span class="text-xl font-bold tracking-tight">HawaiiChatBot</span>
                </Link>
            </div>

            <div class="w-full max-w-sm">
                <div class="flex flex-col gap-8">
                    <div class="space-y-2">
                        <h1 class="text-2xl font-semibold tracking-tight">{{ title }}</h1>
                        <p class="text-sm text-muted-foreground">
                            {{ description }}
                        </p>
                    </div>
                    <slot />
                </div>
            </div>
        </div>
    </div>
</template>
