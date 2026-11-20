<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { TriangleAlert } from '@lucide/vue';
import { toast } from 'vue-sonner';
import { computed, onMounted, reactive, ref } from 'vue';
import DashCard from '@/components/dashboard/DashCard.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { api, ApiError } from '@/lib/api';

const props = defineProps<{
    event: {
        id: number;
        nama: string;
        kategori: string | null;
        lokasi: string | null;
        deskripsi: string | null;
        tanggal_selesai: string;
        periode: string;
        status: string;
        dapat_diubah: boolean;
        dapat_ditutup: boolean;
        siap_diarsipkan: boolean;
        tanggal_ditutup: string | null;
        diperbarui: string;
    };
}>();

const labelStatus: Record<string, string> = {
    draft: 'Draft',
    menunggu_verifikasi: 'Menunggu verifikasi Admin',
    dipublikasikan: 'Terverifikasi Admin',
    ditutup: 'Ditutup',
    diarsipkan: 'Diarsipkan',
};

const dialogBuka = ref(false);
const memproses = ref(false);
const dapatDitutup = ref(props.event.dapat_ditutup);
const siapDiarsipkan = ref(props.event.siap_diarsipkan);
const tanggalDitutup = ref(props.event.tanggal_ditutup);

// ---- Informasi publik (PBI-035): rundown, layout/dokumen, kontak darurat ----
type ButirRundown = {
    id: number;
    judul: string;
    deskripsi: string | null;
    waktu_mulai: string | null;
    waktu_selesai: string | null;
};

type ButirDokumen = {
    id: number;
    nama: string;
    jenis: 'layout' | 'dokumen';
    unduh_url: string;
};

type ButirKontak = {
    id: number;
    nama: string;
    nomor_telepon: string;
};

type RingkasanInformasi = {
    informasi_regulasi: string | null;
    rundown: ButirRundown[];
    dokumen: ButirDokumen[];
    kontak_darurat: ButirKontak[];
    batas_kontak_darurat: number;
    dapat_diubah: boolean;
};

const alamatEvent = `/api/v1/eo/events/${props.event.id}`;
const alamatInformasi = `${alamatEvent}/informasi-publik`;

const informasi = reactive<RingkasanInformasi>({
    informasi_regulasi: null,
    rundown: [],
    dokumen: [],
    kontak_darurat: [],
    batas_kontak_darurat: 2,
    dapat_diubah: props.event.dapat_diubah,
});

const memuatInformasi = ref(true);
const memprosesRundown = ref(false);
const memprosesDokumen = ref(false);
const memprosesKontak = ref(false);
const galat = ref<Record<string, string>>({});

const formRundown = reactive({
    judul_aktivitas: '',
    deskripsi: '',
    waktu_mulai: '',
    waktu_selesai: '',
});

const formDokumen = reactive({
    nama_dokumen: '',
    jenis: 'dokumen' as 'layout' | 'dokumen',
});

const berkasDokumen = ref<File | null>(null);

const formKontak = reactive({ nama: '', nomor_telepon: '' });

// ---- Pembaruan informasi publik (PBI-036) ----
type ModeEdit =
    | { bagian: 'rundown'; id: number }
    | { bagian: 'dokumen'; id: number }
    | { bagian: 'kontak'; id: number }
    | { bagian: 'regulasi' }
    | null;

const modeEdit = ref<ModeEdit>(null);
const galatUbah = ref<Record<string, string>>({});
const memprosesUbah = ref(false);
const berkasDokumenUbah = ref<File | null>(null);

const formUbahRundown = reactive({
    judul_aktivitas: '',
    deskripsi: '',
    waktu_mulai: '',
    waktu_selesai: '',
});

const formUbahDokumen = reactive({
    nama_dokumen: '',
    jenis: 'dokumen' as 'layout' | 'dokumen',
});

const formUbahKontak = reactive({ nama: '', nomor_telepon: '' });

const formUbahRegulasi = reactive({ informasi_regulasi: '' });

const kuotaKontakPenuh = computed(
    () => informasi.kontak_darurat.length >= informasi.batas_kontak_darurat,
);

const kelasInput =
    'w-full rounded-lg border border-input bg-background px-3 py-2 text-sm outline-none transition focus-visible:ring-2 focus-visible:ring-ring aria-[invalid=true]:border-destructive';

function pilihBerkas(peristiwa: Event): void {
    const masukan = peristiwa.target as HTMLInputElement;
    berkasDokumen.value = masukan.files?.[0] ?? null;
}

function pesanGalat(error: unknown, bawaan: string): string {
    if (error instanceof ApiError) {
        galat.value = error.fieldErrors;

        return error.message || bawaan;
    }

    return bawaan;
}

// Nilai untuk input datetime-local (YYYY-MM-DDTHH:mm) dari ISO server.
function keInputWaktu(iso: string | null): string {
    if (!iso) {
        return '';
    }

    const tanggal = new Date(iso);
    const dua = (angka: number): string => String(angka).padStart(2, '0');

    return `${tanggal.getFullYear()}-${dua(tanggal.getMonth() + 1)}-${dua(
        tanggal.getDate(),
    )}T${dua(tanggal.getHours())}:${dua(tanggal.getMinutes())}`;
}

function tampilWaktu(iso: string | null): string {
    if (!iso) {
        return '-';
    }

    return new Date(iso).toLocaleString('id-ID', {
        day: '2-digit',
        month: 'short',
        hour: '2-digit',
        minute: '2-digit',
    });
}

async function muatInformasi(): Promise<void> {
    memuatInformasi.value = true;

    try {
        const hasil = await api.get<{ data: RingkasanInformasi }>(
            alamatInformasi,
        );
        Object.assign(informasi, hasil.data);
    } catch (error) {
        toast.error(pesanGalat(error, 'Gagal memuat informasi publik.'));
    } finally {
        memuatInformasi.value = false;
    }
}

async function tambahRundown(): Promise<void> {
    memprosesRundown.value = true;
    galat.value = {};

    try {
        const hasil = await api.post<{ message: string; data: ButirRundown }>(
            `${alamatEvent}/rundown`,
            {
                judul_aktivitas: formRundown.judul_aktivitas,
                deskripsi: formRundown.deskripsi || null,
                waktu_mulai: formRundown.waktu_mulai,
                waktu_selesai: formRundown.waktu_selesai || null,
            },
        );

        informasi.rundown.push(hasil.data);
        informasi.rundown.sort((a, b) =>
            (a.waktu_mulai ?? '').localeCompare(b.waktu_mulai ?? ''),
        );

        formRundown.judul_aktivitas = '';
        formRundown.deskripsi = '';
        formRundown.waktu_mulai = '';
        formRundown.waktu_selesai = '';

        toast.success(hasil.message);
    } catch (error) {
        toast.error(pesanGalat(error, 'Gagal menambah rundown.'));
    } finally {
        memprosesRundown.value = false;
    }
}

async function hapusRundown(id: number): Promise<void> {
    try {
        const hasil = await api.delete<{ message: string }>(
            `${alamatEvent}/rundown/${id}`,
        );

        informasi.rundown = informasi.rundown.filter((b) => b.id !== id);
        toast.success(hasil.message);
    } catch (error) {
        toast.error(pesanGalat(error, 'Gagal menghapus rundown.'));
    }
}

async function unggahDokumen(): Promise<void> {
    if (!berkasDokumen.value) {
        galat.value = { file: 'Pilih berkas terlebih dahulu.' };

        return;
    }

    memprosesDokumen.value = true;
    galat.value = {};

    try {
        const data = new FormData();
        data.append('nama_dokumen', formDokumen.nama_dokumen);
        data.append('jenis', formDokumen.jenis);
        data.append('file', berkasDokumen.value);

        const hasil = await api.post<{ message: string; data: ButirDokumen }>(
            `${alamatEvent}/dokumen`,
            data,
        );

        informasi.dokumen.push(hasil.data);
        informasi.dokumen.sort((a, b) => a.nama.localeCompare(b.nama));

        formDokumen.nama_dokumen = '';
        formDokumen.jenis = 'dokumen';
        berkasDokumen.value = null;

        toast.success(hasil.message);
    } catch (error) {
        toast.error(pesanGalat(error, 'Gagal mengunggah dokumen.'));
    } finally {
        memprosesDokumen.value = false;
    }
}

async function hapusDokumen(id: number): Promise<void> {
    try {
        const hasil = await api.delete<{ message: string }>(
            `${alamatEvent}/dokumen/${id}`,
        );

        informasi.dokumen = informasi.dokumen.filter((d) => d.id !== id);
        toast.success(hasil.message);
    } catch (error) {
        toast.error(pesanGalat(error, 'Gagal menghapus dokumen.'));
    }
}

async function tambahKontak(): Promise<void> {
    memprosesKontak.value = true;
    galat.value = {};

    try {
        const hasil = await api.post<{ message: string; data: ButirKontak }>(
            `${alamatEvent}/kontak-darurat`,
            {
                nama: formKontak.nama,
                nomor_telepon: formKontak.nomor_telepon,
            },
        );

        informasi.kontak_darurat.push(hasil.data);
        formKontak.nama = '';
        formKontak.nomor_telepon = '';

        toast.success(hasil.message);
    } catch (error) {
        toast.error(pesanGalat(error, 'Gagal menambah kontak darurat.'));
    } finally {
        memprosesKontak.value = false;
    }
}

async function hapusKontak(id: number): Promise<void> {
    try {
        const hasil = await api.delete<{ message: string }>(
            `${alamatEvent}/kontak-darurat/${id}`,
        );

        informasi.kontak_darurat = informasi.kontak_darurat.filter(
            (k) => k.id !== id,
        );
        toast.success(hasil.message);
    } catch (error) {
        toast.error(pesanGalat(error, 'Gagal menghapus kontak darurat.'));
    }
}

function pesanGalatUbah(error: unknown, bawaan: string): string {
    if (error instanceof ApiError) {
        galatUbah.value = error.fieldErrors;

        return error.message || bawaan;
    }

    return bawaan;
}

function batalUbah(): void {
    modeEdit.value = null;
    galatUbah.value = {};
    berkasDokumenUbah.value = null;
}

function pilihBerkasUbah(peristiwa: Event): void {
    const masukan = peristiwa.target as HTMLInputElement;
    berkasDokumenUbah.value = masukan.files?.[0] ?? null;
}

function mulaiUbahRundown(butir: ButirRundown): void {
    galatUbah.value = {};
    formUbahRundown.judul_aktivitas = butir.judul;
    formUbahRundown.deskripsi = butir.deskripsi ?? '';
    formUbahRundown.waktu_mulai = keInputWaktu(butir.waktu_mulai);
    formUbahRundown.waktu_selesai = keInputWaktu(butir.waktu_selesai);
    modeEdit.value = { bagian: 'rundown', id: butir.id };
}

function mulaiUbahDokumen(dok: ButirDokumen): void {
    galatUbah.value = {};
    berkasDokumenUbah.value = null;
    formUbahDokumen.nama_dokumen = dok.nama;
    formUbahDokumen.jenis = dok.jenis;
    modeEdit.value = { bagian: 'dokumen', id: dok.id };
}

function mulaiUbahKontak(kontak: ButirKontak): void {
    galatUbah.value = {};
    formUbahKontak.nama = kontak.nama;
    formUbahKontak.nomor_telepon = kontak.nomor_telepon;
    modeEdit.value = { bagian: 'kontak', id: kontak.id };
}

function mulaiUbahRegulasi(): void {
    galatUbah.value = {};
    formUbahRegulasi.informasi_regulasi = informasi.informasi_regulasi ?? '';
    modeEdit.value = { bagian: 'regulasi' };
}

async function simpanUbahRundown(): Promise<void> {
    if (modeEdit.value?.bagian !== 'rundown') {
        return;
    }

    const id = modeEdit.value.id;
    memprosesUbah.value = true;
    galatUbah.value = {};

    try {
        const hasil = await api.put<{ message: string; data: ButirRundown }>(
            `${alamatEvent}/rundown/${id}`,
            {
                judul_aktivitas: formUbahRundown.judul_aktivitas,
                deskripsi: formUbahRundown.deskripsi || null,
                waktu_mulai: formUbahRundown.waktu_mulai,
                waktu_selesai: formUbahRundown.waktu_selesai || null,
            },
        );

        informasi.rundown = informasi.rundown
            .map((b) => (b.id === id ? hasil.data : b))
            .sort((a, b) =>
                (a.waktu_mulai ?? '').localeCompare(b.waktu_mulai ?? ''),
            );

        toast.success(hasil.message);
        batalUbah();
    } catch (error) {
        toast.error(pesanGalatUbah(error, 'Gagal memperbarui rundown.'));
    } finally {
        memprosesUbah.value = false;
    }
}

async function simpanUbahDokumen(): Promise<void> {
    if (modeEdit.value?.bagian !== 'dokumen') {
        return;
    }

    const id = modeEdit.value.id;
    memprosesUbah.value = true;
    galatUbah.value = {};

    try {
        const data = new FormData();
        // PHP hanya mem-parse multipart pada POST, jadi PUT memakai method
        // spoofing agar berkas pengganti tetap terbaca Laravel.
        data.append('_method', 'PUT');
        data.append('nama_dokumen', formUbahDokumen.nama_dokumen);
        data.append('jenis', formUbahDokumen.jenis);

        if (berkasDokumenUbah.value) {
            data.append('file', berkasDokumenUbah.value);
        }

        const hasil = await api.post<{ message: string; data: ButirDokumen }>(
            `${alamatEvent}/dokumen/${id}`,
            data,
        );

        informasi.dokumen = informasi.dokumen
            .map((d) => (d.id === id ? hasil.data : d))
            .sort((a, b) => a.nama.localeCompare(b.nama));

        toast.success(hasil.message);
        batalUbah();
    } catch (error) {
        toast.error(pesanGalatUbah(error, 'Gagal memperbarui dokumen.'));
    } finally {
        memprosesUbah.value = false;
    }
}

async function simpanUbahKontak(): Promise<void> {
    if (modeEdit.value?.bagian !== 'kontak') {
        return;
    }

    const id = modeEdit.value.id;
    memprosesUbah.value = true;
    galatUbah.value = {};

    try {
        const hasil = await api.put<{ message: string; data: ButirKontak }>(
            `${alamatEvent}/kontak-darurat/${id}`,
            {
                nama: formUbahKontak.nama,
                nomor_telepon: formUbahKontak.nomor_telepon,
            },
        );

        informasi.kontak_darurat = informasi.kontak_darurat.map((k) =>
            k.id === id ? hasil.data : k,
        );

        toast.success(hasil.message);
        batalUbah();
    } catch (error) {
        toast.error(pesanGalatUbah(error, 'Gagal memperbarui kontak darurat.'));
    } finally {
        memprosesUbah.value = false;
    }
}

async function simpanUbahRegulasi(): Promise<void> {
    memprosesUbah.value = true;
    galatUbah.value = {};

    try {
        const hasil = await api.put<{
            message: string;
            data: { informasi_regulasi: string | null };
        }>(`${alamatEvent}/informasi-regulasi`, {
            informasi_regulasi: formUbahRegulasi.informasi_regulasi || null,
        });

        informasi.informasi_regulasi = hasil.data.informasi_regulasi;

        toast.success(hasil.message);
        batalUbah();
    } catch (error) {
        toast.error(pesanGalatUbah(error, 'Gagal memperbarui regulasi.'));
    } finally {
        memprosesUbah.value = false;
    }
}

onMounted(muatInformasi);

function ubahDialog(buka: boolean): void {
    if (memproses.value) {
        return;
    }

    dialogBuka.value = buka;
}

async function tutupEvent(): Promise<void> {
    memproses.value = true;

    try {
        const result = await api.post<{
            message: string;
            data: { tanggal_ditutup: string | null };
        }>(`/api/v1/eo/events/${props.event.id}/tutup`);

        dapatDitutup.value = false;
        siapDiarsipkan.value = true;
        tanggalDitutup.value = result.data.tanggal_ditutup;
        dialogBuka.value = false;

        toast.success(result.message ?? 'Event berhasil ditutup.');
    } catch (err) {
        if (err instanceof ApiError) {
            toast.error(err.message ?? 'Gagal menutup event.');
        } else {
            toast.error('Terjadi kesalahan. Silakan coba lagi.');
        }
    } finally {
        memproses.value = false;
    }
}

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Event', href: '/eo/events' },
            { title: 'Detail Event', href: '#' },
        ],
    },
});

const hariIni = new Date(Date.now() - new Date().getTimezoneOffset() * 60000)
    .toISOString()
    .slice(0, 10);

const belumSelesai = computed(() => props.event.tanggal_selesai > hariIni);
</script>

<template>
    <Head :title="event.nama" />

    <div class="flex flex-col gap-4 p-4 md:p-6">
        <header class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <h1 class="text-2xl font-extrabold" data-test="detail-nama">
                    {{ event.nama }}
                </h1>
                <p class="text-sm text-neutral-500">
                    Terakhir diperbarui {{ event.diperbarui }}
                </p>
            </div>
            <div class="flex flex-wrap gap-2">
                <Link
                    v-if="event.dapat_diubah"
                    :href="`/eo/events/${event.id}/ubah`"
                    class="inline-flex rounded-lg bg-[#17564C] px-4 py-2.5 text-sm font-semibold text-white hover:opacity-90"
                    data-test="ubah-event"
                >
                    Ubah informasi
                </Link>
                <Button
                    v-if="dapatDitutup"
                    type="button"
                    variant="destructive"
                    data-test="tutup-event"
                    @click="dialogBuka = true"
                >
                    Tutup event
                </Button>
            </div>
        </header>

        <div
            v-if="siapDiarsipkan"
            class="rounded-lg border border-emerald-200 bg-emerald-50 p-3 text-sm text-emerald-900"
            data-test="banner-ditutup"
        >
            Event ditutup pada {{ tanggalDitutup }}. Event ini siap diarsipkan
            oleh Admin.
        </div>

        <DashCard judul="Informasi event">
            <dl class="grid gap-4 sm:grid-cols-2">
                <div>
                    <dt class="text-xs text-neutral-500">Periode</dt>
                    <dd class="text-sm font-medium">{{ event.periode }}</dd>
                </div>
                <div>
                    <dt class="text-xs text-neutral-500">Lokasi</dt>
                    <dd class="text-sm font-medium">
                        {{ event.lokasi ?? '-' }}
                    </dd>
                </div>
                <div>
                    <dt class="text-xs text-neutral-500">Kategori</dt>
                    <dd class="text-sm font-medium">
                        {{ event.kategori ?? '-' }}
                    </dd>
                </div>
                <div>
                    <dt class="text-xs text-neutral-500">Status</dt>
                    <dd class="text-sm font-medium" data-test="detail-status">
                        {{ labelStatus[event.status] ?? event.status }}
                    </dd>
                </div>
                <div class="sm:col-span-2">
                    <dt class="text-xs text-neutral-500">Deskripsi</dt>
                    <dd class="text-sm whitespace-pre-line">
                        {{ event.deskripsi || '-' }}
                    </dd>
                </div>
            </dl>
        </DashCard>

        <!-- Informasi publik yang tampil di halaman detail pengunjung -->
        <DashCard judul="Informasi publik event">
            <p class="mb-5 text-xs text-neutral-500">
                Data ini tampil di halaman detail event publik. Hanya bisa
                diubah selama event belum ditutup.
            </p>

            <div
                v-if="memuatInformasi"
                class="py-6 text-center text-sm text-neutral-500"
            >
                Memuat informasi publik...
            </div>

            <div v-else class="flex flex-col gap-8">
                <!-- informasi & regulasi (bisa diubah langsung di sini) -->
                <section
                    class="rounded-lg border bg-neutral-50 p-3"
                    data-test="kelola-regulasi"
                >
                    <div class="flex items-start justify-between gap-3">
                        <h3 class="font-bold">Informasi &amp; regulasi</h3>
                        <button
                            v-if="
                                informasi.dapat_diubah &&
                                modeEdit?.bagian !== 'regulasi'
                            "
                            type="button"
                            class="shrink-0 text-xs font-semibold text-[#17564C] hover:underline"
                            data-test="ubah-regulasi"
                            @click="mulaiUbahRegulasi"
                        >
                            Ubah
                        </button>
                    </div>

                    <template v-if="modeEdit?.bagian === 'regulasi'">
                        <div class="mt-2 space-y-1">
                            <textarea
                                v-model="formUbahRegulasi.informasi_regulasi"
                                rows="4"
                                :class="kelasInput"
                                :aria-invalid="
                                    Boolean(galatUbah.informasi_regulasi)
                                "
                                data-test="input-ubah-regulasi"
                            />
                            <InputError
                                :message="galatUbah.informasi_regulasi"
                            />
                        </div>
                        <div class="mt-2 flex gap-2">
                            <button
                                type="button"
                                :disabled="memprosesUbah"
                                class="rounded-lg bg-[#17564C] px-3 py-1.5 text-xs font-semibold text-white hover:opacity-90 disabled:opacity-50"
                                data-test="simpan-regulasi"
                                @click="simpanUbahRegulasi"
                            >
                                {{ memprosesUbah ? 'Menyimpan...' : 'Simpan' }}
                            </button>
                            <button
                                type="button"
                                class="rounded-lg border px-3 py-1.5 text-xs font-semibold hover:bg-neutral-100"
                                data-test="batal-ubah-regulasi"
                                @click="batalUbah"
                            >
                                Batal
                            </button>
                        </div>
                    </template>

                    <template v-else>
                        <p
                            class="mt-1 text-sm whitespace-pre-line text-neutral-700"
                        >
                            {{ informasi.informasi_regulasi || 'Belum diisi.' }}
                        </p>
                        <Link
                            v-if="informasi.dapat_diubah"
                            :href="`/eo/events/${event.id}/ubah`"
                            class="mt-2 inline-block text-xs font-semibold text-[#17564C] hover:underline"
                        >
                            Ubah data event lainnya
                        </Link>
                    </template>
                </section>

                <!-- rundown -->
                <section data-test="kelola-rundown">
                    <h3 class="font-bold">Rundown acara</h3>
                    <p class="text-xs text-neutral-500">
                        Urutan acara yang dilihat pengunjung, diurutkan otomatis
                        berdasarkan waktu mulai.
                    </p>

                    <ul
                        v-if="informasi.rundown.length > 0"
                        class="mt-3 divide-y rounded-lg border"
                    >
                        <li
                            v-for="butir in informasi.rundown"
                            :key="butir.id"
                            class="p-3"
                        >
                            <template
                                v-if="
                                    modeEdit?.bagian === 'rundown' &&
                                    modeEdit.id === butir.id
                                "
                            >
                                <div class="grid gap-3 sm:grid-cols-2">
                                    <div class="space-y-1 sm:col-span-2">
                                        <label
                                            class="text-xs font-medium"
                                            :for="`ubah-rundown-judul-${butir.id}`"
                                        >
                                            Judul aktivitas
                                        </label>
                                        <input
                                            :id="`ubah-rundown-judul-${butir.id}`"
                                            v-model="
                                                formUbahRundown.judul_aktivitas
                                            "
                                            type="text"
                                            maxlength="255"
                                            :class="kelasInput"
                                            :aria-invalid="
                                                Boolean(
                                                    galatUbah.judul_aktivitas,
                                                )
                                            "
                                        />
                                        <InputError
                                            :message="galatUbah.judul_aktivitas"
                                        />
                                    </div>
                                    <div class="space-y-1">
                                        <label
                                            class="text-xs font-medium"
                                            :for="`ubah-rundown-mulai-${butir.id}`"
                                        >
                                            Waktu mulai
                                        </label>
                                        <input
                                            :id="`ubah-rundown-mulai-${butir.id}`"
                                            v-model="
                                                formUbahRundown.waktu_mulai
                                            "
                                            type="datetime-local"
                                            :class="kelasInput"
                                            :aria-invalid="
                                                Boolean(galatUbah.waktu_mulai)
                                            "
                                        />
                                        <InputError
                                            :message="galatUbah.waktu_mulai"
                                        />
                                    </div>
                                    <div class="space-y-1">
                                        <label
                                            class="text-xs font-medium"
                                            :for="`ubah-rundown-selesai-${butir.id}`"
                                        >
                                            Waktu selesai (opsional)
                                        </label>
                                        <input
                                            :id="`ubah-rundown-selesai-${butir.id}`"
                                            v-model="
                                                formUbahRundown.waktu_selesai
                                            "
                                            type="datetime-local"
                                            :class="kelasInput"
                                            :aria-invalid="
                                                Boolean(galatUbah.waktu_selesai)
                                            "
                                        />
                                        <InputError
                                            :message="galatUbah.waktu_selesai"
                                        />
                                    </div>
                                    <div class="space-y-1 sm:col-span-2">
                                        <label
                                            class="text-xs font-medium"
                                            :for="`ubah-rundown-deskripsi-${butir.id}`"
                                        >
                                            Deskripsi (opsional)
                                        </label>
                                        <textarea
                                            :id="`ubah-rundown-deskripsi-${butir.id}`"
                                            v-model="formUbahRundown.deskripsi"
                                            rows="2"
                                            :class="kelasInput"
                                        />
                                    </div>
                                </div>
                                <div class="mt-3 flex gap-2">
                                    <button
                                        type="button"
                                        :disabled="memprosesUbah"
                                        class="rounded-lg bg-[#17564C] px-3 py-1.5 text-xs font-semibold text-white hover:opacity-90 disabled:opacity-50"
                                        :data-test="`simpan-rundown-${butir.id}`"
                                        @click="simpanUbahRundown"
                                    >
                                        {{
                                            memprosesUbah
                                                ? 'Menyimpan...'
                                                : 'Simpan'
                                        }}
                                    </button>
                                    <button
                                        type="button"
                                        class="rounded-lg border px-3 py-1.5 text-xs font-semibold hover:bg-neutral-100"
                                        @click="batalUbah"
                                    >
                                        Batal
                                    </button>
                                </div>
                            </template>

                            <div
                                v-else
                                class="flex items-start justify-between gap-3"
                            >
                                <div class="min-w-0">
                                    <p class="text-sm font-semibold">
                                        {{ butir.judul }}
                                    </p>
                                    <p class="text-xs text-neutral-500">
                                        {{ tampilWaktu(butir.waktu_mulai) }}
                                        <template v-if="butir.waktu_selesai">
                                            –
                                            {{
                                                tampilWaktu(butir.waktu_selesai)
                                            }}
                                        </template>
                                    </p>
                                    <p
                                        v-if="butir.deskripsi"
                                        class="mt-1 text-xs text-neutral-600"
                                    >
                                        {{ butir.deskripsi }}
                                    </p>
                                </div>
                                <div
                                    v-if="informasi.dapat_diubah"
                                    class="flex shrink-0 items-center gap-3"
                                >
                                    <button
                                        type="button"
                                        class="text-xs font-semibold text-[#17564C] hover:underline"
                                        :data-test="`ubah-rundown-${butir.id}`"
                                        @click="mulaiUbahRundown(butir)"
                                    >
                                        Ubah
                                    </button>
                                    <button
                                        type="button"
                                        class="text-xs font-semibold text-destructive hover:underline"
                                        @click="hapusRundown(butir.id)"
                                    >
                                        Hapus
                                    </button>
                                </div>
                            </div>
                        </li>
                    </ul>
                    <p v-else class="mt-3 text-sm text-neutral-500">
                        Belum ada rundown.
                    </p>

                    <form
                        v-if="informasi.dapat_diubah"
                        class="mt-4 grid gap-3 sm:grid-cols-2"
                        novalidate
                        @submit.prevent="tambahRundown"
                    >
                        <div class="space-y-1 sm:col-span-2">
                            <label
                                class="text-xs font-medium"
                                for="rundown_judul"
                            >
                                Judul aktivitas
                            </label>
                            <input
                                id="rundown_judul"
                                v-model="formRundown.judul_aktivitas"
                                type="text"
                                maxlength="255"
                                :class="kelasInput"
                                :aria-invalid="Boolean(galat.judul_aktivitas)"
                                data-test="rundown-judul"
                            />
                            <InputError :message="galat.judul_aktivitas" />
                        </div>
                        <div class="space-y-1">
                            <label
                                class="text-xs font-medium"
                                for="rundown_mulai"
                            >
                                Waktu mulai
                            </label>
                            <input
                                id="rundown_mulai"
                                v-model="formRundown.waktu_mulai"
                                type="datetime-local"
                                :class="kelasInput"
                                :aria-invalid="Boolean(galat.waktu_mulai)"
                                data-test="rundown-mulai"
                            />
                            <InputError :message="galat.waktu_mulai" />
                        </div>
                        <div class="space-y-1">
                            <label
                                class="text-xs font-medium"
                                for="rundown_selesai"
                            >
                                Waktu selesai (opsional)
                            </label>
                            <input
                                id="rundown_selesai"
                                v-model="formRundown.waktu_selesai"
                                type="datetime-local"
                                :class="kelasInput"
                                :aria-invalid="Boolean(galat.waktu_selesai)"
                            />
                            <InputError :message="galat.waktu_selesai" />
                        </div>
                        <div class="space-y-1 sm:col-span-2">
                            <label
                                class="text-xs font-medium"
                                for="rundown_deskripsi"
                            >
                                Deskripsi (opsional)
                            </label>
                            <textarea
                                id="rundown_deskripsi"
                                v-model="formRundown.deskripsi"
                                rows="2"
                                :class="kelasInput"
                            />
                        </div>
                        <div class="sm:col-span-2">
                            <button
                                type="submit"
                                :disabled="memprosesRundown"
                                class="rounded-lg bg-[#17564C] px-4 py-2 text-sm font-semibold text-white hover:opacity-90 disabled:opacity-50"
                                data-test="tambah-rundown"
                            >
                                {{
                                    memprosesRundown
                                        ? 'Menyimpan...'
                                        : '+ Tambah rundown'
                                }}
                            </button>
                        </div>
                    </form>
                </section>

                <!-- layout & dokumen -->
                <section class="border-t pt-6" data-test="kelola-dokumen">
                    <h3 class="font-bold">Layout &amp; dokumen</h3>
                    <p class="text-xs text-neutral-500">
                        Denah venue dan berkas pendukung yang bisa diunduh
                        pengunjung (PDF/JPG/PNG, maks. 5 MB).
                    </p>

                    <ul
                        v-if="informasi.dokumen.length > 0"
                        class="mt-3 divide-y rounded-lg border"
                    >
                        <li
                            v-for="dok in informasi.dokumen"
                            :key="dok.id"
                            class="p-3"
                        >
                            <template
                                v-if="
                                    modeEdit?.bagian === 'dokumen' &&
                                    modeEdit.id === dok.id
                                "
                            >
                                <div class="grid gap-3 sm:grid-cols-3">
                                    <div class="space-y-1">
                                        <label
                                            class="text-xs font-medium"
                                            :for="`ubah-dokumen-nama-${dok.id}`"
                                        >
                                            Nama dokumen
                                        </label>
                                        <input
                                            :id="`ubah-dokumen-nama-${dok.id}`"
                                            v-model="
                                                formUbahDokumen.nama_dokumen
                                            "
                                            type="text"
                                            maxlength="255"
                                            :class="kelasInput"
                                            :aria-invalid="
                                                Boolean(galatUbah.nama_dokumen)
                                            "
                                        />
                                        <InputError
                                            :message="galatUbah.nama_dokumen"
                                        />
                                    </div>
                                    <div class="space-y-1">
                                        <label
                                            class="text-xs font-medium"
                                            :for="`ubah-dokumen-jenis-${dok.id}`"
                                        >
                                            Jenis
                                        </label>
                                        <select
                                            :id="`ubah-dokumen-jenis-${dok.id}`"
                                            v-model="formUbahDokumen.jenis"
                                            :class="kelasInput"
                                        >
                                            <option value="dokumen">
                                                Dokumen
                                            </option>
                                            <option value="layout">
                                                Layout
                                            </option>
                                        </select>
                                        <InputError
                                            :message="galatUbah.jenis"
                                        />
                                    </div>
                                    <div class="space-y-1">
                                        <label
                                            class="text-xs font-medium"
                                            :for="`ubah-dokumen-file-${dok.id}`"
                                        >
                                            Ganti berkas (opsional)
                                        </label>
                                        <input
                                            :id="`ubah-dokumen-file-${dok.id}`"
                                            type="file"
                                            accept=".pdf,.jpg,.jpeg,.png"
                                            :class="kelasInput"
                                            @change="pilihBerkasUbah"
                                        />
                                        <p class="text-[11px] text-neutral-500">
                                            Kosongkan bila berkas tidak diganti.
                                        </p>
                                        <InputError :message="galatUbah.file" />
                                    </div>
                                </div>
                                <div class="mt-3 flex gap-2">
                                    <button
                                        type="button"
                                        :disabled="memprosesUbah"
                                        class="rounded-lg bg-[#17564C] px-3 py-1.5 text-xs font-semibold text-white hover:opacity-90 disabled:opacity-50"
                                        :data-test="`simpan-dokumen-${dok.id}`"
                                        @click="simpanUbahDokumen"
                                    >
                                        {{
                                            memprosesUbah
                                                ? 'Menyimpan...'
                                                : 'Simpan'
                                        }}
                                    </button>
                                    <button
                                        type="button"
                                        class="rounded-lg border px-3 py-1.5 text-xs font-semibold hover:bg-neutral-100"
                                        @click="batalUbah"
                                    >
                                        Batal
                                    </button>
                                </div>
                            </template>

                            <div
                                v-else
                                class="flex items-center justify-between gap-3"
                            >
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-semibold">
                                        {{ dok.nama }}
                                    </p>
                                    <span
                                        class="mt-1 inline-block rounded-full px-2 py-0.5 text-[11px] font-semibold"
                                        :class="
                                            dok.jenis === 'layout'
                                                ? 'bg-[#17564C]/10 text-[#17564C]'
                                                : 'bg-neutral-100 text-neutral-700'
                                        "
                                    >
                                        {{
                                            dok.jenis === 'layout'
                                                ? 'Layout'
                                                : 'Dokumen'
                                        }}
                                    </span>
                                </div>
                                <div class="flex shrink-0 items-center gap-3">
                                    <a
                                        :href="dok.unduh_url"
                                        class="text-xs font-semibold text-[#17564C] hover:underline"
                                    >
                                        Unduh
                                    </a>
                                    <template v-if="informasi.dapat_diubah">
                                        <button
                                            type="button"
                                            class="text-xs font-semibold text-[#17564C] hover:underline"
                                            :data-test="`ubah-dokumen-${dok.id}`"
                                            @click="mulaiUbahDokumen(dok)"
                                        >
                                            Ubah
                                        </button>
                                        <button
                                            type="button"
                                            class="text-xs font-semibold text-destructive hover:underline"
                                            @click="hapusDokumen(dok.id)"
                                        >
                                            Hapus
                                        </button>
                                    </template>
                                </div>
                            </div>
                        </li>
                    </ul>
                    <p v-else class="mt-3 text-sm text-neutral-500">
                        Belum ada layout atau dokumen.
                    </p>

                    <form
                        v-if="informasi.dapat_diubah"
                        class="mt-4 grid gap-3 sm:grid-cols-3"
                        novalidate
                        @submit.prevent="unggahDokumen"
                    >
                        <div class="space-y-1">
                            <label
                                class="text-xs font-medium"
                                for="dokumen_nama"
                            >
                                Nama dokumen
                            </label>
                            <input
                                id="dokumen_nama"
                                v-model="formDokumen.nama_dokumen"
                                type="text"
                                maxlength="255"
                                :class="kelasInput"
                                :aria-invalid="Boolean(galat.nama_dokumen)"
                            />
                            <InputError :message="galat.nama_dokumen" />
                        </div>
                        <div class="space-y-1">
                            <label
                                class="text-xs font-medium"
                                for="dokumen_jenis"
                            >
                                Jenis
                            </label>
                            <select
                                id="dokumen_jenis"
                                v-model="formDokumen.jenis"
                                :class="kelasInput"
                            >
                                <option value="dokumen">Dokumen</option>
                                <option value="layout">Layout</option>
                            </select>
                            <InputError :message="galat.jenis" />
                        </div>
                        <div class="space-y-1">
                            <label
                                class="text-xs font-medium"
                                for="dokumen_file"
                            >
                                Berkas
                            </label>
                            <input
                                id="dokumen_file"
                                type="file"
                                accept=".pdf,.jpg,.jpeg,.png"
                                :class="kelasInput"
                                @change="pilihBerkas"
                            />
                            <InputError :message="galat.file" />
                        </div>
                        <div class="sm:col-span-3">
                            <button
                                type="submit"
                                :disabled="memprosesDokumen"
                                class="rounded-lg bg-[#17564C] px-4 py-2 text-sm font-semibold text-white hover:opacity-90 disabled:opacity-50"
                                data-test="unggah-dokumen"
                            >
                                {{
                                    memprosesDokumen
                                        ? 'Mengunggah...'
                                        : '+ Unggah dokumen'
                                }}
                            </button>
                        </div>
                    </form>
                </section>

                <!-- kontak darurat -->
                <section class="border-t pt-6" data-test="kelola-kontak">
                    <h3 class="font-bold">Kontak darurat</h3>
                    <p class="text-xs text-neutral-500">
                        {{ informasi.kontak_darurat.length }} dari
                        {{ informasi.batas_kontak_darurat }} kontak terpakai.
                    </p>

                    <ul
                        v-if="informasi.kontak_darurat.length > 0"
                        class="mt-3 divide-y rounded-lg border"
                    >
                        <li
                            v-for="kontak in informasi.kontak_darurat"
                            :key="kontak.id"
                            class="p-3"
                        >
                            <template
                                v-if="
                                    modeEdit?.bagian === 'kontak' &&
                                    modeEdit.id === kontak.id
                                "
                            >
                                <div class="grid gap-3 sm:grid-cols-2">
                                    <div class="space-y-1">
                                        <label
                                            class="text-xs font-medium"
                                            :for="`ubah-kontak-nama-${kontak.id}`"
                                        >
                                            Nama kontak
                                        </label>
                                        <input
                                            :id="`ubah-kontak-nama-${kontak.id}`"
                                            v-model="formUbahKontak.nama"
                                            type="text"
                                            maxlength="100"
                                            :class="kelasInput"
                                            :aria-invalid="
                                                Boolean(galatUbah.nama)
                                            "
                                        />
                                        <InputError :message="galatUbah.nama" />
                                    </div>
                                    <div class="space-y-1">
                                        <label
                                            class="text-xs font-medium"
                                            :for="`ubah-kontak-nomor-${kontak.id}`"
                                        >
                                            Nomor telepon
                                        </label>
                                        <input
                                            :id="`ubah-kontak-nomor-${kontak.id}`"
                                            v-model="
                                                formUbahKontak.nomor_telepon
                                            "
                                            type="tel"
                                            maxlength="20"
                                            :class="kelasInput"
                                            :aria-invalid="
                                                Boolean(galatUbah.nomor_telepon)
                                            "
                                        />
                                        <InputError
                                            :message="galatUbah.nomor_telepon"
                                        />
                                    </div>
                                </div>
                                <div class="mt-3 flex gap-2">
                                    <button
                                        type="button"
                                        :disabled="memprosesUbah"
                                        class="rounded-lg bg-[#17564C] px-3 py-1.5 text-xs font-semibold text-white hover:opacity-90 disabled:opacity-50"
                                        :data-test="`simpan-kontak-${kontak.id}`"
                                        @click="simpanUbahKontak"
                                    >
                                        {{
                                            memprosesUbah
                                                ? 'Menyimpan...'
                                                : 'Simpan'
                                        }}
                                    </button>
                                    <button
                                        type="button"
                                        class="rounded-lg border px-3 py-1.5 text-xs font-semibold hover:bg-neutral-100"
                                        @click="batalUbah"
                                    >
                                        Batal
                                    </button>
                                </div>
                            </template>

                            <div
                                v-else
                                class="flex items-center justify-between gap-3"
                            >
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-semibold">
                                        {{ kontak.nama }}
                                    </p>
                                    <a
                                        :href="`tel:${kontak.nomor_telepon}`"
                                        class="text-xs text-[#17564C] hover:underline"
                                    >
                                        {{ kontak.nomor_telepon }}
                                    </a>
                                </div>
                                <div
                                    v-if="informasi.dapat_diubah"
                                    class="flex shrink-0 items-center gap-3"
                                >
                                    <button
                                        type="button"
                                        class="text-xs font-semibold text-[#17564C] hover:underline"
                                        :data-test="`ubah-kontak-${kontak.id}`"
                                        @click="mulaiUbahKontak(kontak)"
                                    >
                                        Ubah
                                    </button>
                                    <button
                                        type="button"
                                        class="text-xs font-semibold text-destructive hover:underline"
                                        @click="hapusKontak(kontak.id)"
                                    >
                                        Hapus
                                    </button>
                                </div>
                            </div>
                        </li>
                    </ul>
                    <p v-else class="mt-3 text-sm text-neutral-500">
                        Belum ada kontak darurat.
                    </p>

                    <form
                        v-if="informasi.dapat_diubah && !kuotaKontakPenuh"
                        class="mt-4 grid gap-3 sm:grid-cols-2"
                        novalidate
                        @submit.prevent="tambahKontak"
                    >
                        <div class="space-y-1">
                            <label
                                class="text-xs font-medium"
                                for="kontak_nama"
                            >
                                Nama kontak
                            </label>
                            <input
                                id="kontak_nama"
                                v-model="formKontak.nama"
                                type="text"
                                maxlength="100"
                                :class="kelasInput"
                                :aria-invalid="Boolean(galat.nama)"
                                data-test="kontak-nama"
                            />
                            <InputError :message="galat.nama" />
                        </div>
                        <div class="space-y-1">
                            <label
                                class="text-xs font-medium"
                                for="kontak_nomor"
                            >
                                Nomor telepon
                            </label>
                            <input
                                id="kontak_nomor"
                                v-model="formKontak.nomor_telepon"
                                type="tel"
                                maxlength="20"
                                :class="kelasInput"
                                :aria-invalid="Boolean(galat.nomor_telepon)"
                                data-test="kontak-nomor"
                            />
                            <InputError :message="galat.nomor_telepon" />
                        </div>
                        <div class="sm:col-span-2">
                            <button
                                type="submit"
                                :disabled="memprosesKontak"
                                class="rounded-lg bg-[#17564C] px-4 py-2 text-sm font-semibold text-white hover:opacity-90 disabled:opacity-50"
                                data-test="tambah-kontak"
                            >
                                {{
                                    memprosesKontak
                                        ? 'Menyimpan...'
                                        : '+ Tambah kontak'
                                }}
                            </button>
                        </div>
                    </form>

                    <p
                        v-else-if="informasi.dapat_diubah"
                        class="mt-3 rounded-lg bg-amber-50 p-3 text-xs text-amber-900"
                    >
                        Kuota kontak darurat sudah penuh. Hapus salah satu untuk
                        menambah kontak baru.
                    </p>
                </section>
            </div>
        </DashCard>

        <Dialog :open="dialogBuka" @update:open="ubahDialog">
            <DialogContent class="sm:max-w-md">
                <DialogHeader
                    class="items-center text-center sm:items-start sm:text-left"
                >
                    <div
                        class="mb-2 flex size-12 items-center justify-center rounded-full bg-amber-100 text-amber-700"
                    >
                        <TriangleAlert class="size-6" />
                    </div>
                    <DialogTitle>Tutup event ini?</DialogTitle>
                    <DialogDescription>
                        Setelah ditutup, informasi
                        <strong>{{ event.nama }}</strong> tidak dapat diubah
                        lagi dan event tidak tampil sebagai event berlangsung.
                        Event akan menunggu diarsipkan oleh Admin.
                    </DialogDescription>
                </DialogHeader>

                <div
                    v-if="belumSelesai"
                    class="rounded-lg border border-amber-200 bg-amber-50 p-3 text-xs text-amber-900"
                    data-test="peringatan-belum-selesai"
                >
                    Tanggal selesai event ini ({{ event.periode }}) belum
                    terlewati. Tutup hanya jika event memang sudah berakhir.
                </div>

                <DialogFooter class="gap-2">
                    <Button
                        type="button"
                        variant="secondary"
                        :disabled="memproses"
                        @click="ubahDialog(false)"
                    >
                        Batal
                    </Button>
                    <Button
                        type="button"
                        variant="destructive"
                        :disabled="memproses"
                        data-test="konfirmasi-tutup"
                        @click="tutupEvent"
                    >
                        {{ memproses ? 'Menutup...' : 'Ya, tutup event' }}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </div>
</template>
