<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import axios from 'axios';
import { ref } from 'vue';
import denr_wall from '../../../images/bg6.png';
import modal_client_type from '../modal/modal_client_type.vue';
const open = ref(false);
const queue_number = ref('');
const counter_id = ref('');
const counter_name = ref('');
const openClientType = ref(false);
const selectedService = ref(null);  // this will hold 1, 2, or 3

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Transaction',
        href: '/transaction/index',
    },
];

const btn_transaction = async (counterId) => {
    try {
        counter_name.value = counterId;
        const response = await axios.post('/api/transaction', {
            counter_id: counterId,
        });

        const { message, queue_number: qNum } = response.data;
        if (message) {
            queue_number.value = qNum;
            open.value = true; // Show modal
        } else {
            console.warn('Response received but no message found.');
        }
    } catch (error) {
        console.error('❌ Error during transaction:', error);
    }
};

const openModal = (serviceValue: number) => {
  selectedService.value = serviceValue;
  openClientType.value = true;
};

</script>

<template>
    <!-- <modal_generate_queue v-if="open" :counterId="counter_name" :queue_no="queue_number" :open="open"
        @close="open = false">
    </modal_generate_queue> -->
    <modal_client_type
  v-if="openClientType"
  :open="openClientType"
  :serviceType="selectedService"
  @close="openClientType=false"
/>

    <Head title="Client" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div
            class="h-[500px] w-full flex-1 overflow-hidden rounded border bg-cover bg-center p-4 text-center text-white shadow transition-all duration-1000 ease-in-out"
            :style="{ backgroundImage: `url(${denr_wall})` }"
        >
            <div class="mt-64 flex flex-col items-center gap-10 rounded-lg p-6 md:p-24">
                <div class="flex w-full justify-center">
                    <Button
                        class="h-[100px] w-full border !bg-gradient-to-r from-blue-950 to-green-700 p-2 text-center text-white md:w-1/2"
                        @click="openModal(1)"
                    >
                        <div class="flex flex-col items-center justify-center leading-tight">
                            <span class="text-[40px] font-extrabold md:text-[65px]">PACDO</span>
                            <span class="text-center text-[30px] md:text-[14px]"> (LICENSES, PERMITTING, CERTIFICATION AND OTHER CONCERNS) </span>
                        </div>
                    </Button>
                </div>

                <div class="flex w-full justify-center">
                    <Button class="h-[100px] w-full border !bg-gradient-to-r from-blue-950 to-green-700 text-white md:w-1/2" @click="openModal(2)">
                        <label class="text-[40px] font-extrabold md:text-[65px]">SMD SERVICES</label>
                    </Button>
                </div>

                <div class="flex w-full justify-center">
                    <Button class="h-[100px] w-full border !bg-gradient-to-r from-blue-950 to-green-700 text-white md:w-1/2" @click="openModal(3)">
                        <label class="text-[40px] font-extrabold md:text-[65px]">TD EVALUATION </label>
                    </Button>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
