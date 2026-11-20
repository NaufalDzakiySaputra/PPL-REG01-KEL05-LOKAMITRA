<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import DetailEvent from '@/components/event/DetailEvent.vue';
import type { DetailEventData } from '@/types/event';

// Detail event dan rundown untuk Vendor/UMKM (F-06 / PBI-039).
defineProps<{ event: DetailEventData }>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Informasi Event', href: '/vendor/events' },
            { title: 'Detail Event', href: '#' },
        ],
    },
});
</script>

<template>
    <Head :title="event.nama" />

    <DetailEvent
        :event="event"
        tautan-kembali="/vendor/events"
        awalan="vendor"
    />
</template>
