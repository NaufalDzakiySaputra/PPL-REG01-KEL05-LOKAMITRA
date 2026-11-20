<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import DetailEvent from '@/components/event/DetailEvent.vue';
import type { DetailEventData } from '@/types/event';

// Detail event dan jadwal kegiatan untuk Komunitas Lokal (F-06 / PBI-040).
defineProps<{ event: DetailEventData }>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Informasi Event', href: '/komunitas/events' },
            { title: 'Detail Event', href: '#' },
        ],
    },
});
</script>

<template>
    <Head :title="event.nama" />

    <DetailEvent
        :event="event"
        tautan-kembali="/komunitas/events"
        awalan="komunitas"
    />
</template>
