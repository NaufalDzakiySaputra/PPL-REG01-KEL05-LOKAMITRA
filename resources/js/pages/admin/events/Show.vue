<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import {
    Archive,
    CalendarCheck,
    Download,
    Eye,
    FileText,
    ShieldCheck,
} from '@lucide/vue';
import { ref } from 'vue';
import { toast } from 'vue-sonner';
import DashCard from '@/components/dashboard/DashCard.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { ApiError, api } from '@/lib/api';
import type { ApiResponse } from '@/types/api';

type EventDetail = {
    id: number;
    nama: string;
    kategori: string | null;
    lokasi: string | null;
    deskripsi: string | null;
    informasi_regulasi: string | null;
    periode: string;
    tanggal_mulai: string;
    tanggal_selesai: string;
    status: string;
    status_label: string;
    dibuat: string;
    diperbarui: string;
    diverifikasi: string | null;
    diverifikasi_oleh: string | null;
    ditutup: string | null;
    diarsipkan: string | null;
    dapat_diverifikasi: boolean;
    dapat_diarsipkan: boolean;
};

type EoDetail = {
    nama_organisasi: string | null;
    nama_penanggung_jawab: string | null;
    email: string | null;
    nomor_telepon: string | null;
    status_verifikasi: string | null;
    status_verifikasi_label: string;
};

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
    jenis: string;
    diunggah_pada: string | null;
    pratinjau_url: string;
    unduh_url: string;
};

type ButirKontak = {
    id: number;
    nama: string;
    nomor_telepon: string;
};

const props = defineProps<{
    event: EventDetail;
    eo: EoDetail;
    rundown: ButirRundown[];
    dokumen: ButirDokumen[];
    kontak_darurat: ButirKontak[];
}>();

type Aksi = 'verifikasi' | 'arsip';

const teksAksi: Record<
    Aksi,
    { judul: string; deskripsi: string; tombol: string; proses: string }
> = {
    verifikasi: {
        judul: 'Verifikasi event ini?',
        deskripsi:
            'Pastikan seluruh informasi publik di halaman ini sudah diperiksa. Setelah diverifikasi, event langsung tampil di papan informasi publik.',
        tombol: 'Ya, verifikasi event',
        proses: 'Memverifikasi...',
    },
    arsip: {
        judul: 'Arsipkan event ini?',
        deskripsi:
            'Event dipindahkan ke tab Arsip dan tidak tampil sebagai event aktif. Datanya tetap tersimpan untuk ditinjau kembali.',
        tombol: 'Ya, arsipkan event',
        proses: 'Mengarsipkan...',
    },
};

const dialogBuka = ref(false);
const aksi = ref<Aksi>('verifikasi');
const memproses = ref(false);

function bukaDialog(a: Aksi): void {
    aksi.value = a;
    dialogBuka.value = true;
}

function ubahDialog(buka: boolean): void {
    if (memproses.value) {
        return;
    }

    dialogBuka.value = buka;
}

async function konfirmasi(): Promise<void> {
    memproses.value = true;

    try {
        const jalur =
            aksi.value === 'verifikasi'
                ? `/api/v1/admin/events/${props.event.id}/verifikasi`
                : `/api/v1/admin/events/${props.event.id}/arsip`;

        const response = await api.post<ApiResponse<unknown>>(jalur);

        toast.success(
            response.message ??
                (aksi.value === 'verifikasi'
                    ? 'Event berhasil diverifikasi.'
                    : 'Event berhasil diarsipkan.'),
        );
        dialogBuka.value = false;
        router.reload();
    } catch (error) {
        toast.error(
            error instanceof ApiError
                ? error.message
                : 'Terjadi kesalahan. Silakan coba lagi.',
        );
    } finally {
        memproses.value = false;
    }
}

function warnaStatus(status: string): string {
    if (status === 'dipublikasikan') {
        return 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300';
    }

    if (status === 'menunggu_verifikasi') {
        return 'bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300';
    }

    if (status === 'draft') {
        return 'bg-neutral-200 text-neutral-700 dark:bg-neutral-800 dark:text-neutral-300';
    }

    return 'bg-slate-200 text-slate-700 dark:bg-slate-800 dark:text-slate-300';
}

function labelJenis(jenis: string): string {
    return jenis === 'layout' ? 'Layout' : 'Dokumen';
}

function ringkasan(): string {
    return [
        props.eo.nama_organisasi,
        props.event.periode,
        props.event.lokasi,
        props.event.kategori,
    ]
        .filter(Boolean)
        .join(' · ');
}

function warnaVerifikasiEo(status: string | null): string {
    if (status === 'disetujui') {
        return 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300';
    }

    if (status === 'ditolak') {
        return 'bg-red-100 text-red-800 dark:bg-red-950 dark:text-red-300';
    }

    return 'bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300';
}

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Daftar Event', href: '/admin/events' },
            { title: 'Periksa Event', href: '#' },
        ],
    },
});
</script>

<template>
    <Head :title="`Periksa Event - ${event.nama}`" />

    <div class="flex flex-col gap-4 p-4 md:p-6" data-test="pemeriksaan-event">
        <header
            class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between"
        >
            <div class="min-w-0">
                <Link
                    href="/admin/events"
                    class="text-sm font-semibold text-[#17564C] hover:underline"
                >
                    ← Kembali ke daftar event
                </Link>
                <h1
                    class="mt-2 text-2xl font-extrabold break-words"
                    data-test="periksa-nama"
                >
                    {{ event.nama }}
                </h1>
                <p class="text-sm text-neutral-600 dark:text-muted-foreground">
                    {{ ringkasan() }}
                </p>
                <p class="text-xs text-neutral-500">
                    Terakhir diperbarui {{ event.diperbarui }}
                </p>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <span
                    class="inline-flex shrink-0 rounded-full px-3 py-1.5 text-xs font-semibold"
                    :class="warnaStatus(event.status)"
                    data-test="status-event"
                >
                    {{ event.status_label }}
                </span>
                <Button
                    v-if="event.dapat_diverifikasi"
                    type="button"
                    class="bg-[#17564C] text-white hover:bg-[#17564C]/90"
                    data-test="aksi-verifikasi"
                    @click="bukaDialog('verifikasi')"
                >
                    <ShieldCheck class="size-4" />
                    Verifikasi
                </Button>
                <Button
                    v-if="event.dapat_diarsipkan"
                    type="button"
                    class="bg-[#17564C] text-white hover:bg-[#17564C]/90"
                    data-test="aksi-arsipkan"
                    @click="bukaDialog('arsip')"
                >
                    <Archive class="size-4" />
                    Arsipkan
                </Button>
            </div>
        </header>

        <p
            v-if="event.dapat_diverifikasi"
            class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900 dark:border-amber-900 dark:bg-amber-950/40 dark:text-amber-200"
            data-test="peringatan-periksa"
        >
            Periksa seluruh informasi di bawah ini sebelum menekan
            <strong>Verifikasi</strong>. Event yang disetujui langsung tampil
            pada papan informasi publik.
        </p>

        <section
            class="grid gap-4 lg:grid-cols-[minmax(0,1fr)_minmax(280px,0.65fr)]"
        >
            <div class="space-y-4">
                <DashCard judul="Informasi event">
                    <dl
                        class="grid gap-4 sm:grid-cols-2"
                        data-test="informasi-event"
                    >
                        <div>
                            <dt class="text-xs text-neutral-500">Nama event</dt>
                            <dd class="mt-1 font-semibold break-words">
                                {{ event.nama }}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-xs text-neutral-500">Kategori</dt>
                            <dd class="mt-1 font-medium">
                                {{ event.kategori || '-' }}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-xs text-neutral-500">Periode</dt>
                            <dd class="mt-1 font-medium">
                                {{ event.periode }}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-xs text-neutral-500">Lokasi</dt>
                            <dd class="mt-1 font-medium">
                                {{ event.lokasi || '-' }}
                            </dd>
                        </div>
                        <div class="sm:col-span-2">
                            <dt class="text-xs text-neutral-500">Deskripsi</dt>
                            <dd
                                class="mt-1 text-sm whitespace-pre-line text-neutral-700 dark:text-neutral-300"
                            >
                                {{ event.deskripsi || 'Belum diisi.' }}
                            </dd>
                        </div>
                    </dl>
                </DashCard>

                <DashCard judul="Informasi EO">
                    <dl
                        class="grid gap-4 sm:grid-cols-2"
                        data-test="informasi-eo"
                    >
                        <div>
                            <dt class="text-xs text-neutral-500">
                                Nama organisasi
                            </dt>
                            <dd class="mt-1 font-semibold">
                                {{ eo.nama_organisasi || '-' }}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-xs text-neutral-500">
                                Status verifikasi EO
                            </dt>
                            <dd class="mt-1">
                                <span
                                    class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold"
                                    :class="
                                        warnaVerifikasiEo(eo.status_verifikasi)
                                    "
                                >
                                    {{ eo.status_verifikasi_label }}
                                </span>
                            </dd>
                        </div>
                        <div>
                            <dt class="text-xs text-neutral-500">
                                Penanggung jawab
                            </dt>
                            <dd class="mt-1 font-medium">
                                {{ eo.nama_penanggung_jawab || '-' }}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-xs text-neutral-500">Kontak EO</dt>
                            <dd class="mt-1 font-medium break-all">
                                {{ eo.email || '-' }}
                                <span v-if="eo.nomor_telepon" class="block">
                                    {{ eo.nomor_telepon }}
                                </span>
                            </dd>
                        </div>
                    </dl>
                </DashCard>

                <DashCard judul="Informasi & regulasi">
                    <p
                        v-if="event.informasi_regulasi"
                        class="text-sm whitespace-pre-line text-neutral-700 dark:text-neutral-300"
                        data-test="informasi-regulasi"
                    >
                        {{ event.informasi_regulasi }}
                    </p>
                    <p
                        v-else
                        class="text-sm text-neutral-500"
                        data-test="informasi-regulasi-kosong"
                    >
                        EO belum mengisi informasi &amp; regulasi.
                    </p>
                </DashCard>

                <DashCard judul="Rundown acara">
                    <ol
                        v-if="rundown.length > 0"
                        class="divide-y divide-neutral-200/80"
                        data-test="daftar-rundown"
                    >
                        <li
                            v-for="butir in rundown"
                            :key="butir.id"
                            class="flex flex-wrap items-start justify-between gap-2 py-3 first:pt-0 last:pb-0"
                            data-test="butir-rundown"
                        >
                            <div class="min-w-0">
                                <p class="text-sm font-semibold">
                                    {{ butir.judul }}
                                </p>
                                <p
                                    v-if="butir.deskripsi"
                                    class="mt-1 text-xs text-neutral-600 dark:text-neutral-400"
                                >
                                    {{ butir.deskripsi }}
                                </p>
                            </div>
                            <p
                                class="shrink-0 text-xs text-neutral-500"
                                data-test="waktu-rundown"
                            >
                                {{ butir.waktu_mulai || '-' }}
                                <template v-if="butir.waktu_selesai">
                                    – {{ butir.waktu_selesai }}
                                </template>
                            </p>
                        </li>
                    </ol>
                    <p v-else class="text-sm text-neutral-500">
                        EO belum menambahkan rundown.
                    </p>
                </DashCard>

                <DashCard judul="Layout & dokumen">
                    <ul
                        v-if="dokumen.length > 0"
                        class="divide-y divide-neutral-200/80"
                        data-test="daftar-dokumen"
                    >
                        <li
                            v-for="dok in dokumen"
                            :key="dok.id"
                            class="flex flex-wrap items-center justify-between gap-3 py-3 first:pt-0 last:pb-0"
                            data-test="butir-dokumen"
                        >
                            <div class="min-w-0">
                                <p class="truncate text-sm font-semibold">
                                    {{ dok.nama }}
                                </p>
                                <p class="text-xs text-neutral-500">
                                    <span
                                        class="mr-2 inline-block rounded-full px-2 py-0.5 text-[11px] font-semibold"
                                        :class="
                                            dok.jenis === 'layout'
                                                ? 'bg-[#17564C]/10 text-[#17564C]'
                                                : 'bg-neutral-100 text-neutral-700 dark:bg-neutral-800 dark:text-neutral-300'
                                        "
                                    >
                                        {{ labelJenis(dok.jenis) }}
                                    </span>
                                    Diunggah {{ dok.diunggah_pada || '-' }}
                                </p>
                            </div>
                            <div class="flex shrink-0 items-center gap-3">
                                <a
                                    :href="dok.pratinjau_url"
                                    target="_blank"
                                    rel="noopener"
                                    class="inline-flex items-center gap-1 text-xs font-semibold text-[#17564C] hover:underline"
                                    :data-test="`pratinjau-dokumen-${dok.id}`"
                                >
                                    <Eye class="size-3.5" />
                                    Lihat
                                </a>
                                <a
                                    :href="dok.unduh_url"
                                    class="inline-flex items-center gap-1 text-xs font-semibold text-[#17564C] hover:underline"
                                    :data-test="`unduh-dokumen-${dok.id}`"
                                >
                                    <Download class="size-3.5" />
                                    Unduh
                                </a>
                            </div>
                        </li>
                    </ul>
                    <p v-else class="text-sm text-neutral-500">
                        EO belum mengunggah layout atau dokumen.
                    </p>
                </DashCard>
            </div>

            <div class="space-y-4">
                <DashCard judul="Kontak darurat">
                    <ul
                        v-if="kontak_darurat.length > 0"
                        class="divide-y divide-neutral-200/80"
                        data-test="daftar-kontak"
                    >
                        <li
                            v-for="kontak in kontak_darurat"
                            :key="kontak.id"
                            class="py-2 first:pt-0 last:pb-0"
                            data-test="butir-kontak"
                        >
                            <p class="text-sm font-semibold">
                                {{ kontak.nama }}
                            </p>
                            <a
                                :href="`tel:${kontak.nomor_telepon}`"
                                class="text-xs text-[#17564C] hover:underline"
                            >
                                {{ kontak.nomor_telepon }}
                            </a>
                        </li>
                    </ul>
                    <p v-else class="text-sm text-neutral-500">
                        EO belum menambahkan kontak darurat.
                    </p>
                </DashCard>

                <DashCard judul="Riwayat status">
                    <ul class="space-y-2 text-sm" data-test="riwayat-status">
                        <li class="flex items-start gap-2">
                            <CalendarCheck
                                class="mt-0.5 size-4 shrink-0 text-neutral-400"
                            />
                            <div>
                                <p class="text-xs text-neutral-500">Dibuat</p>
                                <p class="font-medium">{{ event.dibuat }}</p>
                            </div>
                        </li>
                        <li class="flex items-start gap-2">
                            <FileText
                                class="mt-0.5 size-4 shrink-0 text-neutral-400"
                            />
                            <div>
                                <p class="text-xs text-neutral-500">
                                    Terakhir diperbarui
                                </p>
                                <p class="font-medium">
                                    {{ event.diperbarui }}
                                </p>
                            </div>
                        </li>
                        <li
                            v-if="event.diverifikasi"
                            class="flex items-start gap-2"
                            data-test="riwayat-diverifikasi"
                        >
                            <ShieldCheck
                                class="mt-0.5 size-4 shrink-0 text-emerald-500"
                            />
                            <div>
                                <p class="text-xs text-neutral-500">
                                    Diverifikasi
                                </p>
                                <p class="font-medium">
                                    {{ event.diverifikasi }}
                                </p>
                                <p
                                    v-if="event.diverifikasi_oleh"
                                    class="text-xs text-neutral-500"
                                >
                                    oleh {{ event.diverifikasi_oleh }}
                                </p>
                            </div>
                        </li>
                        <li
                            v-if="event.ditutup"
                            class="flex items-start gap-2"
                            data-test="riwayat-ditutup"
                        >
                            <Archive
                                class="mt-0.5 size-4 shrink-0 text-neutral-400"
                            />
                            <div>
                                <p class="text-xs text-neutral-500">Ditutup</p>
                                <p class="font-medium">{{ event.ditutup }}</p>
                            </div>
                        </li>
                        <li
                            v-if="event.diarsipkan"
                            class="flex items-start gap-2"
                            data-test="riwayat-diarsipkan"
                        >
                            <Archive
                                class="mt-0.5 size-4 shrink-0 text-neutral-400"
                            />
                            <div>
                                <p class="text-xs text-neutral-500">
                                    Diarsipkan
                                </p>
                                <p class="font-medium">
                                    {{ event.diarsipkan }}
                                </p>
                            </div>
                        </li>
                    </ul>
                </DashCard>
            </div>
        </section>

        <Dialog :open="dialogBuka" @update:open="ubahDialog">
            <DialogContent class="sm:max-w-md">
                <DialogHeader
                    class="items-center text-center sm:items-start sm:text-left"
                >
                    <div
                        class="mb-2 flex size-12 items-center justify-center rounded-full bg-[#17564C]/10 text-[#17564C]"
                    >
                        <ShieldCheck
                            v-if="aksi === 'verifikasi'"
                            class="size-6"
                        />
                        <Archive v-else class="size-6" />
                    </div>
                    <DialogTitle>{{ teksAksi[aksi].judul }}</DialogTitle>
                    <DialogDescription>
                        {{ teksAksi[aksi].deskripsi }}
                    </DialogDescription>
                </DialogHeader>

                <div
                    class="space-y-1 rounded-lg border bg-neutral-50 p-3 text-sm dark:bg-muted/40"
                    data-test="ringkasan-aksi"
                >
                    <p class="font-semibold">{{ event.nama }}</p>
                    <p class="text-xs text-neutral-500">
                        {{ eo.nama_organisasi ?? 'EO tidak diketahui' }}
                    </p>
                    <p class="text-xs text-neutral-500">
                        {{
                            [event.periode, event.lokasi, event.kategori]
                                .filter(Boolean)
                                .join(' · ')
                        }}
                    </p>
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
                        class="bg-[#17564C] text-white hover:bg-[#17564C]/90"
                        :disabled="memproses"
                        data-test="konfirmasi-aksi"
                        @click="konfirmasi"
                    >
                        {{
                            memproses
                                ? teksAksi[aksi].proses
                                : teksAksi[aksi].tombol
                        }}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </div>
</template>
