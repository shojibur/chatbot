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
    <img src="/logodark.png" alt="Zaochat Logo" :class="[className, 'block dark:hidden']" v-bind="$attrs" />
    <img src="/logowhite.png" alt="Zaochat Logo" :class="[className, 'hidden dark:block']" v-bind="$attrs" />
</template>
