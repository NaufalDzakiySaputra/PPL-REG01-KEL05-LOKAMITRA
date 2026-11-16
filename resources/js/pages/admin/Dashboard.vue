<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import DashCard from '@/components/dashboard/DashCard.vue';
import StatCard from '@/components/dashboard/StatCard.vue';

// Dashboard Admin (Figma "Dashboard Admin"). Data dari AdminDashboardService.
const props = defineProps<{
    tanggal: string;
    ringkasan: {
        totalPengguna: number;
        penggunaBulanIni: number;
        eventAktif: number;
        eventBerjalan: number;
        eventTerpublikasi: number;
        menunggu: { total: number; akun: number; event: number; dokumen: number };
        eventDiarsipkan: number;
        eventDiarsipkanBulanIni: number;
    };
    pertumbuhan: { bulan: string; jumlah: number }[];
    penggunaPerPeran: { peran: string; jumlah: number; persen: number }[];
    perluTindakan: {
        total: number;
        item: {
            tag: string;
            jenis: string;
            judul: string;
            keterangan: string;
            href: string | null;
        }[];
    };
    aktivitas: { oleh: string; teks: string; waktu: string; warna: number }[];
    statusEvent: { label: string; kunci: string; jumlah: number; persen: number }[];
}>();

const maksTumbuh = computed(() =>
    Math.max(1, ...props.pertumbuhan.map((p) => p.jumlah)),
);

const warnaTitik = [
    'bg-[#245C3B]',
    'bg-[#6BAE75]',
    'bg-[#3B6FB6]',
    'bg-[#C2562E]',
    'bg-[#7A4CA0]',
    'bg-[#9AA5A0]',
];

const warnaTag: Record<string, string> = {
    akun: 'bg-blue-100 text-blue-700',
    event: 'bg-amber-100 text-amber-700',
    dokumen: 'bg-rose-100 text-rose-700',
    arsip: 'bg-emerald-100 text-emerald-700',
};

const warnaStatus: Record<string, string> = {
    berjalan: 'bg-[#245C3B]',
    dipublikasikan: 'bg-[#6BAE75]',
    menunggu: 'bg-[#F2B632]',
    selesai: 'bg-[#9AA5A0]',
};

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Dashboard Admin', href: '/admin/dashboard' }],
    },
});
</script>

<template>
    <Head title="Dashboard Admin" />

    <div class="flex flex-col gap-4 p-4 md:p-6">
        <header class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <h1
                    class="text-2xl font-extrabold"
                    data-test="admin-dashboard-title"
                >
                    Dashboard Admin
                </h1>
                <p class="text-sm text-neutral-600 dark:text-muted-foreground">
                    Ringkasan seluruh aktivitas platform per {{ tanggal }}.
                </p>
            </div>
            <Link
                href="/admin/pengajuan"
                class="rounded-lg bg-[#245C3B] px-4 py-2 text-sm font-semibold text-white hover:bg-[#1c4a2f]"
            >
                Buka antrean verifikasi
            </Link>
        </header>

        <!-- kartu ringkasan -->
        <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <StatCard
                aksen="admin"
                label="Total pengguna"
                :nilai="ringkasan.totalPengguna"
                :catatan="`+${ringkasan.penggunaBulanIni} bulan ini`"
                nada-catatan="hijau"
            />
            <StatCard
                aksen="admin"
                label="Event aktif"
                :nilai="ringkasan.eventAktif"
                :catatan="`${ringkasan.eventBerjalan} berjalan · ${ringkasan.eventTerpublikasi} terpublikasi`"
            />
            <StatCard
                aksen="admin"
                label="Menunggu verifikasi"
                :nilai="ringkasan.menunggu.total"
                :catatan="`${ringkasan.menunggu.akun} akun · ${ringkasan.menunggu.event} event · ${ringkasan.menunggu.dokumen} dokumen`"
                nada-catatan="kuning"
            />
            <StatCard
                aksen="admin"
                label="Event diarsipkan"
                :nilai="ringkasan.eventDiarsipkan"
                :catatan="`+${ringkasan.eventDiarsipkanBulanIni} bulan ini`"
                nada-catatan="hijau"
            />
        </section>

        <!-- grafik -->
        <section class="grid gap-4 lg:grid-cols-[3fr_2fr]">
            <DashCard judul="Pertumbuhan pengguna">
                <template #aksi>
                    <span class="text-xs text-neutral-500">
                        Akun baru per bulan
                    </span>
                </template>
                <div class="flex h-48 items-end gap-3 sm:gap-5">
                    <div
                        v-for="(p, i) in pertumbuhan"
                        :key="p.bulan + i"
                        class="flex h-full flex-1 flex-col items-center justify-end gap-1"
                    >
                        <span class="text-xs font-semibold">{{ p.jumlah }}</span>
                        <div
                            :class="[
                                'w-full rounded-t-md',
                                i === pertumbuhan.length - 1
                                    ? 'bg-[#245C3B]'
                                    : 'bg-[#94BE9B]',
                            ]"
                            :style="{
                                height: `${Math.max(3, (p.jumlah / maksTumbuh) * 80)}%`,
                            }"
                        />
                        <span class="text-xs text-neutral-500">{{ p.bulan }}</span>
                    </div>
                </div>
            </DashCard>

            <DashCard judul="Pengguna per peran">
                <ul class="space-y-3">
                    <li v-for="p in penggunaPerPeran" :key="p.peran">
                        <div class="mb-1 flex justify-between text-xs">
                            <span class="font-medium">{{ p.peran }}</span>
                            <span class="font-semibold">{{ p.jumlah }}</span>
                        </div>
                        <div class="h-2 rounded-full bg-neutral-200">
                            <div
                                class="h-2 rounded-full bg-[#245C3B]"
                                :style="{ width: `${p.persen}%` }"
                            />
                        </div>
                    </li>
                </ul>
            </DashCard>
        </section>

        <!-- perlu tindakan + aktivitas -->
        <section class="grid gap-4 lg:grid-cols-[3fr_2fr]">
            <DashCard judul="Perlu tindakan">
                <template #aksi>
                    <span
                        class="rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-semibold text-amber-800"
                    >
                        {{ perluTindakan.total }} item
                    </span>
                </template>

                <p
                    v-if="perluTindakan.item.length === 0"
                    class="py-6 text-center text-sm text-neutral-500"
                >
                    Tidak ada yang perlu ditindak. 🎉
                </p>

                <ul v-else class="divide-y divide-neutral-200/80">
                    <li
                        v-for="(it, i) in perluTindakan.item"
                        :key="i"
                        class="flex items-center justify-between gap-3 py-3"
                    >
                        <div class="min-w-0">
                            <div class="flex items-center gap-2">
                                <span
                                    :class="[
                                        'shrink-0 rounded-md px-2 py-0.5 text-[11px] font-semibold',
                                        warnaTag[it.jenis] ?? 'bg-neutral-100 text-neutral-700',
                                    ]"
                                >
                                    {{ it.tag }}
                                </span>
                                <p class="truncate text-sm font-semibold">
                                    {{ it.judul }}
                                </p>
                            </div>
                            <p class="mt-0.5 truncate text-xs text-neutral-500">
                                {{ it.keterangan }}
                            </p>
                        </div>
                        <Link
                            v-if="it.href"
                            :href="it.href"
                            class="shrink-0 rounded-lg border border-[#245C3B] px-4 py-1.5 text-xs font-semibold text-[#245C3B] hover:bg-[#245C3B]/5"
                        >
                            Tinjau
                        </Link>
                        <span
                            v-else
                            class="shrink-0 cursor-not-allowed rounded-lg border border-neutral-300 px-4 py-1.5 text-xs font-semibold text-neutral-400"
                            title="Segera tersedia"
                        >
                            Tinjau
                        </span>
                    </li>
                </ul>
            </DashCard>

            <DashCard judul="Aktivitas terbaru">
                <p
                    v-if="aktivitas.length === 0"
                    class="py-6 text-center text-sm text-neutral-500"
                >
                    Belum ada aktivitas.
                </p>
                <ul v-else class="space-y-4">
                    <li
                        v-for="(a, i) in aktivitas"
                        :key="i"
                        class="flex items-start gap-3"
                    >
                        <span
                            :class="[
                                'mt-1.5 size-2 shrink-0 rounded-full',
                                warnaTitik[a.warna] ?? warnaTitik[5],
                            ]"
                        />
                        <div class="text-sm">
                            <p>
                                <span class="font-semibold">{{ a.oleh }}</span>
                                {{ a.teks }}
                            </p>
                            <p class="text-xs text-neutral-500">{{ a.waktu }}</p>
                        </div>
                    </li>
                </ul>
            </DashCard>
        </section>

        <!-- status event -->
        <DashCard judul="Status event di platform">
            <div class="flex h-3 w-full overflow-hidden rounded-full bg-neutral-200">
                <div
                    v-for="s in statusEvent"
                    :key="s.kunci"
                    :class="warnaStatus[s.kunci]"
                    :style="{ width: `${s.persen}%` }"
                />
            </div>
            <ul class="mt-3 flex flex-wrap gap-x-6 gap-y-1 text-xs">
                <li
                    v-for="s in statusEvent"
                    :key="s.kunci"
                    class="flex items-center gap-2"
                >
                    <span :class="['size-2.5 rounded-sm', warnaStatus[s.kunci]]" />
                    {{ s.label }}
                    <span class="font-bold">{{ s.jumlah }}</span>
                </li>
            </ul>
        </DashCard>
    </div>
</template>
