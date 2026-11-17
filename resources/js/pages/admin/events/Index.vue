<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { CalendarCheck, ShieldCheck } from '@lucide/vue';
import { ref } from 'vue';
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

type EventItem = {
    id: number;
    nama: string;
    kategori: string | null;
    lokasi: string | null;
    periode: string;
    eo: string | null;
    status: string;
    diperbarui: string;
};

type Paginator<T> = {
    data: T[];
    links: { url: string | null; label: string; active: boolean }[];
    total: number;
};

const props = defineProps<{
    events: Paginator<EventItem>;
    filter: { status: string };
    ringkasan: { menunggu: number; terverifikasi: number };
}>();

const tab = [
    { kunci: 'menunggu_verifikasi', label: 'Menunggu verifikasi' },
    { kunci: 'dipublikasikan', label: 'Terverifikasi' },
];

const dialogBuka = ref(false);
const dipilih = ref<EventItem | null>(null);
const memproses = ref(false);

function verifikasi(e: EventItem): void {
    dipilih.value = e;
    dialogBuka.value = true;
}

function ubahDialog(buka: boolean): void {
    if (memproses.value) {
        return;
    }

    dialogBuka.value = buka;
}

function konfirmasi(): void {
    if (!dipilih.value) {
        return;
    }

    router.post(
        `/admin/events/${dipilih.value.id}/verifikasi`,
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
                Verifikasi event yang diajukan EO. Hanya event terverifikasi
                yang tampil di daftar publik.
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
                {{ t.label }}
                ({{
                    t.kunci === 'menunggu_verifikasi'
                        ? ringkasan.menunggu
                        : ringkasan.terverifikasi
                }})
            </Link>
        </div>

        <DashCard>
            <div
                v-if="events.data.length === 0"
                class="flex flex-col items-center gap-2 px-4 py-10 text-center"
            >
                <CalendarCheck class="size-8 text-neutral-400" />
                <p class="font-semibold">
                    {{
                        filter.status === 'menunggu_verifikasi'
                            ? 'Tidak ada event yang menunggu verifikasi'
                            : 'Belum ada event terverifikasi'
                    }}
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
                        <p class="truncate text-sm font-semibold">{{ e.nama }}</p>
                        <p class="text-xs text-neutral-500">
                            {{
                                [e.eo, e.periode, e.lokasi, e.kategori]
                                    .filter(Boolean)
                                    .join(' · ')
                            }}
                        </p>
                        <p class="text-xs text-neutral-400">
                            Diperbarui {{ e.diperbarui }}
                        </p>
                    </div>

                    <button
                        v-if="e.status === 'menunggu_verifikasi'"
                        type="button"
                        class="shrink-0 rounded-lg bg-[#17564C] px-4 py-2 text-sm font-semibold text-white hover:opacity-90"
                        data-test="verifikasi-event"
                        @click="verifikasi(e)"
                    >
                        Verifikasi
                    </button>
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
                        :class="link.active ? 'bg-[#17564C] text-white' : 'hover:bg-muted'"
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
                <DialogHeader class="items-center text-center sm:items-start sm:text-left">
                    <div
                        class="mb-2 flex size-12 items-center justify-center rounded-full bg-[#17564C]/10 text-[#17564C]"
                    >
                        <ShieldCheck class="size-6" />
                    </div>
                    <DialogTitle>Verifikasi event ini?</DialogTitle>
                    <DialogDescription>
                        Pastikan data event sudah diperiksa. Setelah
                        diverifikasi, event langsung tampil di daftar publik.
                    </DialogDescription>
                </DialogHeader>

                <div
                    v-if="dipilih"
                    class="space-y-1 rounded-lg border bg-neutral-50 p-3 text-sm dark:bg-muted/40"
                    data-test="ringkasan-verifikasi"
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
                        data-test="konfirmasi-verifikasi"
                        @click="konfirmasi"
                    >
                        {{ memproses ? 'Memverifikasi...' : 'Ya, verifikasi event' }}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </div>
</template>