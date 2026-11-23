<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import DashCard from '@/components/dashboard/DashCard.vue';
import StatCard from '@/components/dashboard/StatCard.vue';
import type { MitraDashboardProps } from '@/types/dashboard';

// Isi dashboard bersama untuk Vendor, Pemda, Komunitas, Akademisi, dan Media.
// Gaya visual mengikuti Dashboard Admin supaya konsisten lintas peran.
const props = defineProps<MitraDashboardProps>();

const labelStatus: Record<string, string> = {
    belum_mulai: 'Belum mulai',
    berjalan: 'Berjalan',
    selesai: 'Selesai',
};

const tautanEvent = `/${usePage().props.auth.user.role}/events`;
</script>

<template>
    <div class="flex flex-col gap-4 p-4 md:p-6">
        <header class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <h1
                    class="text-2xl font-extrabold"
                    data-test="mitra-dashboard-title"
                >
                    Dashboard {{ props.peran }}
                </h1>
                <p class="text-sm text-neutral-600 dark:text-muted-foreground">
                    Selamat datang, {{ $page.props.auth.user?.name }}. Pantau
                    tugas dan event yang Anda ikuti.
                </p>
            </div>
            <div class="flex items-center gap-2">
                <span
                    v-if="props.statusVerifikasi"
                    class="rounded-full bg-emerald-100 px-3 py-1 text-xs font-semibold text-emerald-800"
                >
                    Profil: {{ props.statusVerifikasi }}
                </span>
                <Link
                    :href="tautanEvent"
                    class="rounded-lg bg-[#245C3B] px-4 py-2 text-sm font-semibold text-white hover:bg-[#1c4a2f]"
                >
                    Jelajahi event
                </Link>
            </div>
        </header>

        <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <StatCard
                aksen="admin"
                label="Tugas selesai"
                :nilai="`${props.statistik.tugasSelesai}/${props.statistik.tugasTotal}`"
                :catatan="`${props.statistik.tugasTerlambat} tugas terlambat`"
                :nada-catatan="
                    props.statistik.tugasTerlambat > 0 ? 'kuning' : 'abu'
                "
            />
            <StatCard
                aksen="admin"
                label="Event diikuti"
                :nilai="props.statistik.eventDiikuti"
                :catatan="`${props.statistik.pengajuanMenunggu} pengajuan menunggu`"
                :nada-catatan="
                    props.statistik.pengajuanMenunggu > 0 ? 'kuning' : 'abu'
                "
            />
            <StatCard
                aksen="admin"
                label="Event tersedia"
                :nilai="props.statistik.eventTersedia"
                catatan="Dipublikasikan dan belum selesai"
                nada-catatan="hijau"
            />
            <StatCard
                aksen="admin"
                label="Tugas aktif"
                :nilai="
                    props.statistik.tugasTotal - props.statistik.tugasSelesai
                "
                catatan="Belum diselesaikan"
            />
        </section>

        <section class="grid gap-4 lg:grid-cols-[3fr_2fr]">
            <DashCard judul="Tugas terdekat">
                <template #aksi>
                    <span class="text-xs text-neutral-500">
                        {{ props.tugasTerdekat.length }} tugas terdekat
                    </span>
                </template>
                <p
                    v-if="props.tugasTerdekat.length === 0"
                    class="py-6 text-center text-sm text-neutral-500"
                >
                    Belum ada tugas untuk Anda.
                </p>
                <ul v-else class="divide-y divide-neutral-200/80">
                    <li
                        v-for="(t, i) in props.tugasTerdekat"
                        :key="i"
                        class="flex items-center gap-3 py-3"
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
                <template #aksi>
                    <span class="text-xs text-neutral-500">
                        {{ props.eventDiikuti.length }} event
                    </span>
                </template>
                <p
                    v-if="props.eventDiikuti.length === 0"
                    class="py-6 text-center text-sm text-neutral-500"
                >
                    Anda belum tergabung di event mana pun.
                </p>
                <ul v-else class="divide-y divide-neutral-200/80">
                    <li
                        v-for="e in props.eventDiikuti"
                        :key="e.id"
                        class="py-3"
                    >
                        <p class="text-sm font-semibold">{{ e.nama }}</p>
                        <p class="mt-0.5 text-xs text-neutral-500">
                            {{ e.tanggal }} · {{ e.lokasi }}
                        </p>
                    </li>
                </ul>
            </DashCard>
        </section>

        <section class="grid gap-4 lg:grid-cols-[3fr_2fr]">
            <DashCard judul="Event yang bisa diikuti">
                <template #aksi>
                    <span class="text-xs text-neutral-500">
                        {{ props.eventTersedia.length }} event
                    </span>
                </template>
                <p
                    v-if="props.eventTersedia.length === 0"
                    class="py-6 text-center text-sm text-neutral-500"
                >
                    Belum ada event baru.
                </p>
                <ul v-else class="divide-y divide-neutral-200/80">
                    <li
                        v-for="e in props.eventTersedia"
                        :key="e.id"
                        class="py-3"
                    >
                        <p class="text-sm font-semibold">{{ e.nama }}</p>
                        <p class="mt-0.5 text-xs text-neutral-500">
                            {{ e.tanggal }} · {{ e.lokasi }}
                        </p>
                    </li>
                </ul>
            </DashCard>

            <DashCard judul="Aktivitas terbaru">
                <p
                    v-if="props.aktivitas.length === 0"
                    class="py-6 text-center text-sm text-neutral-500"
                >
                    Belum ada aktivitas.
                </p>
                <ul v-else class="divide-y divide-neutral-200/80">
                    <li
                        v-for="(a, i) in props.aktivitas"
                        :key="i"
                        class="py-3 text-sm"
                    >
                        <p>{{ a.teks }}</p>
                        <p class="mt-0.5 text-xs text-neutral-500">
                            {{ a.waktu }}
                        </p>
                    </li>
                </ul>
            </DashCard>
        </section>

        <DashCard judul="Profil">
            <template #aksi>
                <Link
                    href="/profil-peran"
                    class="text-xs font-semibold text-[#245C3B] hover:underline"
                >
                    Ubah profil
                </Link>
            </template>
            <dl class="grid gap-3 sm:grid-cols-2">
                <div v-for="item in props.profil" :key="item.label">
                    <dt class="text-xs text-neutral-500">{{ item.label }}</dt>
                    <dd class="text-sm font-medium">{{ item.value }}</dd>
                </div>
            </dl>
        </DashCard>
    </div>
</template>
