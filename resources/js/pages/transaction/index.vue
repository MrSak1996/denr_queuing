<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import denr_wall from '../../../images/bg6.png';
import axios from 'axios';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Transaction',
        href: '/transaction/index',
    },
];

const btn_transaction = async (counterId) => {
    try {
        const response = await axios.post('/api/transaction',{
            counter_id: counterId,
        });
        if (response.data.success) {
            // Handle success, e.g., navigate to another page or show a message
            console.log('Transaction successful:', response.data.message);
        } else {
            // Handle error, e.g., show an error message
            console.error('Transaction failed:', response.data.message);
        }
    } catch (error) {
        console.error('Error during transaction:', error);
    }
};
</script>

<template>
    <Head title="Client" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div
            class="h-[500px] w-full flex-1 overflow-hidden rounded border bg-cover bg-center p-4 text-center text-white shadow transition-all duration-1000 ease-in-out"
            :style="{ backgroundImage: `url(${denr_wall})` }"
        >
            <div class="mt-64 flex flex-col items-center gap-10 rounded-lg p-6 md:p-24">
                <div class="flex w-full justify-center">
                    <Button class="h-[100px] w-full border !bg-gradient-to-r from-blue-950 to-green-700 text-white md:w-1/2" @click="btn_transaction(1)">
                        <label class="text-[40px] font-extrabold md:text-[65px]">LAND SERVICES</label>
                    </Button>
                </div>

                <div class="flex w-full justify-center">
                    <Button class="h-[100px] w-full border !bg-gradient-to-r from-blue-950 to-green-700 text-white md:w-1/2" @click="btn_transaction(2)">
                        <label class="text-[40px] font-extrabold md:text-[65px]">PWD/SENIOR CITIZEN</label>
                    </Button>
                </div>

                <div class="flex w-full justify-center">
                    <Button class="h-[100px] w-full border !bg-gradient-to-r from-blue-950 to-green-700 text-white md:w-1/2" @click="btn_transaction(3)">
                        <label class="text-[40px] font-extrabold md:text-[65px]">OTHER CONCERNS</label>
                    </Button>
                </div>

               
            </div>
        </div>
    </AppLayout>
</template>
