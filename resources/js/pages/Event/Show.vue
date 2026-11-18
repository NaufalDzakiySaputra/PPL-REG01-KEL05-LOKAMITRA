<script setup lang="ts">
import { Head, Link } from "@inertiajs/vue3";
import {
    ArrowLeft,
    CalendarDays,
    CalendarClock,
    CheckCircle2,
    Info,
    MapPin,
    Users,
} from "@lucide/vue";
import AppPublikLayout from "@/layouts/app/AppPublikLayout.vue";

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
};

defineProps<{ event: DetailEvent }>();

const palet = [
    "#4CC95F",
    "#F5333A",
    "#F58A1F",
    "#3DC1E8",
    "#3CC9B5",
    "#F72554",
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
                    {{ event.kategori ?? "Event" }}
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
                        class="mt-3 leading-relaxed whitespace-pre-line text-sm text-neutral-600"
                    >
                        {{
                            event.deskripsi ??
                            "Belum ada deskripsi untuk event ini."
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
                </aside>
            </div>
        </section>
    </AppPublikLayout>
</template>
