<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import {
    ArrowUpRight,
    BellRing,
    BriefcaseBusiness,
    CalendarRange,
    Sparkles,
    TrendingUp,
    Users,
} from '@lucide/vue';
import { dashboard } from '@/routes';

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Dashboard',
                href: dashboard(),
            },
        ],
    },
});

const stats = [
    {
        label: 'Layanan aktif',
        value: '128',
        delta: '+12%',
        icon: BriefcaseBusiness,
        accent: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-300',
    },
    {
        label: 'Mitra terdaftar',
        value: '84',
        delta: '+8%',
        icon: Users,
        accent: 'bg-sky-100 text-sky-700 dark:bg-sky-500/15 dark:text-sky-300',
    },
    {
        label: 'Pendapatan bulan ini',
        value: 'Rp 24.8Jt',
        delta: '+15%',
        icon: TrendingUp,
        accent: 'bg-violet-100 text-violet-700 dark:bg-violet-500/15 dark:text-violet-300',
    },
];

const activities = [
    {
        title: 'Permintaan baru dari Pak Rudy',
        detail: 'Butuh bantuan servis AC rumah tinggal',
        time: '10 menit lalu',
        tone: 'bg-emerald-500/10 text-emerald-700 dark:text-emerald-300',
    },
    {
        title: 'Jadwal teknisi diperbarui',
        detail: 'Tim lapangan siap berangkat pagi ini',
        time: '1 jam lalu',
        tone: 'bg-sky-500/10 text-sky-700 dark:text-sky-300',
    },
    {
        title: 'Review layanan pelanggan',
        detail: '2 ulasan baru dengan rating 5.0',
        time: '3 jam lalu',
        tone: 'bg-violet-500/10 text-violet-700 dark:text-violet-300',
    },
];

const priorities = [
    { label: 'Penjualan', value: 78 },
    { label: 'Onboarding mitra', value: 64 },
    { label: 'Kepuasan pelanggan', value: 88 },
];
</script>

<template>
    <Head title="Dashboard" />

    <div class="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-4">
        <header class="flex flex-col gap-4 rounded-2xl border border-sidebar-border/70 bg-white/80 p-5 shadow-sm backdrop-blur dark:border-sidebar-border dark:bg-slate-900/80 md:flex-row md:items-center md:justify-between">
            <div>
                <p class="mb-1 inline-flex items-center gap-2 rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-medium text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-300">
                    <Sparkles class="h-3.5 w-3.5" />
                    Lokamitra overview
                </p>
                <h1 class="mt-2 text-2xl font-semibold text-slate-900 dark:text-white">
                    Selamat datang kembali, {{ $page.props.auth.user?.name ?? 'Admin' }}
                </h1>
                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                    Berikut ringkasan aktivitas dan performa operasional hari ini.
                </p>
            </div>

            <div class="flex items-center gap-3 self-start md:self-auto">
                <button class="inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-100 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700">
                    <BellRing class="h-4 w-4" />
                    Notifikasi
                </button>
                <button class="inline-flex items-center gap-2 rounded-lg bg-slate-900 px-3 py-2 text-sm font-medium text-white transition hover:bg-slate-800 dark:bg-emerald-500 dark:text-slate-950 dark:hover:bg-emerald-400">
                    <CalendarRange class="h-4 w-4" />
                    Lihat agenda
                </button>
            </div>
        </header>

        <section class="grid gap-4 md:grid-cols-3">
            <div v-for="stat in stats" :key="stat.label" class="rounded-2xl border border-sidebar-border/70 bg-white p-4 shadow-sm dark:border-sidebar-border dark:bg-slate-900">
                <div class="flex items-center justify-between">
                    <div :class="['rounded-xl p-2.5', stat.accent]">
                        <component :is="stat.icon" class="h-5 w-5" />
                    </div>
                    <span class="inline-flex items-center gap-1 text-xs font-semibold text-emerald-600 dark:text-emerald-300">
                        {{ stat.delta }}
                        <ArrowUpRight class="h-3.5 w-3.5" />
                    </span>
                </div>
                <p class="mt-4 text-sm text-slate-500 dark:text-slate-400">{{ stat.label }}</p>
                <p class="mt-2 text-3xl font-bold tracking-tight text-slate-900 dark:text-white">{{ stat.value }}</p>
            </div>
        </section>

        <section class="grid gap-6 xl:grid-cols-[1.6fr_0.9fr]">
            <div class="rounded-2xl border border-sidebar-border/70 bg-white p-5 shadow-sm dark:border-sidebar-border dark:bg-slate-900">
                <div class="mb-4 flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-slate-500 dark:text-slate-400">Aktivitas terbaru</p>
                        <h2 class="text-xl font-semibold text-slate-900 dark:text-white">Timeline operasional</h2>
                    </div>
                    <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-600 dark:bg-slate-800 dark:text-slate-300">Hari ini</span>
                </div>

                <div class="space-y-4">
                    <div v-for="item in activities" :key="item.title" class="flex gap-4 rounded-xl border border-slate-200 bg-slate-50 p-3 dark:border-slate-700 dark:bg-slate-800/70">
                        <div :class="['mt-0.5 h-3 w-3 rounded-full', item.tone]" />
                        <div class="flex-1">
                            <div class="flex items-center justify-between gap-3">
                                <p class="font-medium text-slate-800 dark:text-slate-100">{{ item.title }}</p>
                                <span class="text-xs text-slate-500 dark:text-slate-400">{{ item.time }}</span>
                            </div>
                            <p class="mt-1 text-sm text-slate-600 dark:text-slate-300">{{ item.detail }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="rounded-2xl border border-sidebar-border/70 bg-white p-5 shadow-sm dark:border-sidebar-border dark:bg-slate-900">
                <p class="text-sm font-medium text-slate-500 dark:text-slate-400">Target performa</p>
                <h2 class="mt-1 text-xl font-semibold text-slate-900 dark:text-white">Prioritas bulan ini</h2>

                <div class="mt-5 space-y-5">
                    <div v-for="priority in priorities" :key="priority.label">
                        <div class="mb-2 flex items-center justify-between text-sm">
                            <span class="font-medium text-slate-700 dark:text-slate-200">{{ priority.label }}</span>
                            <span class="text-slate-500 dark:text-slate-400">{{ priority.value }}%</span>
                        </div>
                        <div class="h-2.5 rounded-full bg-slate-100 dark:bg-slate-800">
                            <div
                                class="h-2.5 rounded-full bg-gradient-to-r from-emerald-500 via-sky-500 to-violet-500"
                                :style="{ width: priority.value + '%' }"
                            />
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
</template>
