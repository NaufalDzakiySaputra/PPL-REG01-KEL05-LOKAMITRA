<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import DetailEvent from '@/components/event/DetailEvent.vue';
import type { DetailEventData } from '@/types/event';

// Detail event dan informasi kegiatan untuk Akademisi (F-06 / PBI-041).
defineProps<{ event: DetailEventData }>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Informasi Event', href: '/akademisi/events' },
            { title: 'Detail Event', href: '#' },
        ],
    },
});
</script>

<template>
    <Head :title="event.nama" />

    <DetailEvent
        :event="event"
        tautan-kembali="/akademisi/events"
        awalan="akademisi"
    />
</template>
