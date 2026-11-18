
<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { onMounted, ref } from 'vue';
import {
    ArrowLeft,
    Building2,
    UserRound,
    BriefcaseBusiness,
    Info,
    Handshake,
    Send,
} from '@lucide/vue';
import { api } from '@/lib/api';

type ProfileField = {
    label: string;
    value: string | number;
};

type Stakeholder = {
    id: number;
    type: string;
    nama: string;
    nama_akun: string;
    kategori: string;
    profil: ProfileField[];
};

type EventOption = {
    id: number;
    nama: string;
    status: string;
};

type StakeholderResponse = {
    data: Stakeholder;
};

type EventResponse = {
    data: EventOption[];
};

type SubmitResponse = {
    message: string;
};

const props = defineProps<{
    stakeholder: Stakeholder;
}>();

const stakeholder = ref<Stakeholder>(props.stakeholder);
const events = ref<EventOption[]>([]);
const eventId = ref('');
const pesan = ref('');
const loading = ref(true);
const submitting = ref(false);
const errorMessage = ref('');
const successMessage = ref('');

async function ambilData() {
    loading.value = true;
    errorMessage.value = '';

    try {
        const [detail, daftarEvent] = await Promise.all([
            api.get<StakeholderResponse>(
                `/api/v1/eo/stakeholders/${props.stakeholder.type}/${props.stakeholder.id}`,
            ),
            api.get<EventResponse>('/api/v1/eo/events/options'),
        ]);

        stakeholder.value = detail.data;
        events.value = daftarEvent.data;
    } catch (error) {
        errorMessage.value =
            error instanceof Error
                ? error.message
                : 'Gagal mengambil data stakeholder dan event.';
    } finally {
        loading.value = false;
    }
}

async function kirimPermintaan() {
    errorMessage.value = '';
    successMessage.value = '';

    if (!eventId.value) {
        errorMessage.value = 'Silakan pilih event terlebih dahulu.';
        return;
    }

    submitting.value = true;

    try {
        const response = await api.post<SubmitResponse>(
            '/api/v1/eo/partnership-requests',
            {
                event_id: Number(eventId.value),
                stakeholder_akun_id: stakeholder.value.id,
                pesan: pesan.value.trim() || null,
            },
        );

        successMessage.value = response.message;
        pesan.value = '';
        eventId.value = '';
    } catch (error) {
        errorMessage.value =
            error instanceof Error
                ? error.message
                : 'Permintaan kerja sama gagal dikirim.';
    } finally {
        submitting.value = false;
    }
}

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Direktori Stakeholder', href: '/eo/stakeholders' },
            { title: 'Detail Profil', href: '#' },
        ],
    },
});

onMounted(ambilData);
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
                <ArrowLeft class="size-4" />
                Kembali ke direktori
            </Link>

            <span class="hidden text-xs text-neutral-400 sm:block">
                LokaMitra / Stakeholder / Profil
            </span>
        </div>

        <div
            v-if="loading"
            class="rounded-xl border border-neutral-200 bg-white p-5 text-sm text-neutral-500 dark:border-border dark:bg-card"
        >
            Memuat informasi stakeholder...
        </div>

        <template v-else>
            <!-- Profile Hero -->
            <section
                class="relative overflow-hidden rounded-2xl bg-[#17564C] p-6 text-white shadow-lg md:p-9"
            >
                <div
                    class="pointer-events-none absolute -right-16 -top-24 size-72 rounded-full border-[35px] border-white/[0.04]"
                />
                <div
                    class="pointer-events-none absolute -bottom-28 right-36 size-64 rounded-full bg-[#F2B632]/10 blur-3xl"
                />

                <div class="relative flex flex-col gap-6 sm:flex-row sm:items-center">
                    <div
                        class="flex size-24 shrink-0 items-center justify-center rounded-2xl border border-white/20 bg-white/10 text-4xl font-extrabold text-[#F2B632] shadow-inner backdrop-blur-sm md:size-28"
                    >
                        {{ stakeholder.nama.charAt(0).toUpperCase() }}
                    </div>

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

                        <h1 class="break-words text-2xl font-extrabold tracking-tight md:text-3xl">
                            {{ stakeholder.nama }}
                        </h1>

                        <div class="mt-3 flex flex-wrap items-center gap-x-5 gap-y-2 text-sm text-white/60">
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
                        untuk mendukung kolaborasi dan kebutuhan penyelenggaraan event.
                    </p>
                </div>
            </section>

            <!-- Ringkasan -->
            <div class="grid gap-4 sm:grid-cols-2">
                <div
                    class="flex items-center gap-4 rounded-2xl border border-neutral-200 bg-white p-5 dark:border-border dark:bg-card"
                >
                    <div class="flex size-12 items-center justify-center rounded-xl bg-[#17564C]/10 text-[#17564C] dark:text-emerald-400">
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
                    <div class="flex size-12 items-center justify-center rounded-xl bg-[#F2B632]/15 text-amber-600">
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
                <div class="border-b border-neutral-100 px-6 py-5 dark:border-border">
                    <div class="flex items-center gap-3">
                        <div class="flex size-10 items-center justify-center rounded-xl bg-[#17564C]/10 text-[#17564C]">
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
                        <p class="mb-3 text-xs font-semibold uppercase tracking-wider text-neutral-500">
                            {{ item.label }}
                        </p>
                        <p class="break-words text-base font-bold leading-relaxed text-neutral-800 dark:text-neutral-100">
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

            <!-- Form Pengajuan Kerja Sama -->
            <section
                class="overflow-hidden rounded-2xl border border-neutral-200 bg-white shadow-sm dark:border-border dark:bg-card"
            >
                <div class="border-b border-neutral-100 px-6 py-5 dark:border-border">
                    <div class="flex items-center gap-3">
                        <div class="flex size-10 items-center justify-center rounded-xl bg-[#17564C]/10 text-[#17564C]">
                            <Handshake class="size-5" />
                        </div>
                        <div>
                            <h2 class="font-bold text-neutral-900 dark:text-white">
                                Ajukan Kerja Sama
                            </h2>
                            <p class="mt-1 text-xs text-neutral-500">
                                Kirim permintaan kolaborasi kepada stakeholder ini.
                            </p>
                        </div>
                    </div>
                </div>

                <form class="space-y-5 p-5 md:p-6" @submit.prevent="kirimPermintaan">
                    <div>
                        <label
                            for="event_id"
                            class="mb-2 block text-sm font-semibold text-neutral-700 dark:text-neutral-200"
                        >
                            Pilih Event <span class="text-red-500">*</span>
                        </label>

                        <select
                            id="event_id"
                            v-model="eventId"
                            required
                            class="w-full rounded-xl border border-neutral-200 bg-white px-4 py-3 text-sm outline-none transition focus:border-[#17564C] focus:ring-2 focus:ring-[#17564C]/10 dark:border-border dark:bg-card"
                        >
                            <option value="" disabled>Pilih event yang ingin diajukan</option>
                            <option
                                v-for="event in events"
                                :key="event.id"
                                :value="String(event.id)"
                            >
                                {{ event.nama }} — {{ event.status }}
                            </option>
                        </select>
                    </div>

                    <div>
                        <label
                            for="pesan"
                            class="mb-2 block text-sm font-semibold text-neutral-700 dark:text-neutral-200"
                        >
                            Pesan Pengajuan <span class="text-neutral-400">(Opsional)</span>
                        </label>

                        <textarea
                            id="pesan"
                            v-model="pesan"
                            rows="4"
                            maxlength="2000"
                            placeholder="Tuliskan maksud dan tujuan kerja sama..."
                            class="w-full resize-y rounded-xl border border-neutral-200 bg-white px-4 py-3 text-sm outline-none transition focus:border-[#17564C] focus:ring-2 focus:ring-[#17564C]/10 dark:border-border dark:bg-card"
                        />
                        <p class="mt-1 text-right text-xs text-neutral-400">
                            {{ pesan.length }}/2000 karakter
                        </p>
                    </div>

                    <div
                        v-if="errorMessage"
                        class="rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-700 dark:border-red-900 dark:bg-red-950/30 dark:text-red-300"
                    >
                        {{ errorMessage }}
                    </div>

                    <div
                        v-if="successMessage"
                        class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-700 dark:border-emerald-900 dark:bg-emerald-950/30 dark:text-emerald-300"
                    >
                        {{ successMessage }}
                    </div>

                    <div class="flex justify-end">
                        <button
                            type="submit"
                            :disabled="submitting || loading || events.length === 0"
                            class="inline-flex items-center gap-2 rounded-xl bg-[#17564C] px-5 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-[#12473F] disabled:cursor-not-allowed disabled:opacity-50"
                        >
                            <Send class="size-4" />
                            {{ submitting ? 'Mengirim...' : 'Kirim Permintaan' }}
                        </button>
                    </div>

                    <p v-if="events.length === 0" class="text-xs text-neutral-500">
                        Belum ada event yang dapat dipilih.
                    </p>
                </form>
            </section>
        </template>

        <!-- Footer -->
        <div class="flex justify-end pb-4">
            <Link
                href="/eo/stakeholders"
                class="inline-flex items-center gap-2 rounded-xl bg-[#17564C] px-5 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-[#12473F] hover:shadow-md"
            >
                <ArrowLeft class="size-4" />
                Kembali ke Direktori
            </Link>
        </div>
    </div>
</template>
