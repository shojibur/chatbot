<script setup lang="ts">
/**
 * ClientCombobox — searchable dropdown for selecting a client.
 * Handles large lists efficiently by filtering client-side on the visible items.
 * Keyboard accessible: ArrowUp/ArrowDown to navigate, Enter to select, Escape to close.
 */
import { Building2, Check, ChevronsUpDown, Search, X } from 'lucide-vue-next';
import { computed, nextTick, onMounted, onUnmounted, ref, watch } from 'vue';

interface Client {
    id: number;
    name: string;
}

const props = defineProps<{
    modelValue: number | string | null;
    clients: Client[];
    placeholder?: string;
    disabled?: boolean;
}>();

const emit = defineEmits<{
    'update:modelValue': [value: number | null];
}>();

const open = ref(false);
const query = ref('');
const highlightIndex = ref(-1);
const triggerRef = ref<HTMLButtonElement | null>(null);
const inputRef = ref<HTMLInputElement | null>(null);
const listRef = ref<HTMLUListElement | null>(null);

const selectedClient = computed(() =>
    props.clients.find((c) => c.id === Number(props.modelValue)) ?? null,
);

const filtered = computed(() => {
    if (!query.value.trim()) return props.clients.slice(0, 200); // cap initial render
    const q = query.value.toLowerCase();
    return props.clients.filter((c) => c.name.toLowerCase().includes(q)).slice(0, 200);
});

function openDropdown() {
    if (props.disabled) return;
    open.value = true;
    query.value = '';
    highlightIndex.value = -1;
    nextTick(() => inputRef.value?.focus());
}

function closeDropdown() {
    open.value = false;
    query.value = '';
}

function selectClient(client: Client | null) {
    emit('update:modelValue', client?.id ?? null);
    closeDropdown();
}

function clearSelection(e: MouseEvent) {
    e.stopPropagation();
    emit('update:modelValue', null);
}

function onKeydown(event: KeyboardEvent) {
    if (!open.value) return;
    if (event.key === 'Escape') {
        closeDropdown();
        triggerRef.value?.focus();
        return;
    }
    if (event.key === 'ArrowDown') {
        event.preventDefault();
        highlightIndex.value = Math.min(highlightIndex.value + 1, filtered.value.length - 1);
        scrollHighlighted();
        return;
    }
    if (event.key === 'ArrowUp') {
        event.preventDefault();
        highlightIndex.value = Math.max(highlightIndex.value - 1, 0);
        scrollHighlighted();
        return;
    }
    if (event.key === 'Enter') {
        event.preventDefault();
        if (highlightIndex.value >= 0 && filtered.value[highlightIndex.value]) {
            selectClient(filtered.value[highlightIndex.value]);
        }
    }
}

function scrollHighlighted() {
    nextTick(() => {
        const item = listRef.value?.children[highlightIndex.value] as HTMLElement | undefined;
        item?.scrollIntoView({ block: 'nearest' });
    });
}

// Reset highlight when filter changes
watch(query, () => (highlightIndex.value = -1));

// Close on outside click
function handleOutsideClick(e: MouseEvent) {
    const el = (e.target as Node);
    if (triggerRef.value?.contains(el) || listRef.value?.parentElement?.contains(el)) return;
    closeDropdown();
}

onMounted(() => document.addEventListener('mousedown', handleOutsideClick));
onUnmounted(() => document.removeEventListener('mousedown', handleOutsideClick));
</script>

<template>
    <div class="relative w-full" @keydown="onKeydown">
        <!-- Trigger button -->
        <button
            ref="triggerRef"
            type="button"
            :disabled="disabled"
            class="flex h-10 w-full items-center justify-between gap-2 rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-sm transition-colors outline-none focus:ring-1 focus:ring-ring disabled:cursor-not-allowed disabled:opacity-50"
            :class="open ? 'ring-1 ring-ring border-ring' : ''"
            @click="openDropdown"
        >
            <span v-if="selectedClient" class="flex items-center gap-2 truncate">
                <Building2 class="h-3.5 w-3.5 shrink-0 text-muted-foreground" />
                <span class="truncate font-medium">{{ selectedClient.name }}</span>
            </span>
            <span v-else class="text-muted-foreground">{{ placeholder ?? 'Select a client…' }}</span>
            <div class="flex shrink-0 items-center gap-1">
                <button
                    v-if="selectedClient && !disabled"
                    type="button"
                    class="rounded-full p-0.5 text-muted-foreground/60 hover:text-foreground transition-colors"
                    @click="clearSelection"
                >
                    <X class="h-3 w-3" />
                </button>
                <ChevronsUpDown class="h-4 w-4 text-muted-foreground/60" />
            </div>
        </button>

        <!-- Dropdown panel -->
        <Transition
            enter-from-class="opacity-0 scale-y-95 -translate-y-1"
            enter-active-class="transition-all duration-150 ease-out origin-top"
            leave-to-class="opacity-0 scale-y-95 -translate-y-1"
            leave-active-class="transition-all duration-100 ease-in origin-top"
        >
            <div
                v-if="open"
                class="absolute z-50 mt-1 w-full overflow-hidden rounded-lg border border-sidebar-border/70 bg-popover shadow-lg"
            >
                <!-- Search input -->
                <div class="flex items-center border-b border-sidebar-border/50 px-3 py-2">
                    <Search class="mr-2 h-4 w-4 shrink-0 text-muted-foreground/60" />
                    <input
                        ref="inputRef"
                        v-model="query"
                        type="text"
                        class="flex-1 bg-transparent text-sm outline-none placeholder:text-muted-foreground/50"
                        placeholder="Search clients…"
                    />
                    <span class="text-[10px] text-muted-foreground/50 ml-2 tabular-nums">
                        {{ filtered.length }}{{ filtered.length === 200 ? '+' : '' }}
                    </span>
                </div>

                <!-- List -->
                <ul ref="listRef" class="max-h-56 overflow-y-auto py-1">
                    <!-- Clear option -->
                    <li
                        class="flex cursor-pointer items-center gap-2 px-3 py-2 text-sm text-muted-foreground transition-colors hover:bg-accent"
                        @click="selectClient(null)"
                    >
                        <X class="h-3.5 w-3.5" />
                        <span class="italic">No client (admin only)</span>
                    </li>

                    <li
                        v-for="(client, i) in filtered"
                        :key="client.id"
                        class="flex cursor-pointer items-center gap-2 px-3 py-2 text-sm transition-colors"
                        :class="[
                            i === highlightIndex ? 'bg-accent' : 'hover:bg-accent/60',
                            client.id === Number(modelValue) ? 'font-medium text-foreground' : 'text-foreground/80',
                        ]"
                        @click="selectClient(client)"
                        @mousemove="highlightIndex = i"
                    >
                        <Check
                            class="h-3.5 w-3.5 shrink-0"
                            :class="client.id === Number(modelValue) ? 'opacity-100 text-primary' : 'opacity-0'"
                        />
                        <Building2 class="h-3.5 w-3.5 shrink-0 text-muted-foreground/60" />
                        <span class="truncate">{{ client.name }}</span>
                    </li>

                    <li v-if="filtered.length === 0" class="px-3 py-6 text-center text-sm text-muted-foreground">
                        No clients found for "{{ query }}"
                    </li>
                </ul>

                <!-- Footer hint -->
                <div class="border-t border-sidebar-border/40 px-3 py-1.5 text-[10px] text-muted-foreground/50">
                    ↑↓ navigate &nbsp;&middot;&nbsp; Enter select &nbsp;&middot;&nbsp; Esc close
                </div>
            </div>
        </Transition>
    </div>
</template>
