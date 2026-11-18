<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { Archive, CalendarCheck, ShieldCheck } from '@lucide/vue';
import { computed, ref } from 'vue';
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

type EventItem = {
    id: number;
    nama: string;
    kategori: string | null;
    lokasi: string | null;
    periode: string;
    eo: string | null;
    status: string;
    diperbarui: string;
    tanggal_ditutup: string | null;
    tanggal_diarsipkan: string | null;
};

type Paginator<T> = {
    data: T[];
    links: { url: string | null; label: string; active: boolean }[];
    total: number;
};

const props = defineProps<{
    events: Paginator<EventItem>;
    filter: { status: string };
    ringkasan: {
        menunggu: number;
        terverifikasi: number;
        ditutup: number;
        diarsip: number;
    };
}>();

const tab = computed(() => [
    {
        kunci: 'menunggu_verifikasi',
        label: 'Menunggu verifikasi',
        jumlah: props.ringkasan.menunggu,
    },
    {
        kunci: 'dipublikasikan',
        label: 'Terverifikasi',
        jumlah: props.ringkasan.terverifikasi,
    },
    {
        kunci: 'ditutup',
        label: 'Siap diarsipkan',
        jumlah: props.ringkasan.ditutup,
    },
    { kunci: 'diarsipkan', label: 'Arsip', jumlah: props.ringkasan.diarsip },
]);

const kosong: Record<string, string> = {
    menunggu_verifikasi: 'Tidak ada event yang menunggu verifikasi',
    dipublikasikan: 'Belum ada event terverifikasi',
    ditutup: 'Tidak ada event yang siap diarsipkan',
    diarsipkan: 'Belum ada event yang diarsipkan',
};

type Aksi = 'verifikasi' | 'arsip';

const teksAksi: Record<
    Aksi,
    {
        judul: string;
        deskripsi: string;
        tombol: string;
        proses: string;
    }
> = {
    verifikasi: {
        judul: 'Verifikasi event ini?',
        deskripsi:
            'Pastikan data event sudah diperiksa. Setelah diverifikasi, event langsung tampil di daftar publik.',
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
const dipilih = ref<EventItem | null>(null);
const aksi = ref<Aksi>('verifikasi');
const memproses = ref(false);

function bukaDialog(a: Aksi, e: EventItem): void {
    aksi.value = a;
    dipilih.value = e;
    dialogBuka.value = true;
}

function ubahDialog(buka: boolean): void {
    if (memproses.value) {
        return;
    }

    dialogBuka.value = buka;
}

async function konfirmasi(): Promise<void> {
    if (!dipilih.value) {
        return;
    }

    if (aksi.value === 'verifikasi') {
        memproses.value = true;
        try {
            const response = await api.post<ApiResponse<unknown>>(
                `/api/v1/admin/events/${dipilih.value.id}/verifikasi`,
            );
            toast.success(response.message ?? 'Event berhasil diverifikasi.');
            dialogBuka.value = false;
            router.reload();
        } catch (error) {
            if (error instanceof ApiError) {
                toast.error(error.message);
            } else {
                toast.error('Gagal memverifikasi event.');
            }
        } finally {
            memproses.value = false;
        }
        return;
    }

    if (aksi.value === 'arsip') {
        memproses.value = true;
        try {
            const response = await api.post<ApiResponse<unknown>>(
                `/api/v1/admin/events/${dipilih.value.id}/arsip`,
            );
            toast.success(response.message ?? 'Event berhasil diarsipkan.');
            dialogBuka.value = false;
            router.reload();
        } catch (error) {
            if (error instanceof ApiError) {
                toast.error(error.message);
            } else {
                toast.error('Gagal mengarsipkan event.');
            }
        } finally {
            memproses.value = false;
        }
        return;
    }
}

function keterangan(e: EventItem): string {
    if (e.status === 'ditutup' && e.tanggal_ditutup) {
        return `Ditutup ${e.tanggal_ditutup}`;
    }

    if (e.status === 'diarsipkan' && e.tanggal_diarsipkan) {
        return `Diarsipkan ${e.tanggal_diarsipkan}`;
    }

    return `Diperbarui ${e.diperbarui}`;
}

function labelHalaman(label: string): string {
    return label.replace('&laquo;', '«').replace('&raquo;', '»');
}

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Daftar Event', href: '/admin/events' }],
    },
});
</script>

<template>
    <Head title="Daftar Event" />

    <div class="flex flex-col gap-4 p-4 md:p-6">
        <header>
            <h1 class="text-2xl font-extrabold">Daftar Event</h1>
            <p class="text-sm text-neutral-600 dark:text-muted-foreground">
                Verifikasi event yang diajukan EO dan arsipkan event yang sudah
                ditutup. Hanya event terverifikasi yang tampil di daftar publik.
            </p>
        </header>

        <div class="flex flex-wrap gap-2" role="tablist">
            <Link
                v-for="t in tab"
                :key="t.kunci"
                :href="`/admin/events?status=${t.kunci}`"
                preserve-scroll
                role="tab"
                :aria-selected="filter.status === t.kunci"
                class="rounded-full border px-4 py-1.5 text-sm font-semibold"
                :class="
                    filter.status === t.kunci
                        ? 'border-[#17564C] bg-[#17564C] text-white'
                        : 'hover:bg-muted'
                "
            >
                {{ t.label }} ({{ t.jumlah }})
            </Link>
        </div>

        <DashCard>
            <div
                v-if="events.data.length === 0"
                class="flex flex-col items-center gap-2 px-4 py-10 text-center"
            >
                <CalendarCheck class="size-8 text-neutral-400" />
                <p class="font-semibold">
                    {{ kosong[filter.status] ?? 'Tidak ada event' }}
                </p>
            </div>

            <ul v-else class="divide-y divide-neutral-200/80">
                <li
                    v-for="e in events.data"
                    :key="e.id"
                    class="flex flex-wrap items-center justify-between gap-3 py-3"
                    data-test="baris-event"
                >
                    <div class="min-w-0">
                        <p class="truncate text-sm font-semibold">
                            {{ e.nama }}
                        </p>
                        <p class="text-xs text-neutral-500">
                            {{
                                [e.eo, e.periode, e.lokasi, e.kategori]
                                    .filter(Boolean)
                                    .join(' · ')
                            }}
                        </p>
                        <p class="text-xs text-neutral-400">
                            {{ keterangan(e) }}
                        </p>
                    </div>

                    <button
                        v-if="e.status === 'menunggu_verifikasi'"
                        type="button"
                        class="shrink-0 rounded-lg bg-[#17564C] px-4 py-2 text-sm font-semibold text-white hover:opacity-90"
                        data-test="verifikasi-event"
                        @click="bukaDialog('verifikasi', e)"
                    >
                        Verifikasi
                    </button>
                    <button
                        v-else-if="e.status === 'ditutup'"
                        type="button"
                        class="shrink-0 rounded-lg bg-[#17564C] px-4 py-2 text-sm font-semibold text-white hover:opacity-90"
                        data-test="arsipkan-event"
                        @click="bukaDialog('arsip', e)"
                    >
                        Arsipkan
                    </button>
                    <span
                        v-else-if="e.status === 'diarsipkan'"
                        class="shrink-0 rounded-full bg-neutral-200 px-3 py-1 text-xs font-semibold text-neutral-700"
                    >
                        Diarsipkan
                    </span>
                    <span
                        v-else
                        class="shrink-0 rounded-full bg-emerald-100 px-3 py-1 text-xs font-semibold text-emerald-800"
                    >
                        Terverifikasi
                    </span>
                </li>
            </ul>

            <div
                v-if="events.links.length > 3"
                class="mt-3 flex flex-wrap justify-end gap-1 border-t pt-3"
            >
                <template v-for="(link, i) in events.links" :key="i">
                    <Link
                        v-if="link.url"
                        :href="link.url"
                        preserve-scroll
                        class="rounded-md border px-3 py-1.5 text-xs"
                        :class="
                            link.active
                                ? 'bg-[#17564C] text-white'
                                : 'hover:bg-muted'
                        "
                    >
                        {{ labelHalaman(link.label) }}
                    </Link>
                    <span
                        v-else
                        class="rounded-md border px-3 py-1.5 text-xs text-muted-foreground opacity-50"
                    >
                        {{ labelHalaman(link.label) }}
                    </span>
                </template>
            </div>
        </DashCard>

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
                    v-if="dipilih"
                    class="space-y-1 rounded-lg border bg-neutral-50 p-3 text-sm dark:bg-muted/40"
                    data-test="ringkasan-aksi"
                >
                    <p class="font-semibold">{{ dipilih.nama }}</p>
                    <p class="text-xs text-neutral-500">
                        {{ dipilih.eo ?? 'EO tidak diketahui' }}
                    </p>
                    <p class="text-xs text-neutral-500">
                        {{
                            [dipilih.periode, dipilih.lokasi, dipilih.kategori]
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
