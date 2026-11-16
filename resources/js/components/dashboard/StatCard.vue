<script setup lang="ts">
// Kartu angka besar: label kecil, nilai tebal berwarna hijau, catatan di bawah.
withDefaults(
    defineProps<{
        label: string;
        nilai: string | number;
        catatan?: string;
        // admin = hijau tua; eo = hijau teal
        aksen?: 'admin' | 'eo';
        nadaCatatan?: 'hijau' | 'kuning' | 'abu';
    }>(),
    { aksen: 'eo', nadaCatatan: 'abu' },
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
                aksen === 'admin'
                    ? 'text-[#245C3B] dark:text-emerald-400'
                    : 'text-[#17564C] dark:text-teal-300',
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
