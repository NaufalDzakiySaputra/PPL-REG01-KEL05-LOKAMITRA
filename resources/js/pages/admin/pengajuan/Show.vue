<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';

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
}

interface PengajuanDetail {
    id: number;
    nama: string;
    email: string;
    nomor_telepon: string | null;
    role: string;
    role_label: string;
    status_akun: string;
    tanggal_daftar: string | null;
    profil: { label: string; value: string }[];
    dokumen: DokumenItem[];
}

const props = defineProps<{
    pengajuan: PengajuanDetail;
    kendala_persetujuan: string[];
    dapat_disetujui: boolean;
}>();

const approveForm = useForm({ verifikasi: '' });
const rejectForm = useForm({ alasan: '' });

const documentForms = props.pengajuan.dokumen.map((document) => ({
    id: document.id,
    form: useForm({
        status_pemeriksaan: '',
        catatan_admin: document.catatan_admin ?? '',
    }),
}));

function setujuiAkun(): void {
    if (window.confirm(`Setujui akun ${props.pengajuan.nama}? Akun akan langsung dapat digunakan untuk login.`)) {
        approveForm.post(`/admin/pengajuan/${props.pengajuan.id}/setujui`, {
            preserveScroll: true,
        });
    }
}

function tolakAkun(): void {
    rejectForm.post(`/admin/pengajuan/${props.pengajuan.id}/tolak`, {
        preserveScroll: true,
    });
}

function kirimPemeriksaan(id: number, form: (typeof documentForms)[number]['form']): void {
    form.post(`/admin/pengajuan/${props.pengajuan.id}/dokumen/${id}/periksa`, {
        preserveScroll: true,
    });
}

function labelStatus(status: string): string {
    return ({ menunggu: 'Menunggu', aktif: 'Aktif', ditolak: 'Ditolak', disetujui: 'Disetujui' } as Record<string, string>)[status] ?? status;
}

function warnaStatus(status: string): string {
    if (status === 'aktif' || status === 'disetujui') return 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300';
    if (status === 'ditolak') return 'bg-red-100 text-red-800 dark:bg-red-950 dark:text-red-300';
    return 'bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300';
}

function tanggal(value: string | null): string {
    if (!value) return '-';
    return new Intl.DateTimeFormat('id-ID', { dateStyle: 'medium', timeStyle: 'short' }).format(new Date(value));
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
    <Head :title="`Detail Pengajuan - ${pengajuan.nama}`" />

    <div class="flex flex-1 flex-col gap-6 p-4 md:p-6">
        <header class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <Link href="/admin/pengajuan" class="text-sm text-primary hover:underline">← Kembali ke daftar</Link>
                <h1 class="mt-2 text-2xl font-semibold">Detail Pengajuan</h1>
                <p class="mt-1 text-sm text-muted-foreground">Periksa identitas, profil, dan dokumen sebelum mengambil keputusan.</p>
            </div>
            <span class="inline-flex w-fit rounded-full px-3 py-1.5 text-sm font-medium" :class="warnaStatus(pengajuan.status_akun)">
                {{ labelStatus(pengajuan.status_akun) }}
            </span>
        </header>

        <section class="grid gap-5 lg:grid-cols-[minmax(0,1fr)_minmax(300px,0.8fr)]">
            <div class="space-y-5">
                <div class="rounded-xl border p-5">
                    <h2 class="font-semibold">Informasi akun</h2>
                    <dl class="mt-4 grid gap-4 sm:grid-cols-2">
                        <div>
                            <dt class="text-xs text-muted-foreground">Nama penanggung jawab</dt>
                            <dd class="mt-1 font-medium">{{ pengajuan.nama }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs text-muted-foreground">Role pendaftar</dt>
                            <dd class="mt-1 font-medium">{{ pengajuan.role_label }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs text-muted-foreground">Email</dt>
                            <dd class="mt-1 break-all font-medium">{{ pengajuan.email }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs text-muted-foreground">Nomor telepon</dt>
                            <dd class="mt-1 font-medium">{{ pengajuan.nomor_telepon || '-' }}</dd>
                        </div>
                        <div class="sm:col-span-2">
                            <dt class="text-xs text-muted-foreground">Tanggal pendaftaran</dt>
                            <dd class="mt-1 font-medium">{{ tanggal(pengajuan.tanggal_daftar) }}</dd>
                        </div>
                    </dl>
                </div>

                <div class="rounded-xl border p-5">
                    <h2 class="font-semibold">Data profil {{ pengajuan.role_label }}</h2>
                    <dl v-if="pengajuan.profil.length" class="mt-4 grid gap-4 sm:grid-cols-2">
                        <div v-for="field in pengajuan.profil" :key="field.label" class="min-w-0">
                            <dt class="text-xs text-muted-foreground">{{ field.label }}</dt>
                            <dd class="mt-1 break-words text-sm font-medium">{{ field.value }}</dd>
                        </div>
                    </dl>
                    <p v-else class="mt-3 text-sm text-muted-foreground">Data profil belum tersedia.</p>
                </div>
            </div>

            <aside class="space-y-5">
                <div class="rounded-xl border p-5">
                    <h2 class="font-semibold">Keputusan verifikasi akun</h2>
                    <p class="mt-1 text-sm text-muted-foreground">Akun hanya dapat disetujui setelah profil lengkap dan semua dokumen yang diunggah sudah diperiksa.</p>

                    <div v-if="kendala_persetujuan.length" class="mt-4 rounded-lg border border-amber-300 bg-amber-50 p-3 text-sm text-amber-900 dark:border-amber-900 dark:bg-amber-950/40 dark:text-amber-200">
                        <p class="font-medium">Belum dapat disetujui</p>
                        <ul class="mt-2 list-inside list-disc space-y-1">
                            <li v-for="kendala in kendala_persetujuan" :key="kendala">{{ kendala }}</li>
                        </ul>
                    </div>
                    <div v-else class="mt-4 rounded-lg border border-emerald-300 bg-emerald-50 p-3 text-sm text-emerald-900 dark:border-emerald-900 dark:bg-emerald-950/40 dark:text-emerald-200">
                        Profil dan dokumen sudah memenuhi syarat persetujuan.
                    </div>

                    <div v-if="pengajuan.status_akun === 'menunggu'" class="mt-4 flex flex-col gap-3">
                        <button type="button" :disabled="!dapat_disetujui || approveForm.processing" class="rounded-lg bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-emerald-700 disabled:cursor-not-allowed disabled:opacity-40" @click="setujuiAkun">
                            {{ approveForm.processing ? 'Memproses...' : 'Setujui akun' }}
                        </button>
                        <p v-if="approveForm.errors.verifikasi" class="text-sm text-red-600">{{ approveForm.errors.verifikasi }}</p>

                        <form @submit.prevent="tolakAkun" class="space-y-2 border-t pt-4">
                            <label for="alasan" class="text-sm font-medium">Alasan penolakan</label>
                            <textarea id="alasan" v-model="rejectForm.alasan" rows="3" maxlength="1000" placeholder="Jelaskan alasan penolakan kepada pendaftar..." class="w-full rounded-lg border bg-background px-3 py-2 text-sm" />
                            <p v-if="rejectForm.errors.alasan" class="text-sm text-red-600">{{ rejectForm.errors.alasan }}</p>
                            <button type="submit" :disabled="rejectForm.processing" class="w-full rounded-lg border border-red-300 px-4 py-2.5 text-sm font-semibold text-red-700 hover:bg-red-50 disabled:opacity-50 dark:border-red-900 dark:text-red-300 dark:hover:bg-red-950">
                                {{ rejectForm.processing ? 'Memproses...' : 'Tolak pengajuan' }}
                            </button>
                        </form>
                    </div>
                    <p v-else class="mt-4 text-sm text-muted-foreground">Pengajuan ini sudah diproses. Keputusan akun tidak dapat diubah melalui halaman ini.</p>
                </div>

                <div class="rounded-xl border p-5">
                    <h2 class="font-semibold">Panduan pemeriksaan</h2>
                    <ol class="mt-3 list-inside list-decimal space-y-2 text-sm text-muted-foreground">
                        <li>Pastikan nama dan kontak pendaftar masuk akal.</li>
                        <li>Periksa kesesuaian data profil dengan role.</li>
                        <li>Lihat dokumen melalui preview atau unduh jika diperlukan.</li>
                        <li>Setujui setiap dokumen sebelum menyetujui akun.</li>
                    </ol>
                </div>
            </aside>
        </section>

        <section class="rounded-xl border">
            <div class="border-b p-5">
                <h2 class="font-semibold">Dokumen persyaratan</h2>
                <p class="mt-1 text-sm text-muted-foreground">File tersimpan secara privat. Admin dapat melihat PDF/gambar di browser atau mengunduhnya.</p>
            </div>

            <div v-if="pengajuan.dokumen.length" class="divide-y">
                <article v-for="document in pengajuan.dokumen" :key="document.id" class="grid gap-4 p-5 lg:grid-cols-[minmax(0,1fr)_minmax(280px,0.8fr)]">
                    <div class="min-w-0">
                        <div class="flex flex-wrap items-center gap-2">
                            <h3 class="font-medium">{{ document.jenis }}</h3>
                            <span class="rounded-full bg-muted px-2 py-0.5 text-[11px] text-muted-foreground">{{ document.sifat }}</span>
                            <span class="rounded-full px-2 py-0.5 text-[11px] font-medium" :class="warnaStatus(document.status)">{{ labelStatus(document.status) }}</span>
                        </div>
                        <p class="mt-2 break-all text-sm">{{ document.nama_file }}</p>
                        <p class="mt-1 text-xs text-muted-foreground">{{ document.format }} · Diunggah {{ tanggal(document.tanggal_upload) }}</p>
                        <p v-if="document.catatan_admin" class="mt-2 rounded-lg bg-muted p-3 text-sm">Catatan terakhir: {{ document.catatan_admin }}</p>
                        <div class="mt-3 flex flex-wrap items-center gap-4">
                            <a :href="document.preview_url" target="_blank" rel="noopener noreferrer" class="inline-flex text-sm font-semibold text-primary hover:underline">
                                Lihat dokumen ↗
                            </a>
                            <a :href="document.download_url" class="inline-flex text-sm font-medium text-muted-foreground hover:text-foreground hover:underline">
                                Unduh dokumen
                            </a>
                        </div>
                    </div>

                    <template v-for="item in documentForms" :key="item.id">
                        <form v-if="pengajuan.status_akun === 'menunggu' && item.id === document.id" @submit.prevent="kirimPemeriksaan(item.id, item.form)" class="space-y-2 rounded-lg bg-muted/40 p-3">
                            <label class="block text-sm font-medium">Keputusan pemeriksaan</label>
                            <select v-model="item.form.status_pemeriksaan" class="w-full rounded-lg border bg-background px-3 py-2 text-sm">
                                <option value="">Pilih keputusan</option>
                                <option value="disetujui">Setujui dokumen</option>
                                <option value="ditolak">Tolak dokumen</option>
                            </select>
                            <label class="block text-sm font-medium">Catatan Admin</label>
                            <textarea v-model="item.form.catatan_admin" rows="2" maxlength="1000" placeholder="Wajib diisi jika dokumen ditolak" class="w-full rounded-lg border bg-background px-3 py-2 text-sm" />
                            <p v-if="item.form.errors.catatan_admin" class="text-sm text-red-600">{{ item.form.errors.catatan_admin }}</p>
                            <p v-if="item.form.errors.status_pemeriksaan" class="text-sm text-red-600">{{ item.form.errors.status_pemeriksaan }}</p>
                            <button type="submit" :disabled="item.form.processing || !item.form.status_pemeriksaan" class="rounded-lg border px-3 py-2 text-xs font-semibold hover:bg-background disabled:opacity-50">
                                {{ item.form.processing ? 'Menyimpan...' : 'Simpan hasil pemeriksaan' }}
                            </button>
                        </form>
                    </template>
                </article>
            </div>

            <div v-else class="p-8 text-center">
                <p class="font-medium">Tidak ada dokumen yang diunggah</p>
                <p class="mt-1 text-sm text-muted-foreground">Role ini tidak mensyaratkan dokumen atau pendaftar belum mengunggah file.</p>
            </div>
        </section>
    </div>
</template>
