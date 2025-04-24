<script lang="ts" setup>
import { FolderSync, SkipForward, User } from 'lucide-vue-next';
import { onMounted, ref } from 'vue';

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
</script>

<template>
    <div class="col-span-1 flex flex-col justify-start gap-4 rounded-lg border bg-gray-50 p-4 shadow">
        <div class="grid w-full grid-cols-1 gap-5 p-4 text-center sm:grid-cols-2 lg:grid-cols-4">
            <div class="button-action flex-row items-center gap-4 p-6">
                <User class="h-20 w-20 flex-shrink-0 text-green-400" />
                <div class="flex flex-col text-left">
                    <span class="mb-1 text-7xl font-bold">150</span>
                    <span class="text-xs leading-tight text-white">TOTAL CLIENT SERVED</span>
                </div>
            </div>
            <div class="button-action flex-row items-center gap-4 p-6">
                <SkipForward class="h-20 w-20 flex-shrink-0 text-green-400" />
                <div class="flex flex-col text-left">
                    <span class="mb-1 text-7xl font-bold">150</span>
                    <span class="text-xs leading-tight text-white">TOTAL SKIPPED CLIENT</span>
                </div>
            </div>
            <div class="button-action flex-row items-center gap-4 p-6">
                <FolderSync class="h-20 w-20 flex-shrink-0 text-green-400" />
                <div class="flex flex-col text-left">
                    <span class="mb-1 text-7xl font-bold">150</span>
                    <span class="text-xs leading-tight text-white">TOTAL TRANSFER CLIENT</span>
                </div>
            </div>
            <div class="button-action flex-row items-center gap-4 p-6">
                <FolderSync class="h-20 w-20 flex-shrink-0 text-green-400" />
                <div class="flex flex-col text-left">
                    <span class="mb-1 text-7xl font-bold">150</span>
                    <span class="text-xs leading-tight text-white">HINDI KOPA ALAM ILALAGAY</span>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.button-action {
    @apply flex rounded-xl bg-[#061a6f] text-sm font-bold text-white shadow-md transition-all hover:brightness-110;
}
</style>
