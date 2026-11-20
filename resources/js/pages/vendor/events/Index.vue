<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import DaftarEvent from '@/components/event/DaftarEvent.vue';
import type { FilterEvent, PaginatorKartu, PilihanEvent } from '@/types/event';

// Daftar event publik untuk Vendor/UMKM (F-06 / PBI-039).
defineProps<{
    events: PaginatorKartu;
    filter: FilterEvent;
    pilihan: PilihanEvent;
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Informasi Event', href: '/vendor/events' }],
    },
});
</script>

<template>
    <Head title="Informasi Event" />

    <DaftarEvent
        :events="events"
        :filter="filter"
        :pilihan="pilihan"
        rute="/vendor/events"
        awalan="vendor"
    />
</template>
