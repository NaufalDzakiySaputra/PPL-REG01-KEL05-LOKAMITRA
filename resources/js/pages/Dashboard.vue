<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import DashCard from '@/components/dashboard/DashCard.vue';
import { dashboard } from '@/routes';

// Dashboard generik: dipakai sebagai fallback ketika peran tidak dikenali.
// Gaya visual mengikuti Dashboard Admin supaya konsisten lintas peran.
defineProps<{
    roleLabel?: string;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Dashboard',
                href: dashboard(),
            },
        ],
    },
});
</script>

<template>
    <Head :title="roleLabel ? `Dashboard ${roleLabel}` : 'Dashboard'" />

    <div class="flex flex-col gap-4 p-4 md:p-6">
        <header class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <h1 class="text-2xl font-extrabold">
                    {{ roleLabel ? `Dashboard ${roleLabel}` : 'Dashboard' }}
                </h1>
                <p class="text-sm text-neutral-600 dark:text-muted-foreground">
                    Ringkasan aktivitas Anda di Lokamitra.
                </p>
            </div>
        </header>

        <DashCard judul="Ringkasan">
            <p class="py-6 text-center text-sm text-neutral-500">
                Dashboard untuk peran ini belum tersedia.
            </p>
        </DashCard>
    </div>
</template>
