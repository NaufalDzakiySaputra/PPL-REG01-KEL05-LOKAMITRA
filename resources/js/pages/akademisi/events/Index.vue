<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import DaftarEvent from '@/components/event/DaftarEvent.vue';
import type { FilterEvent, PaginatorKartu, PilihanEvent } from '@/types/event';

// Daftar event publik untuk Akademisi (F-06 / PBI-041).
defineProps<{
    events: PaginatorKartu;
    filter: FilterEvent;
    pilihan: PilihanEvent;
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Informasi Event', href: '/akademisi/events' }],
    },
});
</script>

<template>
    <Head title="Informasi Event" />

    <DaftarEvent
        :events="events"
        :filter="filter"
        :pilihan="pilihan"
        rute="/akademisi/events"
        awalan="akademisi"
    />
</template>
