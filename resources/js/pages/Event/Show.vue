<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import {
    ArrowLeft,
    CalendarDays,
    CalendarClock,
    CheckCircle2,
    Download,
    FileText,
    Info,
    MapPin,
    Phone,
    Users,
    Clock,
} from '@lucide/vue';
import AppPublikLayout from '@/layouts/app/AppPublikLayout.vue';

type ButirRundown = {
    judul: string;
    deskripsi: string | null;
    waktu_mulai: string | null;
    waktu_selesai: string | null;
};

type ButirDokumen = {
    id: number;
    nama: string;
    jenis: 'layout' | 'dokumen';
    unduh_url: string;
};

type ButirKontak = {
    nama: string;
    nomor: string;
};

type DetailEvent = {
    id: number;
    nama: string;
    deskripsi: string | null;
    kategori: string | null;
    lokasi: string | null;
    stakeholder: number;
    warna: number;
    tanggal_mulai: string;
    tanggal_selesai: string;
    daftar_sampai: string;
    sedang_berjalan: boolean;
    informasi_regulasi: string | null;
    rundown: ButirRundown[];
    dokumen: ButirDokumen[];
    kontak_darurat: ButirKontak[];
};

defineProps<{ event: DetailEvent }>();

const palet = [
    '#4CC95F',
    '#F5333A',
    '#F58A1F',
    '#3DC1E8',
    '#3CC9B5',
    '#F72554',
];
</script>

<template>
    <Head :title="event.nama" />

    <AppPublikLayout>
        <!-- banner -->
        <section
            class="relative overflow-hidden px-6 py-12 text-white"
            :style="{ backgroundColor: palet[event.warna] }"
        >
            <!-- overlay gelap agar teks selalu terbaca di atas warna terang -->
            <div
                class="pointer-events-none absolute inset-0 bg-gradient-to-r from-black/55 via-black/35 to-black/10"
            ></div>
            <div
                class="pointer-events-none absolute -top-20 -right-20 h-80 w-80 rounded-full bg-white/15 blur-2xl"
            ></div>
            <div class="relative mx-auto max-w-4xl">
                <Link
                    href="/events"
                    class="mb-6 inline-flex items-center gap-1.5 text-sm font-semibold text-white/90 transition-colors hover:text-white"
                >
                    <ArrowLeft class="size-4" /> Kembali ke daftar event
                </Link>

                <span
                    class="inline-block rounded-full bg-white/25 px-3 py-1 text-xs font-bold tracking-wide uppercase"
                >
                    {{ event.kategori ?? 'Event' }}
                </span>
                <h1
                    class="mt-3 text-3xl font-extrabold text-white drop-shadow md:text-4xl"
                >
                    {{ event.nama }}
                </h1>
                <p class="mt-3 flex items-center gap-1.5 text-sm text-white/90">
                    <MapPin class="size-4" /> {{ event.lokasi }}
                </p>
            </div>
        </section>

        <section class="mx-auto max-w-4xl px-6 py-10">
            <div class="grid gap-8 md:grid-cols-3">
                <!-- deskripsi -->
                <div class="md:col-span-2">
                    <h2 class="text-lg font-extrabold text-neutral-900">
                        Tentang event ini
                    </h2>
                    <p
                        class="mt-3 text-sm leading-relaxed whitespace-pre-line text-neutral-600"
                    >
                        {{
                            event.deskripsi ??
                            'Belum ada deskripsi untuk event ini.'
                        }}
                    </p>

                    <div
                        v-if="event.informasi_regulasi"
                        class="mt-6 flex gap-3 rounded-xl border border-[#E5E2DF] bg-white p-4"
                    >
                        <Info class="size-5 shrink-0 text-[#216B60]" />
                        <div>
                            <p class="text-sm font-bold text-neutral-800">
                                Informasi &amp; regulasi
                            </p>
                            <p
                                class="mt-1 text-sm whitespace-pre-line text-neutral-600"
                            >
                                {{ event.informasi_regulasi }}
                            </p>
                        </div>
                    </div>

                    <!-- rundown acara -->
                    <div
                        v-if="event.rundown.length > 0"
                        class="mt-8"
                        data-test="rundown-event"
                    >
                        <h2 class="text-lg font-extrabold text-neutral-900">
                            Rundown acara
                        </h2>
                        <ol class="mt-4 space-y-0">
                            <li
                                v-for="(butir, i) in event.rundown"
                                :key="i"
                                class="relative flex gap-4 pb-6 last:pb-0"
                            >
                                <!-- garis vertikal -->
                                <span
                                    v-if="i < event.rundown.length - 1"
                                    class="absolute top-5 left-[7px] h-full w-0.5 bg-[#E5E2DF]"
                                ></span>
                                <span
                                    class="relative mt-1.5 size-3.5 shrink-0 rounded-full border-2 border-[#216B60] bg-white"
                                ></span>
                                <div>
                                    <p
                                        class="flex flex-wrap items-center gap-x-2 text-sm font-bold text-neutral-800"
                                    >
                                        {{ butir.judul }}
                                        <span
                                            v-if="butir.waktu_mulai"
                                            class="rounded-full bg-[#216B60]/10 px-2 py-0.5 text-[11px] font-semibold text-[#216B60]"
                                        >
                                            <Clock
                                                class="mr-0.5 inline size-3"
                                            />{{ butir.waktu_mulai
                                            }}{{
                                                butir.waktu_selesai
                                                    ? ` – ${butir.waktu_selesai}`
                                                    : ''
                                            }}
                                        </span>
                                    </p>
                                    <p
                                        v-if="butir.deskripsi"
                                        class="mt-1 text-sm text-neutral-600"
                                    >
                                        {{ butir.deskripsi }}
                                    </p>
                                </div>
                            </li>
                        </ol>
                    </div>

                    <!-- layout & dokumen -->
                    <div
                        v-if="event.dokumen.length > 0"
                        class="mt-8"
                        data-test="dokumen-event"
                    >
                        <h2 class="text-lg font-extrabold text-neutral-900">
                            Layout &amp; dokumen
                        </h2>
                        <ul class="mt-4 grid gap-3 sm:grid-cols-2">
                            <li v-for="d in event.dokumen" :key="d.id">
                                <a
                                    :href="d.unduh_url"
                                    class="flex items-center gap-3 rounded-xl border border-[#E5E2DF] bg-white p-4 transition-colors hover:border-[#216B60]"
                                    :data-test="`unduh-dokumen-${d.id}`"
                                >
                                    <span
                                        class="flex size-10 shrink-0 items-center justify-center rounded-lg bg-[#216B60]/10"
                                    >
                                        <FileText
                                            class="size-5 text-[#216B60]"
                                        />
                                    </span>
                                    <span class="min-w-0 flex-1">
                                        <span
                                            class="block truncate text-sm font-semibold text-neutral-800"
                                            >{{ d.nama }}</span
                                        >
                                        <span class="text-xs text-neutral-500">
                                            {{
                                                d.jenis === 'layout'
                                                    ? 'Layout / denah venue'
                                                    : 'Dokumen pendukung'
                                            }}
                                            &middot; Klik untuk mengunduh
                                        </span>
                                    </span>
                                    <Download
                                        class="size-4 shrink-0 text-[#216B60]"
                                    />
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>

                <!-- info penting -->
                <aside class="space-y-4">
                    <div
                        class="rounded-2xl border border-[#E5E2DF] bg-white p-5 shadow-sm"
                    >
                        <h3 class="text-sm font-extrabold text-neutral-900">
                            Jadwal
                        </h3>
                        <dl class="mt-4 space-y-3 text-sm">
                            <div class="flex gap-3">
                                <CalendarDays
                                    class="size-4 shrink-0 text-[#216B60]"
                                />
                                <div>
                                    <dt class="text-xs text-neutral-500">
                                        Tanggal mulai
                                    </dt>
                                    <dd class="font-semibold text-neutral-800">
                                        {{ event.tanggal_mulai }}
                                    </dd>
                                </div>
                            </div>
                            <div class="flex gap-3">
                                <CalendarClock
                                    class="size-4 shrink-0 text-[#216B60]"
                                />
                                <div>
                                    <dt class="text-xs text-neutral-500">
                                        Pendaftaran sampai
                                    </dt>
                                    <dd class="font-semibold text-neutral-800">
                                        {{ event.daftar_sampai }}
                                    </dd>
                                </div>
                            </div>
                            <div class="flex gap-3">
                                <MapPin
                                    class="size-4 shrink-0 text-[#216B60]"
                                />
                                <div>
                                    <dt class="text-xs text-neutral-500">
                                        Lokasi
                                    </dt>
                                    <dd class="font-semibold text-neutral-800">
                                        {{ event.lokasi }}
                                    </dd>
                                </div>
                            </div>
                            <div class="flex gap-3">
                                <Users class="size-4 shrink-0 text-[#216B60]" />
                                <div>
                                    <dt class="text-xs text-neutral-500">
                                        Stakeholder terlibat
                                    </dt>
                                    <dd class="font-semibold text-neutral-800">
                                        {{ event.stakeholder }}
                                    </dd>
                                </div>
                            </div>
                        </dl>

                        <p
                            v-if="event.sedang_berjalan"
                            class="mt-4 flex items-center gap-1.5 rounded-lg bg-[#216B60]/10 px-3 py-2 text-xs font-semibold text-[#216B60]"
                        >
                            <CheckCircle2 class="size-4" /> Event sedang
                            berlangsung
                        </p>

                        <Link
                            v-if="!event.sedang_berjalan"
                            href="/login"
                            class="mt-5 block rounded-full bg-[#216B60] px-5 py-2.5 text-center text-sm font-semibold text-white transition-colors hover:bg-[#18584F]"
                            data-test="daftar-event"
                        >
                            Masuk untuk mendaftar
                        </Link>
                        <Link
                            v-else
                            href="/pengunjung/dashboard"
                            class="mt-5 block rounded-full bg-[#216B60] px-5 py-2.5 text-center text-sm font-semibold text-white transition-colors hover:bg-[#18584F]"
                            data-test="daftar-event"
                        >
                            Daftar sebagai peserta
                        </Link>
                    </div>

                    <!-- kontak darurat -->
                    <div
                        v-if="event.kontak_darurat.length > 0"
                        class="rounded-2xl border border-[#E5E2DF] bg-white p-5 shadow-sm"
                        data-test="kontak-darurat"
                    >
                        <h3 class="text-sm font-extrabold text-neutral-900">
                            Kontak darurat
                        </h3>
                        <ul class="mt-4 space-y-3">
                            <li
                                v-for="k in event.kontak_darurat"
                                :key="k.nomor"
                                class="flex items-center gap-3"
                            >
                                <span
                                    class="flex size-9 shrink-0 items-center justify-center rounded-full bg-red-100"
                                >
                                    <Phone class="size-4 text-red-600" />
                                </span>
                                <div class="min-w-0">
                                    <p
                                        class="truncate text-sm font-semibold text-neutral-800"
                                    >
                                        {{ k.nama }}
                                    </p>
                                    <a
                                        :href="`tel:${k.nomor}`"
                                        class="text-xs font-medium text-[#155F55] transition-colors hover:text-[#0D443D]"
                                    >
                                        {{ k.nomor }}
                                    </a>
                                </div>
                            </li>
                        </ul>
                    </div>
                </aside>
            </div>
        </section>
    </AppPublikLayout>
</template>
