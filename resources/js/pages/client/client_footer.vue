<script lang="ts" setup>
import { onMounted, ref } from 'vue';
import denr_logo from '../../../images/denr_logo.png';
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
})
</script>
<template>
    <div class="grid grid-cols-4 gap-4">
                <div class="col-span-4 flex items-center justify-between rounded border-t-[5px] border-green-600 bg-blue-950 px-6 py-4">
                    <!-- Left: Logo -->
                    <div class="flex items-center gap-4">
                        <img :src="denr_logo" alt="Logo" class="h-auto w-[100px]" />
                        <div class="text-3xl font-semibold leading-tight text-white">
                            Department of Environment and Natural Resources<br />
                            Regional Office IV-A (CALABARZON)
                        </div>
                    </div>

                    <!-- Right: Copyright -->
                    <div class="text-right text-4xl font-bold text-white">{{ currentDateTime }}</div>
                </div>
            </div>
</template>