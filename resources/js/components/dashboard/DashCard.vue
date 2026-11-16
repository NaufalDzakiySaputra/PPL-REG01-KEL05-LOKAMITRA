<script setup lang="ts">
// Kartu putih bersudut membulat yang dipakai semua dashboard.
defineProps<{ judul?: string }>();
</script>

<template>
    <section
        class="rounded-2xl border border-neutral-200/80 bg-white p-5 dark:border-border dark:bg-card"
    >
        <header
            v-if="judul || $slots.aksi"
            class="mb-3 flex items-center justify-between gap-3"
        >
            <h2 class="text-base font-bold">{{ judul }}</h2>
            <slot name="aksi" />
        </header>
        <slot />
    </section>
</template>
