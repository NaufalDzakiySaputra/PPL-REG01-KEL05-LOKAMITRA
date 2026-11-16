<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import MitraDashboardContent from '@/components/dashboard/MitraDashboardContent.vue';
import type { MitraDashboardProps } from '@/types/dashboard';

// Dashboard Akademisi. Isi bersama ada di MitraDashboardContent.
defineProps<MitraDashboardProps>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Dashboard Akademisi', href: '/akademisi/dashboard' }],
    },
});
</script>

<template>
    <Head title="Dashboard Akademisi" />

    <MitraDashboardContent
        :peran="peran"
        :status-verifikasi="statusVerifikasi"
        :profil="profil"
        :statistik="statistik"
        :tugas-terdekat="tugasTerdekat"
        :event-diikuti="eventDiikuti"
        :event-tersedia="eventTersedia"
        :aktivitas="aktivitas"
    />
</template>
