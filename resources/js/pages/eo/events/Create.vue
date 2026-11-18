<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { reactive, ref } from 'vue';
import { toast } from 'vue-sonner';
import DashCard from '@/components/dashboard/DashCard.vue';
import InputError from '@/components/InputError.vue';
import { ApiError, api } from '@/lib/api';
import type { ApiResponse } from '@/types/api';

defineProps<{ kategori: string[] }>();

type EventResourceData = {
    id: number;
    nama_event: string;
    kategori_event: string | null;
    tanggal_mulai: string;
    tanggal_selesai: string;
    periode: string;
    lokasi: string;
    deskripsi: string | null;
    status: string;
    status_label: string;
};

const hariIni = new Date(Date.now() - new Date().getTimezoneOffset() * 60000)
    .toISOString()
    .slice(0, 10);

const form = reactive({
    nama_event: '',
    kategori_event: '',
    tanggal_mulai: '',
    tanggal_selesai: '',
    lokasi: '',
    deskripsi: '',
});

const errors = ref<Record<string, string>>({});
const processing = ref(false);

function samakanTanggal(): void {
    if (
        form.tanggal_mulai &&
        form.tanggal_selesai &&
        form.tanggal_selesai < form.tanggal_mulai
    ) {
        form.tanggal_selesai = form.tanggal_mulai;
    }
}

async function simpan(): Promise<void> {
    processing.value = true;
    errors.value = {};

    try {
        const response = await api.post<ApiResponse<EventResourceData>>(
            '/api/v1/eo/events',
            {
                nama_event: form.nama_event,
                kategori_event: form.kategori_event || null,
                tanggal_mulai: form.tanggal_mulai,
                tanggal_selesai: form.tanggal_selesai,
                lokasi: form.lokasi,
                deskripsi: form.deskripsi || null,
            },
        );

        toast.success(
            response.message ??
                'Event baru berhasil dibuat dan menunggu verifikasi.',
        );
        router.visit('/eo/events');
    } catch (error) {
        if (error instanceof ApiError) {
            errors.value = error.fieldErrors;
            toast.error(error.message);
        } else {
            toast.error('Terjadi kesalahan saat menyimpan event.');
        }
    } finally {
        processing.value = false;
    }
}

const kelasInput =
    'w-full rounded-lg border border-input bg-background px-3 py-2 text-sm outline-none transition focus-visible:ring-2 focus-visible:ring-ring aria-[invalid=true]:border-destructive';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Event', href: '/eo/events' },
            { title: 'Buat Event Baru', href: '/eo/events/buat' },
        ],
    },
});
</script>

<template>
    <Head title="Buat Event Baru" />

    <div class="flex flex-col gap-4 p-4 md:p-6">
        <header>
            <h1 class="text-2xl font-extrabold">Buat Event Baru</h1>
            <p class="text-sm text-neutral-600 dark:text-muted-foreground">
                Catat informasi awal penyelenggaraan event. Detail lain bisa
                dilengkapi setelah event tersimpan.
            </p>
        </header>

        <div class="grid items-start gap-4 lg:grid-cols-[3fr_2fr]">
            <DashCard judul="Informasi event">
                <form class="space-y-5" novalidate @submit.prevent="simpan">
                    <div class="space-y-2">
                        <label for="nama_event" class="text-sm font-medium">
                            Nama event <span class="text-destructive">*</span>
                        </label>
                        <input
                            id="nama_event"
                            v-model="form.nama_event"
                            type="text"
                            maxlength="255"
                            placeholder="Contoh: Festival Budaya Banyumas"
                            :class="kelasInput"
                            :aria-invalid="Boolean(errors.nama_event)"
                        />
                        <InputError :message="errors.nama_event" />
                    </div>

                    <div class="space-y-2">
                        <label for="kategori_event" class="text-sm font-medium">
                            Kategori
                        </label>
                        <select
                            id="kategori_event"
                            v-model="form.kategori_event"
                            :class="kelasInput"
                            :aria-invalid="Boolean(errors.kategori_event)"
                        >
                            <option value="">Pilih kategori</option>
                            <option v-for="k in kategori" :key="k" :value="k">
                                {{ k }}
                            </option>
                        </select>
                        <InputError :message="errors.kategori_event" />
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div class="space-y-2">
                            <label
                                for="tanggal_mulai"
                                class="text-sm font-medium"
                            >
                                Tanggal mulai
                                <span class="text-destructive">*</span>
                            </label>
                            <input
                                id="tanggal_mulai"
                                v-model="form.tanggal_mulai"
                                type="date"
                                :min="hariIni"
                                :class="kelasInput"
                                :aria-invalid="Boolean(errors.tanggal_mulai)"
                                @change="samakanTanggal"
                            />
                            <InputError :message="errors.tanggal_mulai" />
                        </div>
                        <div class="space-y-2">
                            <label
                                for="tanggal_selesai"
                                class="text-sm font-medium"
                            >
                                Tanggal selesai
                                <span class="text-destructive">*</span>
                            </label>
                            <input
                                id="tanggal_selesai"
                                v-model="form.tanggal_selesai"
                                type="date"
                                :min="form.tanggal_mulai || hariIni"
                                :class="kelasInput"
                                :aria-invalid="Boolean(errors.tanggal_selesai)"
                            />
                            <InputError :message="errors.tanggal_selesai" />
                        </div>
                    </div>

                    <div class="space-y-2">
                        <label for="lokasi" class="text-sm font-medium">
                            Lokasi <span class="text-destructive">*</span>
                        </label>
                        <input
                            id="lokasi"
                            v-model="form.lokasi"
                            type="text"
                            maxlength="255"
                            placeholder="Contoh: Alun-alun Purwokerto, Banyumas, Jawa Tengah"
                            :class="kelasInput"
                            :aria-invalid="Boolean(errors.lokasi)"
                        />
                        <InputError :message="errors.lokasi" />
                    </div>

                    <div class="space-y-2">
                        <label for="deskripsi" class="text-sm font-medium">
                            Deskripsi
                        </label>
                        <textarea
                            id="deskripsi"
                            v-model="form.deskripsi"
                            rows="5"
                            maxlength="5000"
                            placeholder="Ceritakan tujuan dan gambaran singkat event."
                            :class="kelasInput"
                            :aria-invalid="Boolean(errors.deskripsi)"
                        />
                        <InputError :message="errors.deskripsi" />
                    </div>

                    <div
                        class="flex flex-col-reverse gap-2 border-t pt-4 sm:flex-row sm:justify-end"
                    >
                        <Link
                            href="/eo/events"
                            class="inline-flex items-center justify-center rounded-lg border border-[#17564C] px-5 py-2.5 text-sm font-semibold text-[#17564C] hover:bg-[#17564C]/5"
                        >
                            Batal
                        </Link>
                        <button
                            type="submit"
                            :disabled="processing"
                            class="inline-flex items-center justify-center rounded-lg bg-[#17564C] px-5 py-2.5 text-sm font-semibold text-white hover:opacity-90 disabled:cursor-not-allowed disabled:opacity-50"
                            data-test="simpan-event"
                        >
                            {{ processing ? 'Menyimpan...' : 'Simpan event' }}
                        </button>
                    </div>
                </form>
            </DashCard>

            <DashCard judul="Setelah event disimpan">
                <p class="mb-4 text-xs text-neutral-500">
                    Event baru belum tampil sebagai event publik.
                </p>
                <ol class="space-y-4">
                    <li class="flex gap-3">
                        <span
                            class="flex size-6 shrink-0 items-center justify-center rounded-full bg-[#17564C] text-xs font-bold text-white"
                        >
                            1
                        </span>
                        <div>
                            <p class="text-sm font-semibold">Draft</p>
                            <p class="text-xs text-neutral-500">
                                Event tercatat di akun Anda.
                            </p>
                        </div>
                    </li>
                    <li class="flex gap-3">
                        <span
                            class="flex size-6 shrink-0 items-center justify-center rounded-full bg-neutral-200 text-xs font-bold text-neutral-700"
                        >
                            2
                        </span>
                        <div>
                            <p class="text-sm font-semibold">
                                Verifikasi Admin
                            </p>
                            <p class="text-xs text-neutral-500">
                                Admin memeriksa kelengkapan data.
                            </p>
                        </div>
                    </li>
                    <li class="flex gap-3">
                        <span
                            class="flex size-6 shrink-0 items-center justify-center rounded-full bg-neutral-200 text-xs font-bold text-neutral-700"
                        >
                            3
                        </span>
                        <div>
                            <p class="text-sm font-semibold">Tampil publik</p>
                            <p class="text-xs text-neutral-500">
                                Terlihat setelah event dipublikasikan.
                            </p>
                        </div>
                    </li>
                </ol>
            </DashCard>
        </div>
    </div>
</template>
