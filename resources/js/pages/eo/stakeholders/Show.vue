<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import {
    ArrowLeft,
    Building2,
    UserRound,
    BriefcaseBusiness,
    Info,
} from '@lucide/vue';

type Stakeholder = {
    id: number;
    nama: string;
    nama_akun: string;
    kategori: string;
    profil: {
        label: string;
        value: string | number;
    }[];
};

defineProps<{
    stakeholder: Stakeholder;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Direktori Stakeholder', href: '/eo/stakeholders' },
            { title: 'Detail Profil', href: '#' },
        ],
    },
});
</script>

<template>
    <Head title="Profil Stakeholder" />

    <div class="min-h-full space-y-6 p-4 md:p-6">
        <!-- Navigasi -->
        <div class="flex items-center justify-between">
            <Link
                href="/eo/stakeholders"
                class="group inline-flex items-center gap-2 text-sm font-semibold text-neutral-500 transition hover:text-[#17564C] dark:text-neutral-400"
            >
            </Link>

            <span class="hidden text-xs text-neutral-400 sm:block">
                LokaMitra / Stakeholder / Profil
            </span>
        </div>

        <!-- Profile Hero -->
        <section
            class="relative overflow-hidden rounded-2xl bg-[#17564C] p-6 text-white shadow-lg md:p-9"
        >
            <!-- Dekorasi -->
            <div
                class="pointer-events-none absolute -top-24 -right-16 size-72 rounded-full border-[35px] border-white/[0.04]"
            />
            <div
                class="pointer-events-none absolute right-36 -bottom-28 size-64 rounded-full bg-[#F2B632]/10 blur-3xl"
            />

            <div
                class="relative flex flex-col gap-6 sm:flex-row sm:items-center"
            >
                <!-- Avatar -->
                <div
                    class="flex size-24 shrink-0 items-center justify-center rounded-2xl border border-white/20 bg-white/10 text-4xl font-extrabold text-[#F2B632] shadow-inner backdrop-blur-sm md:size-28"
                >
                    {{ stakeholder.nama.charAt(0).toUpperCase() }}
                </div>

                <!-- Identitas -->
                <div class="min-w-0 flex-1">
                    <div class="mb-3 flex flex-wrap items-center gap-2">
                        <span
                            class="inline-flex items-center gap-1.5 rounded-full border border-[#F2B632]/30 bg-[#F2B632]/15 px-3 py-1 text-xs font-semibold text-[#F2B632]"
                        >
                            <Building2 class="size-3.5" />
                            {{ stakeholder.kategori }}
                        </span>

                        <span
                            class="rounded-full border border-emerald-300/20 bg-emerald-300/10 px-3 py-1 text-xs font-medium text-emerald-200"
                        >
                            Terdaftar
                        </span>
                    </div>

                    <h1
                        class="text-2xl font-extrabold tracking-tight break-words md:text-3xl"
                    >
                        {{ stakeholder.nama }}
                    </h1>

                    <div
                        class="mt-3 flex flex-wrap items-center gap-x-5 gap-y-2 text-sm text-white/60"
                    >
                        <span class="inline-flex items-center gap-2">
                            <UserRound class="size-4" />
                            {{ stakeholder.nama_akun }}
                        </span>

                        <span class="inline-flex items-center gap-2">
                            <BriefcaseBusiness class="size-4" />
                            Mitra LokaMitra
                        </span>
                    </div>
                </div>
            </div>

            <div class="relative mt-7 border-t border-white/10 pt-5">
                <p class="text-xs leading-relaxed text-white/60">
                    Profil stakeholder yang terdaftar dalam ekosistem LokaMitra
                    untuk mendukung kolaborasi dan kebutuhan penyelenggaraan
                    event.
                </p>
            </div>
        </section>

        <!-- Ringkasan -->
        <div class="grid gap-4 sm:grid-cols-2">
            <div
                class="flex items-center gap-4 rounded-2xl border border-neutral-200 bg-white p-5 dark:border-border dark:bg-card"
            >
                <div
                    class="flex size-12 items-center justify-center rounded-xl bg-[#17564C]/10 text-[#17564C] dark:text-emerald-400"
                >
                    <UserRound class="size-6" />
                </div>
                <div>
                    <p class="text-xs text-neutral-500">Nama Stakeholder</p>
                    <p class="mt-1 font-bold">{{ stakeholder.nama }}</p>
                </div>
            </div>

            <div
                class="flex items-center gap-4 rounded-2xl border border-neutral-200 bg-white p-5 dark:border-border dark:bg-card"
            >
                <div
                    class="flex size-12 items-center justify-center rounded-xl bg-[#F2B632]/15 text-amber-600"
                >
                    <Building2 class="size-6" />
                </div>
                <div>
                    <p class="text-xs text-neutral-500">Kategori</p>
                    <p class="mt-1 font-bold">{{ stakeholder.kategori }}</p>
                </div>
            </div>
        </div>

        <!-- Detail Informasi -->
        <section
            class="overflow-hidden rounded-2xl border border-neutral-200 bg-white shadow-sm dark:border-border dark:bg-card"
        >
            <div
                class="border-b border-neutral-100 px-6 py-5 dark:border-border"
            >
                <div class="flex items-center gap-3">
                    <div
                        class="flex size-10 items-center justify-center rounded-xl bg-[#17564C]/10 text-[#17564C]"
                    >
                        <Info class="size-5" />
                    </div>
                    <div>
                        <h2 class="font-bold text-neutral-900 dark:text-white">
                            Informasi Profil
                        </h2>
                        <p class="mt-1 text-xs text-neutral-500">
                            Informasi lengkap mengenai stakeholder.
                        </p>
                    </div>
                </div>
            </div>

            <div
                v-if="stakeholder.profil.length"
                class="grid grid-cols-1 gap-4 p-5 sm:grid-cols-2 md:p-6"
            >
                <div
                    v-for="(item, index) in stakeholder.profil"
                    :key="index"
                    class="min-w-0 rounded-xl border border-neutral-200 bg-neutral-50/70 p-5 dark:border-border dark:bg-muted/20"
                >
                    <p
                        class="mb-3 text-xs font-semibold tracking-wider text-neutral-500 uppercase"
                    >
                        {{ item.label }}
                    </p>

                    <p
                        class="text-base leading-relaxed font-bold break-words text-neutral-800 dark:text-neutral-100"
                    >
                        {{ item.value }}
                    </p>
                </div>
            </div>

            <div
                v-else
                class="flex flex-col items-center px-6 py-12 text-center"
            >
                <Info class="mb-3 size-8 text-neutral-400" />
                <p class="font-semibold">Informasi belum tersedia</p>
                <p class="mt-1 text-sm text-neutral-500">
                    Belum ada informasi profil tambahan dari stakeholder ini.
                </p>
            </div>
        </section>

        <!-- Footer -->
        <div class="flex justify-end pb-4">
            <Link
                href="/eo/stakeholders"
                class="inline-flex items-center gap-2 rounded-xl bg-[#17564C] px-5 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-[#17564C] hover:shadow-md"
            >
                <ArrowLeft class="size-4" />
                Kembali ke Direktori
            </Link>
        </div>
    </div>
</template>
