<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
// You can leave this import here just in case, or delete it if you strictly use the <img> tag
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import { home } from '@/routes';

defineProps<{
    title?: string;
    description?: string;
}>();
</script>

<template>
    <div
        class="flex min-h-svh flex-col items-center justify-center gap-6 bg-slate-950 text-white p-6 md:p-10"
    >
        <div class="w-full max-w-sm">
            <div class="flex flex-col gap-8">
                
                <div class="flex flex-col items-center gap-4">
                    <Link
                        :href="home()"
                        class="flex flex-col items-center gap-2 font-medium focus:outline-none"
                    >
                        <img src="/logodark.png" alt="Zao Chat" class="h-10 w-auto mb-2 dark:hidden" />
                        <img src="/logowhite.png" alt="Zao Chat" class="h-10 w-auto mb-2 hidden dark:block" />

                        <span class="sr-only">{{ title }}</span>
                    </Link>
                    
                    <div class="space-y-2 text-center">
                        <h1 class="text-2xl font-semibold tracking-tight">{{ title }}</h1>
                        <p class="text-center text-sm text-slate-400">
                            {{ description }}
                        </p>
                    </div>
                </div>
                
                <slot />
                
            </div>
        </div>
    </div>
</template>