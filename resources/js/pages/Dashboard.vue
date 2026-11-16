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

defineProps<{
    roleLabel?: string;
}>();

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
    <Head :title="roleLabel ? `Dashboard ${roleLabel}` : 'Dashboard'" />

    <div
        class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4"
    >
        <header
            v-if="roleLabel"
            class="rounded-xl border border-sidebar-border/70 p-5 dark:border-sidebar-border"
        >
            <p class="text-sm text-muted-foreground">Lokamitra</p>
            <h1 class="mt-1 text-2xl font-semibold">
                Dashboard {{ roleLabel }}
            </h1>
            <p class="mt-1 text-sm text-muted-foreground">
                Selamat datang, {{ $page.props.auth.user?.name }}.
            </p>
        </header>

        <div class="grid auto-rows-min gap-4 md:grid-cols-3">
            <div
                class="relative aspect-video overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border"
            >
                <PlaceholderPattern />
            </div>
            <div
                class="relative aspect-video overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border"
            >
                <PlaceholderPattern />
            </div>
            <div
                class="relative aspect-video overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border"
            >
                <PlaceholderPattern />
            </div>
        </div>
        <div
            class="relative min-h-[100vh] flex-1 rounded-xl border border-sidebar-border/70 md:min-h-min dark:border-sidebar-border"
        >
            <PlaceholderPattern />
        </div>
    </div>
</template>
