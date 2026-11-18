<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { TriangleAlert } from '@lucide/vue';
import { computed, ref } from 'vue';
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

function ubahDialog(buka: boolean): void {
    if (memproses.value) {
        return;
    }

    dialogBuka.value = buka;
}

function tutupEvent(): void {
    router.post(
        `/eo/events/${props.event.id}/tutup`,
        {},
        {
            preserveScroll: true,
            onStart: () => (memproses.value = true),
            onFinish: () => {
                memproses.value = false;
                dialogBuka.value = false;
            },
        },
    );
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

// Peringatan tambahan di modal kalau event belum melewati tanggal selesainya.
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
                    v-if="event.dapat_ditutup"
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
            v-if="event.siap_diarsipkan"
            class="rounded-lg border border-emerald-200 bg-emerald-50 p-3 text-sm text-emerald-900"
            data-test="banner-ditutup"
        >
            Event ditutup pada {{ event.tanggal_ditutup }}. Event ini siap
            diarsipkan oleh Admin.
        </div>
        

        <DashCard judul="Informasi event">
            <dl class="grid gap-4 sm:grid-cols-2">
                <div>
                    <dt class="text-xs text-neutral-500">Periode</dt>
                    <dd class="text-sm font-medium">{{ event.periode }}</dd>
                </div>
                <div>
                    <dt class="text-xs text-neutral-500">Lokasi</dt>
                    <dd class="text-sm font-medium">{{ event.lokasi ?? '-' }}</dd>
                </div>
                <div>
                    <dt class="text-xs text-neutral-500">Kategori</dt>
                    <dd class="text-sm font-medium">{{ event.kategori ?? '-' }}</dd>
                </div>
                <div>
                    <dt class="text-xs text-neutral-500">Status</dt>
                    <dd class="text-sm font-medium" data-test="detail-status">
                        {{ labelStatus[event.status] ?? event.status }}
                    </dd>
                </div>
                <div class="sm:col-span-2">
                    <dt class="text-xs text-neutral-500">Deskripsi</dt>
                    <dd class="whitespace-pre-line text-sm">
                        {{ event.deskripsi || '-' }}
                    </dd>
                </div>
            </dl>
        </DashCard>

        <Dialog :open="dialogBuka" @update:open="ubahDialog">
            <DialogContent class="sm:max-w-md">
                <DialogHeader class="items-center text-center sm:items-start sm:text-left">
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
                    Tanggal selesai event ini ({{ event.periode }}) belum terlewati. Tutup
                    hanya jika event memang sudah berakhir.
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