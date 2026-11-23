<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import UtasForum from '@/components/forum/UtasForum.vue';

// Halaman detail utas forum (F-07). Dipakai bersama oleh seluruh peserta.
defineProps<{
    event: { id: number; nama: string };
    awalan: string;
    topikAwal: number | null;
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Forum Diskusi' }, { title: 'Utas' }],
    },
});
</script>

<template>
    <Head :title="`Forum Diskusi - ${event.nama}`" />

    <UtasForum :event="event" :awalan="awalan" :topik-awal="topikAwal" />
</template>
