<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import DetailEvent from '@/components/event/DetailEvent.vue';
import type { DetailEventData } from '@/types/event';

// Detail event, rundown, dan kontak penyelenggara untuk Media (F-06 / PBI-042).
defineProps<{ event: DetailEventData }>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Informasi Event', href: '/media/events' },
            { title: 'Detail Event', href: '#' },
        ],
    },
});
</script>

<template>
    <Head :title="event.nama" />

    <DetailEvent
        :event="event"
        tautan-kembali="/media/events"
        awalan="media"
        judul-kontak="Kontak penyelenggara"
    />
</template>
