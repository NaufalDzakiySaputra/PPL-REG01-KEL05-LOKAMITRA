<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { onMounted, ref } from 'vue';
import {
    ArrowLeft,
    Building2,
    UserRound,
    BriefcaseBusiness,
    Info,
} from '@lucide/vue';
import { api, ApiError } from '@/lib/api';

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

type EventOption = {
    id: number;
    nama: string;
    status: string;
};

const props = defineProps<{
    stakeholder: Stakeholder;
}>();

const events = ref<EventOption[]>([]);
const eventId = ref('');
const pesan = ref('');
const loading = ref(false);
const mengirim = ref(false);
const errorMessage = ref('');
const successMessage = ref('');

async function ambilEvent() {
    loading.value = true;
    errorMessage.value = '';

    try {
        const response = await api.get<{ data: EventOption[] }>(
            '/api/v1/eo/events/options',
        );

        events.value = response.data;
    } catch (error) {
        errorMessage.value =
            error instanceof Error
                ? error.message
                : 'Gagal mengambil daftar event.';
    } finally {
        loading.value = false;
    }
}

async function kirimPermintaan() {
    if (!eventId.value) {
        errorMessage.value = 'Silakan pilih event terlebih dahulu.';
        return;
    }

    mengirim.value = true;
    errorMessage.value = '';
    successMessage.value = '';

    try {
        const response = await api.post<{
            message: string;
        }>('/api/v1/eo/partnership-requests', {
            event_id: Number(eventId.value),
            stakeholder_akun_id: props.stakeholder.id,
            pesan: pesan.value || null,
        });

        successMessage.value = response.message;
        eventId.value = '';
        pesan.value = '';
    } catch (error) {
        errorMessage.value =
            error instanceof ApiError
                ? error.message
                : 'Gagal mengirim permintaan kerja sama.';
    } finally {
        mengirim.value = false;
    }
}

onMounted(ambilEvent);

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
        <div class="flex items-center justify-between">
            <Link
                href="/eo/stakeholders"
                class="inline-flex items-center gap-2 text-sm font-semibold text-neutral-500 hover:text-[#17564C]"
            >
                <ArrowLeft class="size-4" />
                Kembali
            </Link>

            <span class="hidden text-xs text-neutral-400 sm:block">
                LokaMitra / Stakeholder / Profil
            </span>
        </div>

        <!-- Profil stakeholder -->
        <section
            class="relative overflow-hidden rounded-2xl bg-[#17564C] p-6 text-white shadow-lg md:p-9"
        >
            <div
                class="relative flex flex-col gap-6 sm:flex-row sm:items-center"
            >
                <div
                    class="flex size-24 shrink-0 items-center justify-center rounded-2xl border border-white/20 bg-white/10 text-4xl font-extrabold text-[#F2B632] md:size-28"
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

                    <h1 class="text-2xl font-extrabold break-words md:text-3xl">
                        {{ stakeholder.nama }}
                    </h1>

                    <p class="text-sm text-white">
                        ID Akun: {{ stakeholder.id }}
                    </p>

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
                    class="flex size-12 items-center justify-center rounded-xl bg-[#17564C]/10 text-[#17564C]"
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

        <!-- Detail profil -->
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

            <div v-else class="px-6 py-12 text-center">
                <Info class="mx-auto mb-3 size-8 text-neutral-400" />
                <p class="font-semibold">Informasi belum tersedia</p>
            </div>
        </section>

        <!-- Form pengajuan kerja sama -->
        <section
            class="rounded-2xl border border-neutral-200 bg-white p-6 shadow-sm dark:border-border dark:bg-card"
        >
            <div class="mb-5">
                <h2 class="text-lg font-bold text-neutral-900 dark:text-white">
                    Ajukan Kerja Sama
                </h2>
                <p class="mt-1 text-sm text-neutral-500">
                    Kirim permintaan kerja sama kepada {{ stakeholder.nama }}.
                </p>
            </div>

            <form class="space-y-4" @submit.prevent="kirimPermintaan">
                <div>
                    <label for="event" class="mb-2 block text-sm font-semibold">
                        Pilih Event
                    </label>

                    <select
                        id="event"
                        v-model="eventId"
                        :disabled="loading || mengirim"
                        required
                        class="w-full rounded-xl border border-neutral-300 bg-white px-4 py-3 text-sm dark:border-border dark:bg-background"
                    >
                        <option value="" disabled>
                            {{
                                loading ? 'Memuat event...' : 'Pilih event Anda'
                            }}
                        </option>
                        <option
                            v-for="event in events"
                            :key="event.id"
                            :value="String(event.id)"
                        >
                            {{ event.nama }} ({{ event.status }})
                        </option>
                    </select>
                </div>

                <div>
                    <label for="pesan" class="mb-2 block text-sm font-semibold">
                        Pesan Pengajuan
                    </label>

                    <textarea
                        id="pesan"
                        v-model="pesan"
                        rows="4"
                        maxlength="2000"
                        placeholder="Tuliskan maksud dan kebutuhan kerja sama..."
                        :disabled="mengirim"
                        class="w-full rounded-xl border border-neutral-300 bg-white px-4 py-3 text-sm dark:border-border dark:bg-background"
                    />
                </div>

                <p v-if="errorMessage" class="text-sm text-red-600">
                    {{ errorMessage }}
                </p>

                <p
                    v-if="successMessage"
                    class="text-sm font-semibold text-green-600"
                >
                    {{ successMessage }}
                </p>

                <button
                    type="submit"
                    :disabled="loading || mengirim || events.length === 0"
                    class="rounded-xl bg-[#17564C] px-5 py-3 text-sm font-semibold text-white transition hover:bg-[#12463e] disabled:cursor-not-allowed disabled:opacity-50"
                >
                    {{
                        mengirim ? 'Mengirim...' : 'Kirim Permintaan Kerja Sama'
                    }}
                </button>
            </form>
        </section>

        <div class="flex justify-end pb-4">
            <Link
                href="/eo/stakeholders"
                class="inline-flex items-center gap-2 rounded-xl bg-[#17564C] px-5 py-3 text-sm font-semibold text-white"
            >
                <ArrowLeft class="size-4" />
                Kembali ke Direktori
            </Link>
        </div>
    </div>
</template>
