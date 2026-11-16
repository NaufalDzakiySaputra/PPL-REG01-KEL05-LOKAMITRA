<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Bell } from '@lucide/vue';
import DashCard from '@/components/dashboard/DashCard.vue';
import StatCard from '@/components/dashboard/StatCard.vue';

// Dashboard EO (Figma "Dashboard Event"). Data dari EoDashboardService.
defineProps<{
    notifikasiBelumDibaca: number;
    profil: { nama: string | null; status_verifikasi: string | null };
    event: {
        id: number;
        nama: string;
        kategori: string | null;
        lokasi: string | null;
        status: string;
        tanggalMulai: string;
        hariMenuju: number;
        href: string | null;
    } | null;
    ringkasan?: {
        persiapanPersen: number;
        kebutuhanSiap: number;
        kebutuhanTotal: number;
        tugasSelesai: number;
        tugasTotal: number;
        tugasBerjalan: number;
        stakeholder: number;
        jenisStakeholder: number;
    };
    kebutuhan?: { judul: string; keterangan: string; status: string }[];
    tenggat?: { judul: string; untuk: string; hari: string; bulan: string }[];
    progresStakeholder?: {
        peran: string;
        selesai: number;
        total: number;
        persen: number;
    }[];
    aktivitas?: { oleh: string; teks: string; waktu: string }[];
}>();

const labelStatusEvent: Record<string, { teks: string; kelas: string }> = {
    dipublikasikan: {
        teks: 'Terverifikasi Admin',
        kelas: 'bg-emerald-100 text-emerald-800',
    },
    menunggu_verifikasi: {
        teks: 'Menunggu verifikasi Admin',
        kelas: 'bg-amber-100 text-amber-800',
    },
    draft: { teks: 'Draft', kelas: 'bg-neutral-200 text-neutral-700' },
};

const labelKebutuhan: Record<string, { teks: string; kelas: string }> = {
    selesai: { teks: 'Selesai', kelas: 'bg-emerald-100 text-emerald-800' },
    terkonfirmasi: {
        teks: 'Terkonfirmasi',
        kelas: 'bg-emerald-100 text-emerald-800',
    },
    tersusun: { teks: 'Tersusun', kelas: 'bg-emerald-100 text-emerald-800' },
    berjalan: { teks: 'Berjalan', kelas: 'bg-amber-100 text-amber-800' },
    menunggu: { teks: 'Menunggu', kelas: 'bg-blue-100 text-blue-800' },
    draft: { teks: 'Draft', kelas: 'bg-neutral-200 text-neutral-700' },
};

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Dashboard Event', href: '/eo/dashboard' }],
    },
});
</script>

<template>
    <Head title="Dashboard Event" />

    <div class="flex flex-col gap-4 p-4 md:p-6">
        <header class="flex items-start justify-between gap-3">
            <div>
                <h1
                    class="text-2xl font-extrabold"
                    data-test="eo-dashboard-title"
                >
                    Dashboard Event
                </h1>
                <p class="text-sm text-neutral-600 dark:text-muted-foreground">
                    Pantau kelengkapan persiapan dan progres pelaksanaan event
                    yang Anda kelola.
                </p>
            </div>
            <div
                class="relative flex size-10 shrink-0 items-center justify-center rounded-xl border border-neutral-200 bg-white dark:border-border dark:bg-card"
                :title="`${notifikasiBelumDibaca} notifikasi belum dibaca`"
            >
                <Bell class="size-4" />
                <span
                    v-if="notifikasiBelumDibaca > 0"
                    class="absolute top-2 right-2 size-2 rounded-full bg-amber-500"
                />
            </div>
        </header>

        <!-- belum ada event -->
        <DashCard v-if="!event">
            <p class="text-sm font-semibold">Belum ada event aktif</p>
            <p class="mt-1 text-sm text-neutral-600 dark:text-muted-foreground">
                Buat event pertama Anda untuk mulai mengelola persiapan,
                stakeholder, dan tugas di sini.
            </p>
        </DashCard>

        <template v-else>
            <!-- event aktif -->
            <DashCard>
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <p
                            class="text-[11px] font-semibold tracking-wide text-neutral-500"
                        >
                            EVENT AKTIF
                        </p>
                        <h2 class="text-xl font-extrabold">{{ event.nama }}</h2>
                        <p class="text-xs text-neutral-500">
                            {{
                                [event.kategori, event.lokasi]
                                    .filter(Boolean)
                                    .join(' · ')
                            }}
                        </p>
                    </div>
                    <div class="flex items-center gap-3">
                        <span
                            :class="[
                                'rounded-full px-3 py-1 text-xs font-semibold',
                                (
                                    labelStatusEvent[event.status] ??
                                    labelStatusEvent.draft
                                ).kelas,
                            ]"
                        >
                            {{
                                (
                                    labelStatusEvent[event.status] ??
                                    labelStatusEvent.draft
                                ).teks
                            }}
                        </span>
                        <Link
                            v-if="event.href"
                            :href="event.href"
                            class="rounded-lg border border-[#17564C] px-4 py-2 text-sm font-semibold text-[#17564C] hover:bg-[#17564C]/5"
                        >
                            Lihat detail event
                        </Link>
                    </div>
                </div>
            </DashCard>

            <!-- kartu ringkasan -->
            <section v-if="ringkasan" class="grid gap-4 md:grid-cols-3">
                <StatCard
                    label="Persiapan"
                    :nilai="`${ringkasan.persiapanPersen}%`"
                    :catatan="`${ringkasan.kebutuhanSiap} dari ${ringkasan.kebutuhanTotal} kebutuhan siap`"
                />
                <StatCard
                    label="Tugas selesai"
                    :nilai="`${ringkasan.tugasSelesai}/${ringkasan.tugasTotal}`"
                    :catatan="`${ringkasan.tugasBerjalan} tugas masih berjalan`"
                />
                <StatCard
                    label="Stakeholder terlibat"
                    :nilai="ringkasan.stakeholder"
                    :catatan="`${ringkasan.jenisStakeholder} jenis stakeholder`"
                />
                <div class="md:col-span-3">
                    <StatCard
                        label="Hari menuju event"
                        :nilai="event.hariMenuju"
                        :catatan="event.tanggalMulai"
                    />
                </div>
            </section>

            <section class="grid gap-4 lg:grid-cols-[3fr_2fr]">
                <DashCard judul="Kelengkapan persiapan">
                    <template #aksi>
                        <span class="text-xs text-neutral-500">
                            Status per kebutuhan event
                        </span>
                    </template>
                    <ul class="divide-y divide-neutral-200/80">
                        <li
                            v-for="k in kebutuhan"
                            :key="k.judul"
                            class="flex items-center justify-between gap-3 py-3"
                        >
                            <div>
                                <p class="text-sm font-semibold">
                                    {{ k.judul }}
                                </p>
                                <p class="text-xs text-neutral-500">
                                    {{ k.keterangan }}
                                </p>
                            </div>
                            <span
                                :class="[
                                    'shrink-0 rounded-full px-3 py-1 text-xs font-semibold',
                                    (
                                        labelKebutuhan[k.status] ??
                                        labelKebutuhan.draft
                                    ).kelas,
                                ]"
                            >
                                {{
                                    (
                                        labelKebutuhan[k.status] ??
                                        labelKebutuhan.draft
                                    ).teks
                                }}
                            </span>
                        </li>
                    </ul>
                </DashCard>

                <DashCard judul="Tenggat terdekat">
                    <p
                        v-if="!tenggat || tenggat.length === 0"
                        class="py-6 text-center text-sm text-neutral-500"
                    >
                        Tidak ada tenggat mendatang.
                    </p>
                    <ul v-else class="space-y-4">
                        <li
                            v-for="(t, i) in tenggat"
                            :key="i"
                            class="flex items-center gap-3"
                        >
                            <div
                                class="flex size-12 shrink-0 flex-col items-center justify-center rounded-xl bg-[#FBE9C4] leading-tight text-[#8A5A00]"
                            >
                                <span class="text-base font-extrabold">{{
                                    t.hari
                                }}</span>
                                <span class="text-[10px] font-semibold">{{
                                    t.bulan
                                }}</span>
                            </div>
                            <div>
                                <p class="text-sm font-semibold">
                                    {{ t.judul }}
                                </p>
                                <p class="text-xs text-neutral-500">
                                    {{ t.untuk }}
                                </p>
                            </div>
                        </li>
                    </ul>
                </DashCard>
            </section>

            <section class="grid gap-4 lg:grid-cols-[3fr_2fr]">
                <DashCard judul="Progres tugas per stakeholder">
                    <ul class="space-y-4">
                        <li v-for="p in progresStakeholder" :key="p.peran">
                            <div class="mb-1 flex justify-between text-xs">
                                <span class="font-medium">{{ p.peran }}</span>
                                <span class="text-neutral-500">
                                    {{ p.selesai }}/{{ p.total }} tugas
                                </span>
                            </div>
                            <div class="h-2.5 rounded-full bg-neutral-200">
                                <div
                                    class="h-2.5 rounded-full bg-[#17564C]"
                                    :style="{ width: `${p.persen}%` }"
                                />
                            </div>
                        </li>
                    </ul>
                </DashCard>

                <DashCard judul="Aktivitas terbaru">
                    <p
                        v-if="!aktivitas || aktivitas.length === 0"
                        class="py-6 text-center text-sm text-neutral-500"
                    >
                        Belum ada aktivitas.
                    </p>
                    <ul v-else class="divide-y divide-neutral-200/80">
                        <li
                            v-for="(a, i) in aktivitas"
                            :key="i"
                            class="py-3 text-sm"
                        >
                            <p>
                                <span class="font-semibold">{{ a.oleh }}</span>
                                {{ a.teks }}
                            </p>
                            <p class="text-xs text-neutral-500">
                                {{ a.waktu }}
                            </p>
                        </li>
                    </ul>
                </DashCard>
            </section>
        </template>
    </div>
</template>
