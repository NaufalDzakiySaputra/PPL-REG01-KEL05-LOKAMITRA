<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import DaftarForum from '@/components/forum/DaftarForum.vue';
import type { EventForum } from '@/types/forum';

// Halaman daftar topik forum (F-07). Satu komponen dipakai oleh enam peran.
defineProps<{
    events: EventForum[];
    awalan: string;
    bolehBuatTopik: boolean;
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Forum Diskusi' }],
    },
});
</script>

<template>
    <Head title="Forum Diskusi" />

    <DaftarForum
        :events="events"
        :awalan="awalan"
        :boleh-buat-topik="bolehBuatTopik"
    />
</template>
