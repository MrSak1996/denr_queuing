<script setup lang="ts">
import type { HTMLAttributes } from 'vue';
import denr_logo from '../../images/denr_logo.png';


defineOptions({
    inheritAttrs: false,
});

interface Props {
    className?: HTMLAttributes['class'];
}

defineProps<Props>();
</script>

<template>
    <img :src="denr_logo" alt="DENR Logo" class="h-full w-full object-cover" />
</template>
