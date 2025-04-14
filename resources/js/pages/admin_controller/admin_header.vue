<script lang="ts">
import { onMounted, ref } from 'vue';

export default {
    setup() {
        const currentDateTime = ref('');
        const updateDateTime = () => {
            const now = new Date();
            const options: Intl.DateTimeFormatOptions = {
                weekday: 'long',
                year: 'numeric',
                month: 'long',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit',
            };
            currentDateTime.value = now.toLocaleDateString('en-US', options);
        };

        onMounted(() => {
            updateDateTime();
            setInterval(updateDateTime, 1000);
        });

        return {
            currentDateTime,
        };
    },
};
</script>
<template>
    <div class="col-span-1 flex flex-col justify-start gap-4 rounded-lg bg-gray-50 shadow border rounded p-4">
        <div class="grid w-full grid-cols-4 gap-5 p-4 text-center md:w-full">
            <div class="button-action">
                <span class="mb-1 text-5xl ">150</span>
                <span class="text-center text-xs leading-tight ">TOTAL CLIENT SERVED</span>
            </div>
            <div class="button-action">
                <span class="mb-1 text-5xl">150</span>
                <span class="text-center text-xs leading-tight">TOTAL SKIPPED CLIENT</span>
            </div>
            <div class="button-action">
                <span class="mb-1 text-5xl">150</span>
                <span class="text-center text-xs leading-tight">TOTAL TRANSFER CLIENT</span>
            </div>
            <div class="button-action">
                <span class="mb-1 text-5xl">150</span>
                <span class="text-center text-xs leading-tight"> EXPECTED WAITING TIME</span>
            </div>
            
        </div>
    </div>
</template>
<style scoped>
.button-action {
    @apply flex h-24 flex-col items-center justify-center rounded-xl bg-gradient-to-b from-[#2E4156] to-[#1A2D42] p-3 text-sm font-bold text-white shadow-md transition-all hover:brightness-110;
}
</style>
