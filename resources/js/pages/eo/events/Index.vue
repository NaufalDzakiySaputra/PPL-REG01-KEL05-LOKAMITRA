
<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import DashCard from '@/components/dashboard/DashCard.vue';

type EventItem = {
    id: number;
    nama: string;
    kategori: string;
    lokasi: string;
    periode: string;
    status: string;
};

type PaginationLink = {
    url: string | null;
    label: string;
    active: boolean;
};

type EventPaginator = {
    data: EventItem[];
    current_page: number;
    last_page: number;
    total: number;
    links: PaginationLink[];
};

defineProps<{
    events: EventPaginator;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Event', href: '/eo/events' },
        ],
    },
});

function formatStatus(status: string) {
    return status.replaceAll('_', ' ');
}
</script>

<template>
    <Head title="Daftar Event" />

    <div class="flex flex-col gap-5 p-4 md:p-6">
        <header class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="text-2xl font-extrabold">Daftar Event</h1>
                <p class="mt-1 text-sm text-neutral-600 dark:text-muted-foreground">
                    Kelola dan pantau event yang telah Anda buat.
                </p>
            </div>

            <Link
                href="/eo/events/buat"
                class="rounded-lg bg-[#17564C] px-5 py-2.5 text-sm font-semibold text-white transition hover:opacity-90"
            >
                + Buat Event
            </Link>
        </header>

        <DashCard>
            <div class="mb-4">
                <h2 class="font-bold">Daftar Event Saya</h2>
                <p class="text-xs text-neutral-500">
                    {{ events.total }} event ditemukan
                </p>
            </div>

            <div
                v-if="events.data.length === 0"
                class="flex flex-col items-center gap-2 py-12 text-center"
            >
                <div class="text-4xl">📅</div>
                <p class="font-semibold">Belum ada event</p>
                <p class="text-sm text-neutral-500">
                    Mulai buat event pertama Anda.
                </p>
                <Link
                    href="/eo/events/buat"
                    class="mt-2 text-sm font-semibold text-[#17564C] hover:underline"
                >
                    Buat Event
                </Link>
            </div>

            <div v-else class="overflow-x-auto">
                <table class="w-full min-w-[650px] text-left text-sm">
                    <thead>
                        <tr class="border-b text-xs text-neutral-500">
                            <th class="px-3 py-3 font-semibold">Nama Event</th>
                            <th class="px-3 py-3 font-semibold">Kategori</th>
                            <th class="px-3 py-3 font-semibold">Lokasi</th>
                            <th class="px-3 py-3 font-semibold">Periode</th>
                            <th class="px-3 py-3 font-semibold">Status</th>
                            <th class="px-3 py-3 text-right font-semibold">Aksi</th>
                        </tr>
                    </thead>

                    <tbody>
                        <tr
                            v-for="event in events.data"
                            :key="event.id"
                            class="border-b last:border-0 hover:bg-neutral-50 dark:hover:bg-white/5"
                        >
                            <td class="px-3 py-4 font-semibold">
                                {{ event.nama }}
                            </td>
                            <td class="px-3 py-4">{{ event.kategori }}</td>
                            <td class="px-3 py-4">{{ event.lokasi }}</td>
                            <td class="px-3 py-4">{{ event.periode }}</td>
                            <td class="px-3 py-4">
                                <span class="rounded-full bg-neutral-100 px-3 py-1 text-xs font-medium capitalize text-neutral-700 dark:bg-white/10 dark:text-neutral-200">
                                    {{ formatStatus(event.status) }}
                                </span>
                            </td>
                            <td class="px-3 py-4 text-right">
                                <Link
                                    :href="`/eo/events/${event.id}`"
                                    class="font-semibold text-[#17564C] hover:underline"
                                >
                                    Lihat Detail
                                </Link>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div
                v-if="events.last_page > 1"
                class="mt-5 flex flex-wrap items-center justify-between gap-3 border-t pt-4"
            >
                <p class="text-xs text-neutral-500">
                    Halaman {{ events.current_page }} dari {{ events.last_page }}
                </p>

                <div class="flex flex-wrap gap-1">
                    <template v-for="(link, index) in events.links" :key="index">
                        <Link
                            v-if="link.url"
                            :href="link.url"
                            preserve-scroll
                            class="rounded-md border px-3 py-1.5 text-xs"
                            :class="link.active
                                ? 'border-[#17564C] bg-[#17564C] text-white'
                                : 'border-neutral-200 hover:bg-neutral-100 dark:border-border'"
                            v-html="link.label"
                        />
                        <span
                            v-else
                            class="rounded-md border border-neutral-200 px-3 py-1.5 text-xs opacity-40 dark:border-border"
                            v-html="link.label"
                        />
                    </template>
                </div>
            </div>
        </DashCard>
    </div>
</template>
