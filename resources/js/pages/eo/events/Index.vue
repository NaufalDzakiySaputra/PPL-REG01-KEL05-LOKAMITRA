<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { CalendarDays } from '@lucide/vue';
import DashCard from '@/components/dashboard/DashCard.vue';

// Daftar event milik EO. Data dari EventController@index.
type EventItem = {
    id: number;
    nama: string;
    kategori: string | null;
    lokasi: string | null;
    periode: string;
    status: string;
};

type Paginator<T> = {
    data: T[];
    links: { url: string | null; label: string; active: boolean }[];
    total: number;
};

defineProps<{ events: Paginator<EventItem> }>();

const labelStatus: Record<string, { teks: string; kelas: string }> = {
    draft: { teks: 'Draft', kelas: 'bg-neutral-200 text-neutral-700' },
    menunggu_verifikasi: {
        teks: 'Menunggu verifikasi Admin',
        kelas: 'bg-amber-100 text-amber-800',
    },
    dipublikasikan: {
        teks: 'Terverifikasi Admin',
        kelas: 'bg-emerald-100 text-emerald-800',
    },
    ditutup: { teks: 'Ditutup', kelas: 'bg-neutral-200 text-neutral-700' },
    diarsipkan: {
        teks: 'Diarsipkan',
        kelas: 'bg-neutral-200 text-neutral-700',
    },
};

function status(s: string): { teks: string; kelas: string } {
    return labelStatus[s] ?? labelStatus.draft;
}

function labelHalaman(label: string): string {
    return label.replace('&laquo;', '«').replace('&raquo;', '»');
}

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Event', href: '/eo/events' }],
    },
});
</script>

<template>
    <Head title="Event" />

    <div class="flex flex-col gap-4 p-4 md:p-6">
        <header class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <h1 class="text-2xl font-extrabold">Event</h1>
                <p class="text-sm text-neutral-600 dark:text-muted-foreground">
                    Daftar event yang Anda kelola.
                </p>
            </div>
            <Link
                href="/eo/events/buat"
                class="inline-flex items-center justify-center rounded-lg bg-[#17564C] px-4 py-2.5 text-sm font-semibold text-white hover:opacity-90"
                data-test="buat-event"
            >
                Buat event baru
            </Link>
        </header>

        <DashCard>
            <div
                v-if="events.data.length === 0"
                class="flex flex-col items-center gap-2 px-4 py-10 text-center"
            >
                <CalendarDays class="size-8 text-neutral-400" />
                <p class="font-semibold">Belum ada event</p>
                <p class="text-sm text-neutral-500">
                    Mulai dengan mencatat informasi awal event pertama Anda.
                </p>
                <Link
                    href="/eo/events/buat"
                    class="mt-2 inline-flex rounded-lg border border-[#17564C] px-4 py-2 text-sm font-semibold text-[#17564C] hover:bg-[#17564C]/5"
                >
                    Buat event baru
                </Link>
            </div>

            <ul v-else class="divide-y divide-neutral-200/80">
                <li
                    v-for="e in events.data"
                    :key="e.id"
                    class="flex flex-wrap items-center justify-between gap-3 py-3"
                >
                    <div class="min-w-0">
                        <p class="truncate text-sm font-semibold">
                            {{ e.nama }}
                        </p>
                        <p class="text-xs text-neutral-500">
                            {{
                                [e.periode, e.lokasi, e.kategori]
                                    .filter(Boolean)
                                    .join(' · ')
                            }}
                        </p>
                    </div>
                    <span
                        :class="[
                            'shrink-0 rounded-full px-3 py-1 text-xs font-semibold',
                            status(e.status).kelas,
                        ]"
                    >
                        {{ status(e.status).teks }}
                    </span>
                    <Link
                        v-if="!['ditutup', 'diarsipkan'].includes(e.status)"
                        :href="`/eo/events/${e.id}/ubah`"
                        class="text-xs font-semibold text-[#17564C] hover:underline"
                    >
                        Ubah
                    </Link>
                </li>
            </ul>

            <div
                v-if="events.links.length > 3"
                class="mt-3 flex flex-wrap justify-end gap-1 border-t pt-3"
            >
                <template v-for="(link, i) in events.links" :key="i">
                    <Link
                        v-if="link.url"
                        :href="link.url"
                        preserve-scroll
                        class="rounded-md border px-3 py-1.5 text-xs"
                        :class="
                            link.active
                                ? 'bg-[#17564C] text-white'
                                : 'hover:bg-muted'
                        "
                    >
                        {{ labelHalaman(link.label) }}
                    </Link>
                    <span
                        v-else
                        class="rounded-md border px-3 py-1.5 text-xs text-muted-foreground opacity-50"
                    >
                        {{ labelHalaman(link.label) }}
                    </span>
                </template>
            </div>
        </DashCard>
    </div>
</template>
