<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { MessageSquare, Plus } from '@lucide/vue';
import { computed, ref, watch } from 'vue';
import InputError from '@/components/InputError.vue';
import DashCard from '@/components/dashboard/DashCard.vue';
import StatCard from '@/components/dashboard/StatCard.vue';
import Button from '@/components/ui/button/Button.vue';
import Input from '@/components/ui/input/Input.vue';
import Label from '@/components/ui/label/Label.vue';
import { ApiError, api } from '@/lib/api';
import type { DaftarTopikRespons, EventForum, TopikForum } from '@/types/forum';

// Daftar topik forum per event (F-07). Komponen ini dipakai bersama oleh
// seluruh peran mitra; hanya EO yang mendapat formulir pembuatan topik.
// Gaya visual mengikuti Dashboard Admin supaya konsisten lintas peran.
const props = defineProps<{
    events: EventForum[];
    awalan: string;
    bolehBuatTopik: boolean;
}>();

const selectedEventId = ref<number | null>(props.events[0]?.id ?? null);
const daftarTopik = ref<TopikForum[]>([]);
const memuat = ref(false);
const pesanKesalahan = ref('');

const judul = ref('');
const deskripsi = ref('');
const menyimpan = ref(false);
const kesalahanBidang = ref<Record<string, string>>({});

const totalBalasan = computed(() =>
    daftarTopik.value.reduce(
        (jumlah, topik) => jumlah + (topik.jumlah_pesan ?? 0),
        0,
    ),
);

function formatTanggal(nilai: string): string {
    return new Intl.DateTimeFormat('id-ID', {
        dateStyle: 'medium',
        timeStyle: 'short',
    }).format(new Date(nilai));
}

async function muatTopik(): Promise<void> {
    if (selectedEventId.value === null) {
        daftarTopik.value = [];

        return;
    }

    memuat.value = true;
    pesanKesalahan.value = '';

    try {
        const respons = await api.get<DaftarTopikRespons>(
            `/api/v1/events/${selectedEventId.value}/forum`,
        );

        daftarTopik.value = respons.data;
    } catch (error) {
        pesanKesalahan.value =
            error instanceof ApiError
                ? error.message
                : 'Topik forum gagal dimuat.';
    } finally {
        memuat.value = false;
    }
}

watch(selectedEventId, () => void muatTopik(), { immediate: true });

async function buatTopik(): Promise<void> {
    if (selectedEventId.value === null || menyimpan.value) {
        return;
    }

    menyimpan.value = true;
    kesalahanBidang.value = {};
    pesanKesalahan.value = '';

    try {
        await api.post(`/api/v1/events/${selectedEventId.value}/forum`, {
            judul: judul.value,
            deskripsi: deskripsi.value,
        });

        judul.value = '';
        deskripsi.value = '';
        await muatTopik();
    } catch (error) {
        if (error instanceof ApiError) {
            kesalahanBidang.value = error.fieldErrors;
            pesanKesalahan.value = error.message;
        } else {
            pesanKesalahan.value = 'Topik forum gagal disimpan.';
        }
    } finally {
        menyimpan.value = false;
    }
}
</script>

<template>
    <div class="flex flex-col gap-4 p-4 md:p-6" data-test="forum-index">
        <header class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <h1 class="text-2xl font-extrabold">Forum Diskusi</h1>
                <p class="text-sm text-neutral-600 dark:text-muted-foreground">
                    Ruang koordinasi peserta event. Pilih event untuk melihat
                    atau memulai topik diskusi.
                </p>
            </div>
        </header>

        <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <StatCard
                aksen="admin"
                label="Event diikuti"
                :nilai="props.events.length"
                catatan="Forum yang bisa dibuka"
            />
            <StatCard
                aksen="admin"
                label="Topik dibahas"
                :nilai="daftarTopik.length"
                catatan="Pada event yang dipilih"
            />
            <StatCard
                aksen="admin"
                label="Total balasan"
                :nilai="totalBalasan"
                catatan="Seluruh topik pada event ini"
                nada-catatan="hijau"
            />
        </section>

        <DashCard v-if="props.events.length > 0" judul="Pilih event">
            <template #aksi>
                <span class="text-xs text-neutral-500">
                    Pilih event untuk memuat topik
                </span>
            </template>
            <div class="flex flex-col gap-1.5">
                <Label for="forum-event">Event</Label>
                <select
                    id="forum-event"
                    v-model.number="selectedEventId"
                    class="flex h-9 w-full rounded-md border border-neutral-200 bg-white px-3 py-1 text-sm shadow-sm"
                    data-test="pilih-event-forum"
                >
                    <option
                        v-for="event in props.events"
                        :key="event.id"
                        :value="event.id"
                    >
                        {{ event.nama }}
                    </option>
                </select>
            </div>
        </DashCard>

        <DashCard v-else judul="Pilih event">
            <p
                class="py-6 text-center text-sm text-neutral-500"
                data-test="forum-kosong"
            >
                Anda belum menjadi peserta forum pada event mana pun.
            </p>
        </DashCard>

        <template v-if="selectedEventId !== null">
            <section class="grid gap-4 lg:grid-cols-[3fr_2fr]">
                <DashCard judul="Daftar topik">
                    <template #aksi>
                        <span class="text-xs text-neutral-500">
                            {{ daftarTopik.length }} topik
                        </span>
                    </template>

                    <p
                        v-if="pesanKesalahan"
                        class="mb-3 text-sm text-red-600 dark:text-red-500"
                        data-test="kesalahan-forum"
                    >
                        {{ pesanKesalahan }}
                    </p>

                    <p
                        v-if="memuat"
                        class="py-6 text-center text-sm text-neutral-500"
                    >
                        Memuat topik...
                    </p>

                    <ul
                        v-else-if="daftarTopik.length > 0"
                        class="divide-y divide-neutral-200/80"
                        data-test="daftar-topik"
                    >
                        <li
                            v-for="topik in daftarTopik"
                            :key="topik.id"
                            class="py-3"
                        >
                            <Link
                                :href="`/${props.awalan}/forum/${topik.event_id}?topik=${topik.id}`"
                                class="group flex items-start gap-3"
                                data-test="butir-topik"
                            >
                                <span
                                    class="mt-0.5 rounded-lg bg-[#245C3B]/10 p-2 text-[#245C3B] dark:text-emerald-400"
                                >
                                    <MessageSquare class="size-4" />
                                </span>
                                <span class="min-w-0">
                                    <span
                                        class="block font-bold group-hover:underline"
                                    >
                                        {{ topik.judul }}
                                    </span>
                                    <span
                                        v-if="topik.deskripsi"
                                        class="mt-0.5 line-clamp-2 block text-sm text-neutral-600 dark:text-muted-foreground"
                                    >
                                        {{ topik.deskripsi }}
                                    </span>
                                    <span
                                        class="mt-1 block text-xs text-neutral-500 dark:text-muted-foreground"
                                    >
                                        {{ topik.pembuat?.name ?? 'EO' }}
                                        &middot;
                                        {{
                                            formatTanggal(topik.tanggal_dibuat)
                                        }}
                                        &middot;
                                        {{ topik.jumlah_pesan ?? 0 }} balasan
                                    </span>
                                </span>
                            </Link>
                        </li>
                    </ul>

                    <p
                        v-else
                        class="py-6 text-center text-sm text-neutral-500"
                        data-test="topik-belum-ada"
                    >
                        Belum ada topik diskusi pada event ini.
                    </p>
                </DashCard>

                <div class="flex flex-col gap-4">
                    <DashCard
                        v-if="props.bolehBuatTopik"
                        judul="Buat topik baru"
                        data-test="form-topik-forum"
                    >
                        <form
                            class="flex flex-col gap-3"
                            @submit.prevent="buatTopik"
                        >
                            <div class="flex flex-col gap-1.5">
                                <Label for="forum-judul">Judul topik</Label>
                                <Input
                                    id="forum-judul"
                                    v-model="judul"
                                    placeholder="Contoh: Koordinasi teknis panggung"
                                    maxlength="150"
                                    data-test="judul-topik"
                                />
                                <InputError :message="kesalahanBidang.judul" />
                            </div>

                            <div class="flex flex-col gap-1.5">
                                <Label for="forum-deskripsi">Deskripsi</Label>
                                <textarea
                                    id="forum-deskripsi"
                                    v-model="deskripsi"
                                    rows="3"
                                    maxlength="2000"
                                    placeholder="Jelaskan maksud topik diskusi ini."
                                    class="flex w-full rounded-md border border-neutral-200 bg-white px-3 py-2 text-sm shadow-sm"
                                    data-test="deskripsi-topik"
                                />
                                <InputError
                                    :message="kesalahanBidang.deskripsi"
                                />
                            </div>

                            <Button
                                type="submit"
                                class="w-fit bg-[#245C3B] text-white hover:bg-[#1c4a2f]"
                                :disabled="menyimpan"
                                data-test="simpan-topik"
                            >
                                <Plus class="size-4" />
                                {{ menyimpan ? 'Menyimpan...' : 'Buat topik' }}
                            </Button>
                        </form>
                    </DashCard>

                    <DashCard v-else judul="Tentang forum">
                        <p
                            class="text-sm text-neutral-600 dark:text-muted-foreground"
                        >
                            Forum ini dipakai untuk koordinasi antarpeserta
                            event. Anda dapat membaca topik dan memberi
                            tanggapan pada event yang Anda ikuti.
                        </p>
                    </DashCard>
                </div>
            </section>
        </template>
    </div>
</template>
