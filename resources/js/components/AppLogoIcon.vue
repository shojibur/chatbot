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
    <img src="/logodark.png" alt="Zao Chat Logo" :class="[className, 'block dark:hidden object-contain']" v-bind="$attrs" />
    <img src="/logowhite.png" alt="Zao Chat Logo" :class="[className, 'hidden dark:block object-contain']" v-bind="$attrs" />
</template>
