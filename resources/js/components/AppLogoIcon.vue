<script setup lang="ts">
import type { HTMLAttributes } from 'vue';

defineOptions({
    inheritAttrs: false,
});

type Props = {
    className?: HTMLAttributes['class'];
};

defineProps<Props>();
</script>

<template>
    <svg
        xmlns="http://www.w3.org/2000/svg"
        viewBox="0 0 24 24"
        fill="none"
        :class="className"
        v-bind="$attrs"
    >
        <path
            fill="currentColor"
            d="M12 2C6.48 2 2 5.58 2 10c0 2.24 1.12 4.27 2.94 5.72-.18 1.34-.78 2.6-1.72 3.56a.5.5 0 0 0 .36.85c2.17-.05 4.12-.88 5.54-2.17.6.1 1.23.15 1.88.15 5.52 0 10-3.58 10-8s-4.48-8-10-8Zm-3 9.5a1.5 1.5 0 1 1 0-3 1.5 1.5 0 0 1 0 3Zm3 0a1.5 1.5 0 1 1 0-3 1.5 1.5 0 0 1 0 3Zm3 0a1.5 1.5 0 1 1 0-3 1.5 1.5 0 0 1 0 3Z"
        />
    </svg>
</template>
