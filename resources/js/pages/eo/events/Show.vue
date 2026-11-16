<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import DashCard from '@/components/dashboard/DashCard.vue';

defineProps<{
    event: {
        id: number;
        nama: string;
        kategori: string | null;
        lokasi: string | null;
        deskripsi: string | null;
        periode: string;
        status: string;
        dapat_diubah: boolean;
        diperbarui: string;
    };
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Event', href: '/eo/events' },
            { title: 'Detail Event', href: '#' },
        ],
    },
});
</script>

<template>
    <Head :title="event.nama" />

    <div class="flex flex-col gap-4 p-4 md:p-6">
        <header class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <h1 class="text-2xl font-extrabold" data-test="detail-nama">
                    {{ event.nama }}
                </h1>
                <p class="text-sm text-neutral-500">
                    Terakhir diperbarui {{ event.diperbarui }}
                </p>
            </div>
            <Link
                v-if="event.dapat_diubah"
                :href="`/eo/events/${event.id}/ubah`"
                class="inline-flex rounded-lg bg-[#17564C] px-4 py-2.5 text-sm font-semibold text-white hover:opacity-90"
                data-test="ubah-event"
            >
                Ubah informasi
            </Link>
        </header>

        <DashCard judul="Informasi event">
            <dl class="grid gap-4 sm:grid-cols-2">
                <div>
                    <dt class="text-xs text-neutral-500">Periode</dt>
                    <dd class="text-sm font-medium">{{ event.periode }}</dd>
                </div>
                <div>
                    <dt class="text-xs text-neutral-500">Lokasi</dt>
                    <dd class="text-sm font-medium">
                        {{ event.lokasi ?? '-' }}
                    </dd>
                </div>
                <div>
                    <dt class="text-xs text-neutral-500">Kategori</dt>
                    <dd class="text-sm font-medium">
                        {{ event.kategori ?? '-' }}
                    </dd>
                </div>
                <div>
                    <dt class="text-xs text-neutral-500">Status</dt>
                    <dd class="text-sm font-medium">{{ event.status }}</dd>
                </div>
                <div class="sm:col-span-2">
                    <dt class="text-xs text-neutral-500">Deskripsi</dt>
                    <dd class="text-sm whitespace-pre-line">
                        {{ event.deskripsi || '-' }}
                    </dd>
                </div>
            </dl>
        </DashCard>
    </div>
</template>
