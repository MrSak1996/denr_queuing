<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import client_dash from './client_dash.vue';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import bgBanner from '../../images/bg2.png';


const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Client',
        href: '/client/index',
    },
];
</script>

<template>

    <Head title="Client" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-4 p-4">
        <!-- <img :src="bgBanner" alt="Background Banner" /> -->
        <client_dash />

            <div class="grid grid-cols-4 gap-4">
            </div>
        </div>
    </AppLayout>
</template>
