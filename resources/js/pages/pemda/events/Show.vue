<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import DetailEvent from '@/components/event/DetailEvent.vue';
import type { DetailEventData } from '@/types/event';

// Detail event dan regulasi untuk Pemerintah Daerah (F-06 / PBI-038).
defineProps<{ event: DetailEventData }>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Informasi Event', href: '/pemda/events' },
            { title: 'Detail Event', href: '#' },
        ],
    },
});
</script>

<template>
    <Head :title="event.nama" />

    <DetailEvent :event="event" tautan-kembali="/pemda/events" awalan="pemda" />
</template>
