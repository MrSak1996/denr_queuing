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
    <div class="col-span-1 flex flex-col  justify-start gap-4 bg-gray-100 p-4 rounded-lg">
        <div class="flex items-center">
                <div class="mr-4 text-lg">Total Client’s Served:</div>
                <div class="rounded-md border bg-white px-4 py-1 font-bold">1,430</div>
            </div>
    </div>
</template>
<style>

</style>