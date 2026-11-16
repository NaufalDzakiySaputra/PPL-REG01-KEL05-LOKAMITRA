<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import DashCard from '@/components/dashboard/DashCard.vue';
import StatCard from '@/components/dashboard/StatCard.vue';
import type { MitraDashboardProps } from '@/types/dashboard';

// Isi dashboard bersama untuk Vendor, Pemda, Komunitas, Akademisi, dan Media.
defineProps<MitraDashboardProps>();

const labelStatus: Record<string, string> = {
    belum_mulai: 'Belum mulai',
    berjalan: 'Berjalan',
    selesai: 'Selesai',
};
</script>

<template>
    <div class="flex flex-col gap-4 p-4 md:p-6">
        <header class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <h1
                    class="text-2xl font-extrabold"
                    data-test="mitra-dashboard-title"
                >
                    Dashboard {{ peran }}
                </h1>
                <p class="text-sm text-neutral-600 dark:text-muted-foreground">
                    Selamat datang, {{ $page.props.auth.user?.name }}. Pantau
                    tugas dan event yang Anda ikuti.
                </p>
            </div>
            <span
                v-if="statusVerifikasi"
                class="rounded-full bg-emerald-100 px-3 py-1 text-xs font-semibold text-emerald-800"
            >
                Profil: {{ statusVerifikasi }}
            </span>
        </header>

        <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <StatCard
                label="Tugas selesai"
                :nilai="`${statistik.tugasSelesai}/${statistik.tugasTotal}`"
                :catatan="`${statistik.tugasTerlambat} tugas terlambat`"
                :nada-catatan="statistik.tugasTerlambat > 0 ? 'kuning' : 'abu'"
            />
            <StatCard
                label="Event diikuti"
                :nilai="statistik.eventDiikuti"
                :catatan="`${statistik.pengajuanMenunggu} pengajuan menunggu`"
            />
            <StatCard
                label="Event tersedia"
                :nilai="statistik.eventTersedia"
                catatan="Dipublikasikan dan belum selesai"
            />
            <StatCard
                label="Tugas aktif"
                :nilai="statistik.tugasTotal - statistik.tugasSelesai"
                catatan="Belum diselesaikan"
            />
        </section>

        <section class="grid gap-4 lg:grid-cols-2">
            <DashCard judul="Tugas terdekat">
                <p
                    v-if="tugasTerdekat.length === 0"
                    class="py-6 text-center text-sm text-neutral-500"
                >
                    Belum ada tugas untuk Anda.
                </p>
                <ul v-else class="space-y-4">
                    <li
                        v-for="(t, i) in tugasTerdekat"
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
                        <div class="min-w-0">
                            <p class="truncate text-sm font-semibold">
                                {{ t.judul }}
                            </p>
                            <p class="truncate text-xs text-neutral-500">
                                {{ t.event }} ·
                                {{ labelStatus[t.status] ?? t.status }}
                            </p>
                        </div>
                    </li>
                </ul>
            </DashCard>

            <DashCard judul="Event yang diikuti">
                <p
                    v-if="eventDiikuti.length === 0"
                    class="py-6 text-center text-sm text-neutral-500"
                >
                    Anda belum tergabung di event mana pun.
                </p>
                <ul v-else class="divide-y divide-neutral-200/80">
                    <li v-for="e in eventDiikuti" :key="e.id" class="py-3">
                        <p class="text-sm font-semibold">{{ e.nama }}</p>
                        <p class="text-xs text-neutral-500">
                            {{ e.tanggal }} · {{ e.lokasi }}
                        </p>
                    </li>
                </ul>
            </DashCard>
        </section>

        <section class="grid gap-4 lg:grid-cols-2">
            <DashCard judul="Event yang bisa diikuti">
                <p
                    v-if="eventTersedia.length === 0"
                    class="py-6 text-center text-sm text-neutral-500"
                >
                    Belum ada event baru.
                </p>
                <ul v-else class="divide-y divide-neutral-200/80">
                    <li v-for="e in eventTersedia" :key="e.id" class="py-3">
                        <p class="text-sm font-semibold">{{ e.nama }}</p>
                        <p class="text-xs text-neutral-500">
                            {{ e.tanggal }} · {{ e.lokasi }}
                        </p>
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
                <ul v-else class="divide-y divide-neutral-200/80">
                    <li
                        v-for="(a, i) in aktivitas"
                        :key="i"
                        class="py-3 text-sm"
                    >
                        <p>{{ a.teks }}</p>
                        <p class="text-xs text-neutral-500">{{ a.waktu }}</p>
                    </li>
                </ul>
            </DashCard>
        </section>

        <DashCard judul="Profil">
            <template #aksi>
                <Link
                    href="/profil-peran"
                    class="text-xs font-semibold text-[#17564C] hover:underline"
                >
                    Ubah profil
                </Link>
            </template>
            <dl class="grid gap-3 sm:grid-cols-2">
                <div v-for="item in profil" :key="item.label">
                    <dt class="text-xs text-neutral-500">{{ item.label }}</dt>
                    <dd class="text-sm font-medium">{{ item.value }}</dd>
                </div>
            </dl>
        </DashCard>
    </div>
</template>
