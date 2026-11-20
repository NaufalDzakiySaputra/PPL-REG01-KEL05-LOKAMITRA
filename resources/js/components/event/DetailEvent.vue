<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import {
    ArrowLeft,
    CalendarClock,
    CalendarDays,
    Download,
    FileText,
    Info,
    MapPin,
    Phone,
    ShieldCheck,
    Users,
} from '@lucide/vue';
import DashCard from '@/components/dashboard/DashCard.vue';
import type { DetailEventData } from '@/types/event';

// Detail event publik untuk peran mitra (F-06). Hanya berisi informasi dari
// event yang sudah dipublikasikan. Dipakai bersama oleh halaman Pemerintah
// Daerah dan Vendor/UMKM.
defineProps<{
    event: DetailEventData;
    tautanKembali: string;
    awalan: string;
}>();
</script>

<template>
    <div class="flex flex-col gap-4 p-4 md:p-6">
        <header class="flex flex-col gap-3">
            <Link
                :href="tautanKembali"
                class="inline-flex w-fit items-center gap-1.5 text-sm font-semibold text-[#17564C] hover:underline dark:text-emerald-300"
                data-test="kembali-daftar-event"
            >
                <ArrowLeft class="size-4" /> Kembali ke daftar event
            </Link>

            <div class="flex flex-wrap items-start justify-between gap-3">
                <div class="min-w-0">
                    <span
                        class="inline-block rounded-full bg-[#17564C]/10 px-2.5 py-0.5 text-[11px] font-bold tracking-wide text-[#17564C] uppercase dark:text-emerald-300"
                    >
                        {{ event.kategori ?? 'Event' }}
                    </span>
                    <h1
                        class="mt-1 text-2xl font-extrabold"
                        :data-test="`nama-event-${awalan}`"
                    >
                        {{ event.nama }}
                    </h1>
                    <p
                        class="mt-1 flex items-center gap-1 text-sm text-neutral-600 dark:text-muted-foreground"
                    >
                        <MapPin class="size-4" />
                        {{ event.lokasi ?? 'Lokasi belum diisi' }}
                    </p>
                </div>

                <p
                    v-if="event.sedang_berjalan"
                    class="inline-flex items-center gap-1.5 rounded-full bg-[#17564C]/10 px-3 py-1 text-xs font-semibold text-[#17564C] dark:text-emerald-300"
                >
                    <ShieldCheck class="size-4" /> Event sedang berlangsung
                </p>
            </div>
        </header>

        <div
            class="grid gap-4 lg:grid-cols-3"
            :data-test="`detail-event-${awalan}`"
        >
            <div class="flex flex-col gap-4 lg:col-span-2">
                <DashCard judul="Informasi event">
                    <p
                        class="text-sm leading-relaxed whitespace-pre-line text-neutral-600 dark:text-muted-foreground"
                    >
                        {{
                            event.deskripsi ??
                            'Belum ada deskripsi untuk event ini.'
                        }}
                    </p>

                    <dl class="mt-4 grid gap-3 text-sm sm:grid-cols-2">
                        <div>
                            <dt
                                class="text-xs text-neutral-500 dark:text-muted-foreground"
                            >
                                Tanggal mulai
                            </dt>
                            <dd class="font-semibold">
                                {{ event.tanggal_mulai }}
                            </dd>
                        </div>
                        <div>
                            <dt
                                class="text-xs text-neutral-500 dark:text-muted-foreground"
                            >
                                Tanggal selesai
                            </dt>
                            <dd class="font-semibold">
                                {{ event.tanggal_selesai }}
                            </dd>
                        </div>
                        <div>
                            <dt
                                class="text-xs text-neutral-500 dark:text-muted-foreground"
                            >
                                Pendaftaran sampai
                            </dt>
                            <dd class="font-semibold">
                                {{ event.daftar_sampai }}
                            </dd>
                        </div>
                        <div>
                            <dt
                                class="text-xs text-neutral-500 dark:text-muted-foreground"
                            >
                                Stakeholder terlibat
                            </dt>
                            <dd class="font-semibold">
                                {{ event.stakeholder }} pihak
                            </dd>
                        </div>
                    </dl>
                </DashCard>

                <DashCard judul="Informasi & regulasi">
                    <div
                        v-if="event.informasi_regulasi"
                        class="flex gap-3 rounded-xl border border-[#17564C]/20 bg-[#17564C]/5 p-4"
                        data-test="informasi-regulasi"
                    >
                        <Info
                            class="size-5 shrink-0 text-[#17564C] dark:text-emerald-300"
                        />
                        <p
                            class="text-sm whitespace-pre-line text-neutral-700 dark:text-muted-foreground"
                        >
                            {{ event.informasi_regulasi }}
                        </p>
                    </div>
                    <p
                        v-else
                        class="py-4 text-sm text-neutral-500 dark:text-muted-foreground"
                    >
                        EO belum menambahkan informasi regulasi untuk event ini.
                    </p>
                </DashCard>

                <DashCard judul="Rundown acara">
                    <ol
                        v-if="event.rundown.length > 0"
                        class="space-y-3"
                        data-test="rundown-event"
                    >
                        <li
                            v-for="(butir, i) in event.rundown"
                            :key="i"
                            class="rounded-xl border border-neutral-200/80 p-3 dark:border-border"
                            data-test="butir-rundown"
                        >
                            <p
                                class="flex flex-wrap items-center gap-x-2 text-sm font-bold"
                            >
                                {{ butir.judul }}
                                <span
                                    v-if="butir.waktu_mulai"
                                    class="rounded-full bg-[#17564C]/10 px-2 py-0.5 text-[11px] font-semibold text-[#17564C] dark:text-emerald-300"
                                >
                                    {{ butir.waktu_mulai
                                    }}{{
                                        butir.waktu_selesai
                                            ? ` – ${butir.waktu_selesai}`
                                            : ''
                                    }}
                                </span>
                            </p>
                            <p
                                v-if="butir.deskripsi"
                                class="mt-1 text-sm text-neutral-600 dark:text-muted-foreground"
                            >
                                {{ butir.deskripsi }}
                            </p>
                        </li>
                    </ol>
                    <p
                        v-else
                        class="py-4 text-sm text-neutral-500 dark:text-muted-foreground"
                    >
                        EO belum menyusun rundown untuk event ini.
                    </p>
                </DashCard>

                <DashCard judul="Layout & dokumen">
                    <ul
                        v-if="event.dokumen.length > 0"
                        class="grid gap-3 sm:grid-cols-2"
                        data-test="dokumen-event"
                    >
                        <li v-for="d in event.dokumen" :key="d.id">
                            <a
                                :href="d.unduh_url"
                                class="flex items-center gap-3 rounded-xl border border-neutral-200/80 p-3 transition-colors hover:border-[#17564C] dark:border-border"
                                :data-test="`unduh-dokumen-${d.id}`"
                            >
                                <span
                                    class="flex size-10 shrink-0 items-center justify-center rounded-lg bg-[#17564C]/10"
                                >
                                    <FileText
                                        class="size-5 text-[#17564C] dark:text-emerald-300"
                                    />
                                </span>
                                <span class="min-w-0 flex-1">
                                    <span
                                        class="block truncate text-sm font-semibold"
                                        >{{ d.nama }}</span
                                    >
                                    <span
                                        class="text-xs text-neutral-500 dark:text-muted-foreground"
                                    >
                                        {{
                                            d.jenis === 'layout'
                                                ? 'Layout / denah venue'
                                                : 'Dokumen pendukung'
                                        }}
                                    </span>
                                </span>
                                <Download
                                    class="size-4 shrink-0 text-[#17564C] dark:text-emerald-300"
                                />
                            </a>
                        </li>
                    </ul>
                    <p
                        v-else
                        class="py-4 text-sm text-neutral-500 dark:text-muted-foreground"
                    >
                        EO belum mengunggah layout atau dokumen.
                    </p>
                </DashCard>
            </div>

            <div class="flex flex-col gap-4">
                <DashCard judul="Jadwal">
                    <dl class="space-y-3 text-sm">
                        <div class="flex gap-3">
                            <CalendarDays
                                class="size-4 shrink-0 text-[#17564C] dark:text-emerald-300"
                            />
                            <div>
                                <dt
                                    class="text-xs text-neutral-500 dark:text-muted-foreground"
                                >
                                    Mulai
                                </dt>
                                <dd class="font-semibold">
                                    {{ event.tanggal_mulai }}
                                </dd>
                            </div>
                        </div>
                        <div class="flex gap-3">
                            <CalendarClock
                                class="size-4 shrink-0 text-[#17564C] dark:text-emerald-300"
                            />
                            <div>
                                <dt
                                    class="text-xs text-neutral-500 dark:text-muted-foreground"
                                >
                                    Selesai
                                </dt>
                                <dd class="font-semibold">
                                    {{ event.tanggal_selesai }}
                                </dd>
                            </div>
                        </div>
                        <div class="flex gap-3">
                            <Users
                                class="size-4 shrink-0 text-[#17564C] dark:text-emerald-300"
                            />
                            <div>
                                <dt
                                    class="text-xs text-neutral-500 dark:text-muted-foreground"
                                >
                                    Stakeholder
                                </dt>
                                <dd class="font-semibold">
                                    {{ event.stakeholder }} pihak terlibat
                                </dd>
                            </div>
                        </div>
                    </dl>
                </DashCard>

                <DashCard judul="Kontak darurat">
                    <ul
                        v-if="event.kontak_darurat.length > 0"
                        class="space-y-3"
                        data-test="kontak-darurat"
                    >
                        <li
                            v-for="k in event.kontak_darurat"
                            :key="k.nomor"
                            class="flex items-center gap-3"
                        >
                            <span
                                class="flex size-9 shrink-0 items-center justify-center rounded-full bg-red-100 dark:bg-red-500/20"
                            >
                                <Phone class="size-4 text-red-600" />
                            </span>
                            <div class="min-w-0">
                                <p class="truncate text-sm font-semibold">
                                    {{ k.nama }}
                                </p>
                                <a
                                    :href="`tel:${k.nomor}`"
                                    class="text-xs font-medium text-[#17564C] hover:underline dark:text-emerald-300"
                                >
                                    {{ k.nomor }}
                                </a>
                            </div>
                        </li>
                    </ul>
                    <p
                        v-else
                        class="py-4 text-sm text-neutral-500 dark:text-muted-foreground"
                    >
                        Belum ada kontak darurat untuk event ini.
                    </p>
                </DashCard>
            </div>
        </div>
    </div>
</template>
