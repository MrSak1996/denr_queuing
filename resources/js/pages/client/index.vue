<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import client_dash from './client_dash.vue';
import client_footer from './client_footer.vue';

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
        <div class="from-blue-20 flex flex-col gap-4 rounded-lg bg-gradient-to-b to-green-50 p-4 shadow-md">
            <client_dash />
            <client_footer/>
        </div>
    </AppLayout>
</template>
