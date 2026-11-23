<script setup lang="ts">
// Kartu angka besar: label kecil, nilai tebal berwarna hijau admin,
// catatan di bawah. Semua dashboard memakai aksen yang sama.
withDefaults(
    defineProps<{
        label: string;
        nilai: string | number;
        catatan?: string;
        // admin = hijau tua (default); eo = hijau teal (kompatibilitas lama)
        aksen?: 'admin' | 'eo';
        nadaCatatan?: 'hijau' | 'kuning' | 'abu';
    }>(),
    { aksen: 'admin', nadaCatatan: 'abu' },
);
</script>

<template>
    <div
        class="rounded-2xl border border-neutral-200/80 bg-white p-4 dark:border-border dark:bg-card"
    >
        <p class="text-xs text-neutral-600 dark:text-muted-foreground">
            {{ label }}
        </p>
        <p
            :class="[
                'mt-1 text-3xl font-extrabold',
                aksen === 'eo'
                    ? 'text-[#17564C] dark:text-teal-300'
                    : 'text-[#245C3B] dark:text-emerald-400',
            ]"
        >
            {{ nilai }}
        </p>
        <p
            v-if="catatan"
            :class="[
                'mt-0.5 text-xs',
                nadaCatatan === 'hijau'
                    ? 'font-medium text-[#2F8F4E]'
                    : nadaCatatan === 'kuning'
                      ? 'font-medium text-[#B7791F]'
                      : 'text-neutral-500 dark:text-muted-foreground',
            ]"
        >
            {{ catatan }}
        </p>
    </div>
</template>
