<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import admin_header from '@/pages/admin_controller/admin_header.vue';
import admin_serving from '@/pages/admin_controller/admin_serving.vue';
import client_footer from './client/client_footer.vue';

import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import bgBanner from '../../images/bg2.png';


const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: '/dashboard',
    },
];
</script>

<template>

    <Head title="Dashboard" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-4 p-4">
        <!-- <img :src="bgBanner" alt="Background Banner" /> -->
            <!-- <admin_header /> -->
            <div class="grid grid-cols-4 gap-4">
                <admin_serving />
            </div>
            <client_footer/>

        </div>
    </AppLayout>
</template>
