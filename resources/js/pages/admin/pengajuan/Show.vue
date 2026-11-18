<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { onMounted, reactive, ref } from 'vue';
import { toast } from 'vue-sonner';
import DashCard from '@/components/dashboard/DashCard.vue';
import { ApiError, api } from '@/lib/api';

interface DokumenItem {
    id: number;
    jenis: string;
    nama_file: string;
    format: string;
    sifat: string;
    tanggal_upload: string | null;
    status: string;
    catatan_admin: string | null;
    preview_url: string;
    download_url: string;
    file_url: string;
}

interface PengajuanDetail {
    id: number;
    nama: string;
    email: string;
    nomor_telepon: string | null;
    role: string;
    role_label: string;
    status_akun: string;
    status_verifikasi?: string;
    tanggal_daftar: string | null;
    profil: { label: string; value: string }[];
    dokumen: DokumenItem[];
}

interface DetailResponse {
    pengajuan: PengajuanDetail;
    kendala_persetujuan: string[];
    dapat_disetujui: boolean;
}

interface PesanResponse {
    message: string;
}

interface DocumentForm {
    status_pemeriksaan: string;
    catatan_admin: string;
    errors: Record<string, string>;
    processing: boolean;
}

const props = defineProps<DetailResponse>();

const detail = ref<PengajuanDetail>(props.pengajuan);
const kendala = ref<string[]>(props.kendala_persetujuan);
const dapatDisetujui = ref(props.dapat_disetujui);

const approveProcessing = ref(false);
const approveErrors = ref<Record<string, string>>({});

const rejectForm = reactive({ catatan: '' });
const rejectErrors = ref<Record<string, string>>({});
const rejectProcessing = ref(false);

const documentForms = reactive<Record<number, DocumentForm>>({});

for (const dokumen of props.pengajuan.dokumen) {
    documentForms[dokumen.id] = {
        status_pemeriksaan: '',
        catatan_admin: dokumen.catatan_admin ?? '',
        errors: {},
        processing: false,
    };
}

function pesanGagal(error: unknown, fallback: string): void {
    if (error instanceof ApiError) {
        const fieldErrors = error.fieldErrors;

        if (Object.keys(fieldErrors).length > 0) {
            return;
        }

        toast.error(error.message);
        return;
    }

    toast.error(fallback);
}

async function muatDetail(): Promise<void> {
    try {
        const response = await api.get<DetailResponse>(
            `/api/v1/admin/pengajuan/${props.pengajuan.id}`,
        );

        detail.value = response.pengajuan;
        kendala.value = response.kendala_persetujuan;
        dapatDisetujui.value = response.dapat_disetujui;
    } catch (error) {
        pesanGagal(error, 'Tidak dapat memuat detail pengajuan.');
    }
}

async function setujuiAkun(): Promise<void> {
    if (
        !window.confirm(
            `Setujui akun ${detail.value.nama}? Akun akan langsung dapat digunakan untuk login.`,
        )
    ) {
        return;
    }

    approveProcessing.value = true;
    approveErrors.value = {};

    try {
        const response = await api.patch<PesanResponse>(
            `/api/v1/admin/pengajuan/${props.pengajuan.id}`,
            { status: 'verified' },
        );

        toast.success(response.message);
        await muatDetail();
    } catch (error) {
        if (error instanceof ApiError) {
            approveErrors.value = error.fieldErrors;
        }

        pesanGagal(error, 'Pengajuan tidak dapat disetujui.');
    } finally {
        approveProcessing.value = false;
    }
}

async function tolakAkun(): Promise<void> {
    rejectProcessing.value = true;
    rejectErrors.value = {};

    try {
        const response = await api.patch<PesanResponse>(
            `/api/v1/admin/pengajuan/${props.pengajuan.id}`,
            { status: 'rejected', catatan: rejectForm.catatan },
        );

        toast.success(response.message);
        rejectForm.catatan = '';
        await muatDetail();
    } catch (error) {
        if (error instanceof ApiError) {
            rejectErrors.value = error.fieldErrors;
        }

        pesanGagal(error, 'Pengajuan tidak dapat ditolak.');
    } finally {
        rejectProcessing.value = false;
    }
}

async function kirimPemeriksaan(id: number): Promise<void> {
    const form = documentForms[id];

    if (!form) {
        return;
    }

    form.processing = true;
    form.errors = {};

    try {
        const response = await api.post<PesanResponse>(
            `/api/v1/admin/pengajuan/${props.pengajuan.id}/documents/${id}/review`,
            {
                status_pemeriksaan: form.status_pemeriksaan,
                catatan_admin: form.catatan_admin,
            },
        );

        toast.success(response.message);
        await muatDetail();
    } catch (error) {
        if (error instanceof ApiError) {
            form.errors = error.fieldErrors;
        }

        pesanGagal(error, 'Hasil pemeriksaan tidak dapat disimpan.');
    } finally {
        form.processing = false;
    }
}

onMounted(() => {
    void muatDetail();
});

function statusVerifikasi(): string {
    return detail.value.status_verifikasi ?? detail.value.status_akun;
}

function labelStatus(status: string): string {
    return (
        (
            {
                pending: 'Menunggu',
                verified: 'Terverifikasi',
                rejected: 'Ditolak',
                // Nilai database dan status dokumen tetap didukung.
                menunggu: 'Menunggu',
                aktif: 'Aktif',
                ditolak: 'Ditolak',
                disetujui: 'Disetujui',
            } as Record<string, string>
        )[status] ?? status
    );
}

function warnaStatus(status: string): string {
    if (status === 'verified' || status === 'aktif' || status === 'disetujui')
        return 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300';
    if (status === 'rejected' || status === 'ditolak')
        return 'bg-red-100 text-red-800 dark:bg-red-950 dark:text-red-300';
    return 'bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300';
}

function tanggal(value: string | null): string {
    if (!value) return '-';
    return new Intl.DateTimeFormat('id-ID', {
        dateStyle: 'medium',
        timeStyle: 'short',
    }).format(new Date(value));
}

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard Pengelola', href: '/admin/dashboard' },
            { title: 'Pengajuan Pendaftaran', href: '/admin/pengajuan' },
            { title: 'Detail Pengajuan', href: '/admin/pengajuan' },
        ],
    },
});
</script>

<template>
    <Head :title="`Detail Pengajuan - ${detail.nama}`" />

    <div class="flex flex-1 flex-col gap-6 p-4 md:p-6">
        <header
            class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between"
        >
            <div>
                <Link
                    href="/admin/pengajuan"
                    class="text-sm text-primary hover:underline"
                    >← Kembali ke daftar</Link
                >
                <h1 class="mt-2 text-2xl font-semibold">Detail Pengajuan</h1>
                <p class="mt-1 text-sm text-muted-foreground">
                    Periksa identitas, profil, dan dokumen sebelum mengambil
                    keputusan.
                </p>
            </div>
            <span
                class="inline-flex w-fit rounded-full px-3 py-1.5 text-sm font-medium"
                :class="warnaStatus(statusVerifikasi())"
            >
                {{ labelStatus(statusVerifikasi()) }}
            </span>
        </header>

        <section
            class="grid gap-5 lg:grid-cols-[minmax(0,1fr)_minmax(300px,0.8fr)]"
        >
            <div class="space-y-5">
                <DashCard judul="Informasi akun">
                    <dl class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <dt class="text-xs text-muted-foreground">
                                Nama penanggung jawab
                            </dt>
                            <dd class="mt-1 font-medium">
                                {{ detail.nama }}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-xs text-muted-foreground">
                                Role pendaftar
                            </dt>
                            <dd class="mt-1 font-medium">
                                {{ detail.role_label }}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-xs text-muted-foreground">Email</dt>
                            <dd class="mt-1 font-medium break-all">
                                {{ detail.email }}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-xs text-muted-foreground">
                                Nomor telepon
                            </dt>
                            <dd class="mt-1 font-medium">
                                {{ detail.nomor_telepon || '-' }}
                            </dd>
                        </div>
                        <div class="sm:col-span-2">
                            <dt class="text-xs text-muted-foreground">
                                Tanggal pendaftaran
                            </dt>
                            <dd class="mt-1 font-medium">
                                {{ tanggal(detail.tanggal_daftar) }}
                            </dd>
                        </div>
                    </dl>
                </DashCard>

                <DashCard :judul="`Data profil ${detail.role_label}`">
                    <dl
                        v-if="detail.profil.length"
                        class="grid gap-4 sm:grid-cols-2"
                    >
                        <div
                            v-for="field in detail.profil"
                            :key="field.label"
                            class="min-w-0"
                        >
                            <dt class="text-xs text-muted-foreground">
                                {{ field.label }}
                            </dt>
                            <dd class="mt-1 text-sm font-medium break-words">
                                {{ field.value }}
                            </dd>
                        </div>
                    </dl>
                    <p v-else class="text-sm text-muted-foreground">
                        Data profil belum tersedia.
                    </p>
                </DashCard>
            </div>

            <aside class="space-y-5">
                <DashCard judul="Keputusan verifikasi akun">
                    <p class="text-sm text-muted-foreground">
                        Akun hanya dapat disetujui setelah profil lengkap dan
                        semua dokumen yang diunggah sudah diperiksa.
                    </p>

                    <div
                        v-if="kendala.length"
                        class="mt-4 rounded-lg border border-amber-300 bg-amber-50 p-3 text-sm text-amber-900 dark:border-amber-900 dark:bg-amber-950/40 dark:text-amber-200"
                    >
                        <p class="font-medium">Belum dapat disetujui</p>
                        <ul class="mt-2 list-inside list-disc space-y-1">
                            <li v-for="item in kendala" :key="item">
                                {{ item }}
                            </li>
                        </ul>
                    </div>
                    <div
                        v-else
                        class="mt-4 rounded-lg border border-emerald-300 bg-emerald-50 p-3 text-sm text-emerald-900 dark:border-emerald-900 dark:bg-emerald-950/40 dark:text-emerald-200"
                    >
                        Profil dan dokumen sudah memenuhi syarat persetujuan.
                    </div>

                    <div
                        v-if="
                            statusVerifikasi() === 'pending' ||
                            statusVerifikasi() === 'menunggu'
                        "
                        class="mt-4 flex flex-col gap-3"
                    >
                        <button
                            type="button"
                            :disabled="!dapatDisetujui || approveProcessing"
                            class="rounded-lg bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-emerald-700 disabled:cursor-not-allowed disabled:opacity-40"
                            @click="setujuiAkun"
                        >
                            {{
                                approveProcessing
                                    ? 'Memproses...'
                                    : 'Setujui akun'
                            }}
                        </button>
                        <p
                            v-if="approveErrors.verifikasi"
                            class="text-sm text-red-600"
                        >
                            {{ approveErrors.verifikasi }}
                        </p>

                        <form
                            @submit.prevent="tolakAkun"
                            class="space-y-2 border-t pt-4"
                        >
                            <label for="catatan" class="text-sm font-medium"
                                >Catatan penolakan</label
                            >
                            <textarea
                                id="catatan"
                                v-model="rejectForm.catatan"
                                rows="3"
                                maxlength="1000"
                                placeholder="Jelaskan alasan penolakan kepada pendaftar..."
                                class="w-full rounded-lg border bg-background px-3 py-2 text-sm"
                            />
                            <p
                                v-if="rejectErrors.catatan"
                                class="text-sm text-red-600"
                            >
                                {{ rejectErrors.catatan }}
                            </p>
                            <button
                                type="submit"
                                :disabled="rejectProcessing"
                                class="w-full rounded-lg border border-red-300 px-4 py-2.5 text-sm font-semibold text-red-700 hover:bg-red-50 disabled:opacity-50 dark:border-red-900 dark:text-red-300 dark:hover:bg-red-950"
                            >
                                {{
                                    rejectProcessing
                                        ? 'Memproses...'
                                        : 'Tolak pengajuan'
                                }}
                            </button>
                        </form>
                    </div>
                    <p v-else class="mt-4 text-sm text-muted-foreground">
                        Pengajuan ini sudah diproses. Keputusan akun tidak dapat
                        diubah melalui halaman ini.
                    </p>
                </DashCard>

                <DashCard judul="Panduan pemeriksaan">
                    <ol
                        class="list-inside list-decimal space-y-2 text-sm text-muted-foreground"
                    >
                        <li>Pastikan nama dan kontak pendaftar masuk akal.</li>
                        <li>Periksa kesesuaian data profil dengan role.</li>
                        <li>
                            Lihat dokumen melalui preview atau unduh jika
                            diperlukan.
                        </li>
                        <li>Setujui setiap dokumen sebelum menyetujui akun.</li>
                    </ol>
                </DashCard>
            </aside>
        </section>

        <DashCard judul="Dokumen persyaratan">
            <p class="-mt-1 text-sm text-muted-foreground">
                File tersimpan secara privat. Admin dapat melihat PDF/gambar di
                browser atau mengunduhnya.
            </p>

            <div v-if="detail.dokumen.length" class="mt-4 divide-y border-t">
                <article
                    v-for="dokumen in detail.dokumen"
                    :key="dokumen.id"
                    class="grid gap-4 py-5 lg:grid-cols-[minmax(0,1fr)_minmax(280px,0.8fr)]"
                >
                    <div class="min-w-0">
                        <div class="flex flex-wrap items-center gap-2">
                            <h3 class="font-medium">{{ dokumen.jenis }}</h3>
                            <span
                                class="rounded-full bg-muted px-2 py-0.5 text-[11px] text-muted-foreground"
                                >{{ dokumen.sifat }}</span
                            >
                            <span
                                class="rounded-full px-2 py-0.5 text-[11px] font-medium"
                                :class="warnaStatus(dokumen.status)"
                                >{{ labelStatus(dokumen.status) }}</span
                            >
                        </div>
                        <p class="mt-2 text-sm break-all">
                            {{ dokumen.nama_file }}
                        </p>
                        <p class="mt-1 text-xs text-muted-foreground">
                            {{ dokumen.format }} · Diunggah
                            {{ tanggal(dokumen.tanggal_upload) }}
                        </p>
                        <p
                            v-if="dokumen.catatan_admin"
                            class="mt-2 rounded-lg bg-muted p-3 text-sm"
                        >
                            Catatan terakhir: {{ dokumen.catatan_admin }}
                        </p>
                        <div class="mt-3 flex flex-wrap items-center gap-4">
                            <a
                                :href="dokumen.preview_url"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="inline-flex text-sm font-semibold text-primary hover:underline"
                            >
                                Lihat dokumen ↗
                            </a>
                            <a
                                :href="dokumen.file_url"
                                class="inline-flex text-sm font-medium text-muted-foreground hover:text-foreground hover:underline"
                            >
                                Unduh dokumen
                            </a>
                        </div>
                    </div>

                    <form
                        v-if="
                            (statusVerifikasi() === 'pending' ||
                                statusVerifikasi() === 'menunggu') &&
                            documentForms[dokumen.id]
                        "
                        @submit.prevent="kirimPemeriksaan(dokumen.id)"
                        class="space-y-2 rounded-lg bg-muted/40 p-3"
                    >
                        <label class="block text-sm font-medium"
                            >Keputusan pemeriksaan</label
                        >
                        <select
                            v-model="
                                documentForms[dokumen.id].status_pemeriksaan
                            "
                            class="w-full rounded-lg border bg-background px-3 py-2 text-sm"
                        >
                            <option value="">Pilih keputusan</option>
                            <option value="disetujui">Setujui dokumen</option>
                            <option value="ditolak">Tolak dokumen</option>
                        </select>
                        <label class="block text-sm font-medium"
                            >Catatan Admin</label
                        >
                        <textarea
                            v-model="documentForms[dokumen.id].catatan_admin"
                            rows="2"
                            maxlength="1000"
                            placeholder="Wajib diisi jika dokumen ditolak"
                            class="w-full rounded-lg border bg-background px-3 py-2 text-sm"
                        />
                        <p
                            v-if="
                                documentForms[dokumen.id].errors.catatan_admin
                            "
                            class="text-sm text-red-600"
                        >
                            {{ documentForms[dokumen.id].errors.catatan_admin }}
                        </p>
                        <p
                            v-if="
                                documentForms[dokumen.id].errors
                                    .status_pemeriksaan
                            "
                            class="text-sm text-red-600"
                        >
                            {{
                                documentForms[dokumen.id].errors
                                    .status_pemeriksaan
                            }}
                        </p>
                        <button
                            type="submit"
                            :disabled="
                                documentForms[dokumen.id].processing ||
                                !documentForms[dokumen.id].status_pemeriksaan
                            "
                            class="rounded-lg border px-3 py-2 text-xs font-semibold hover:bg-background disabled:opacity-50"
                        >
                            {{
                                documentForms[dokumen.id].processing
                                    ? 'Menyimpan...'
                                    : 'Simpan hasil pemeriksaan'
                            }}
                        </button>
                    </form>
                </article>
            </div>

            <div v-else class="mt-4 border-t p-8 text-center">
                <p class="font-medium">Tidak ada dokumen yang diunggah</p>
                <p class="mt-1 text-sm text-muted-foreground">
                    Role ini tidak mensyaratkan dokumen atau pendaftar belum
                    mengunggah file.
                </p>
            </div>
        </DashCard>
    </div>
</template>
