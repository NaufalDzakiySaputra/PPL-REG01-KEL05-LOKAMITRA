<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ArrowLeft, MessageSquare } from '@lucide/vue';
import { ref, watch } from 'vue';
import InputError from '@/components/InputError.vue';
import DashCard from '@/components/dashboard/DashCard.vue';
import StatCard from '@/components/dashboard/StatCard.vue';
import Button from '@/components/ui/button/Button.vue';
import Label from '@/components/ui/label/Label.vue';
import { ApiError, api } from '@/lib/api';
import type {
    DaftarTopikRespons,
    DetailUtasRespons,
    PesanForum,
    TopikForum,
    UtasForum,
} from '@/types/forum';

// Detail utas dan formulir tanggapan (PBI-044, 046, 047, 048).
// Dipakai bersama oleh seluruh peran peserta forum.
// Gaya visual mengikuti Dashboard Admin supaya konsisten lintas peran.
const props = defineProps<{
    event: { id: number; nama: string };
    awalan: string;
    topikAwal: number | null;
}>();

const daftarTopik = ref<TopikForum[]>([]);
const topikTerpilih = ref<number | null>(props.topikAwal);
const utas = ref<UtasForum | null>(null);
const memuat = ref(false);
const pesanKesalahan = ref('');

const isiPesan = ref('');
const mengirim = ref(false);
const kesalahanBidang = ref<Record<string, string>>({});

function formatWaktu(nilai: string): string {
    return new Intl.DateTimeFormat('id-ID', {
        dateStyle: 'medium',
        timeStyle: 'short',
    }).format(new Date(nilai));
}

async function muatDaftarTopik(): Promise<void> {
    try {
        const respons = await api.get<DaftarTopikRespons>(
            `/api/v1/events/${props.event.id}/forum`,
        );

        daftarTopik.value = respons.data;

        if (topikTerpilih.value === null && respons.data.length > 0) {
            topikTerpilih.value = respons.data[0].id;
        }
    } catch (error) {
        pesanKesalahan.value =
            error instanceof ApiError
                ? error.message
                : 'Daftar topik gagal dimuat.';
    }
}

async function muatUtas(): Promise<void> {
    if (topikTerpilih.value === null) {
        utas.value = null;

        return;
    }

    memuat.value = true;
    pesanKesalahan.value = '';

    try {
        const respons = await api.get<DetailUtasRespons>(
            `/api/v1/forum/${topikTerpilih.value}`,
        );

        utas.value = respons.data;
    } catch (error) {
        pesanKesalahan.value =
            error instanceof ApiError
                ? error.message
                : 'Utas diskusi gagal dimuat.';
    } finally {
        memuat.value = false;
    }
}

watch(topikTerpilih, () => void muatUtas(), { immediate: true });
void muatDaftarTopik();

async function kirimPesan(): Promise<void> {
    if (topikTerpilih.value === null || mengirim.value) {
        return;
    }

    mengirim.value = true;
    kesalahanBidang.value = {};
    pesanKesalahan.value = '';

    try {
        const respons = await api.post<{ data: PesanForum }>(
            `/api/v1/forum/${topikTerpilih.value}/pesan`,
            { isi_pesan: isiPesan.value },
        );

        if (utas.value) {
            utas.value.pesan.push(respons.data);
        }

        isiPesan.value = '';
    } catch (error) {
        if (error instanceof ApiError) {
            kesalahanBidang.value = error.fieldErrors;
            pesanKesalahan.value = error.message;
        } else {
            pesanKesalahan.value = 'Tanggapan gagal dikirim.';
        }
    } finally {
        mengirim.value = false;
    }
}
</script>

<template>
    <div class="flex flex-col gap-4 p-4 md:p-6" data-test="forum-show">
        <header class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <Link
                    :href="`/${props.awalan}/forum`"
                    class="inline-flex w-fit items-center gap-1.5 text-xs font-semibold text-[#245C3B] hover:underline dark:text-emerald-400"
                    data-test="kembali-forum"
                >
                    <ArrowLeft class="size-4" /> Kembali ke forum
                </Link>
                <h1 class="mt-2 text-2xl font-extrabold">
                    {{ props.event.nama }}
                </h1>
                <p
                    class="mt-1 text-sm text-neutral-600 dark:text-muted-foreground"
                >
                    Forum Diskusi &middot; pilih topik untuk membaca dan
                    membalas.
                </p>
            </div>
        </header>

        <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <StatCard
                aksen="admin"
                label="Topik pada event"
                :nilai="daftarTopik.length"
                catatan="Topik diskusi tersedia"
            />
            <StatCard
                aksen="admin"
                label="Tanggapan"
                :nilai="utas?.pesan.length ?? 0"
                catatan="Pada topik yang dipilih"
                nada-catatan="hijau"
            />
            <StatCard
                aksen="admin"
                label="Status utas"
                :nilai="utas ? 'Terbuka' : '—'"
                catatan="Forum diskusi peserta"
            />
        </section>

        <p
            v-if="pesanKesalahan"
            class="rounded-xl border border-red-200 bg-red-50 p-3 text-sm text-red-700 dark:border-red-900 dark:bg-red-950/40 dark:text-red-300"
            data-test="kesalahan-forum"
        >
            {{ pesanKesalahan }}
        </p>

        <template v-if="utas">
            <section class="grid gap-4 lg:grid-cols-[3fr_2fr]">
                <div class="flex flex-col gap-4">
                    <DashCard :judul="utas.topik.judul">
                        <template #aksi>
                            <span class="text-xs text-neutral-500">
                                Topik diskusi
                            </span>
                        </template>
                        <p
                            v-if="utas.topik.deskripsi"
                            class="text-sm whitespace-pre-line text-neutral-600 dark:text-muted-foreground"
                        >
                            {{ utas.topik.deskripsi }}
                        </p>
                        <p
                            class="mt-2 text-xs text-neutral-500 dark:text-muted-foreground"
                        >
                            Dibuat oleh {{ utas.topik.pembuat?.name ?? 'EO' }}
                            &middot;
                            {{ formatWaktu(utas.topik.tanggal_dibuat) }}
                        </p>
                    </DashCard>

                    <DashCard judul="Tanggapan peserta">
                        <template #aksi>
                            <span class="text-xs text-neutral-500">
                                {{ utas.pesan.length }} tanggapan
                            </span>
                        </template>
                        <p
                            v-if="memuat"
                            class="py-6 text-center text-sm text-neutral-500"
                        >
                            Memuat tanggapan...
                        </p>

                        <ul
                            v-else-if="utas.pesan.length > 0"
                            class="divide-y divide-neutral-200/80"
                            data-test="daftar-pesan"
                        >
                            <li
                                v-for="pesan in utas.pesan"
                                :key="pesan.id"
                                class="py-3"
                                data-test="butir-pesan"
                            >
                                <div
                                    class="flex flex-wrap items-center justify-between gap-2"
                                >
                                    <div class="flex items-center gap-2">
                                        <p class="text-sm font-bold">
                                            {{
                                                pesan.pengirim?.name ??
                                                'Peserta'
                                            }}
                                        </p>
                                        <span
                                            class="rounded-full bg-emerald-100 px-3 py-1 text-xs font-semibold text-emerald-800"
                                        >
                                            {{ pesan.pengirim?.role }}
                                        </span>
                                    </div>
                                    <p
                                        class="text-xs text-neutral-500 dark:text-muted-foreground"
                                    >
                                        {{ formatWaktu(pesan.waktu_kirim) }}
                                    </p>
                                </div>
                                <p
                                    class="mt-2 text-sm whitespace-pre-line text-neutral-700 dark:text-muted-foreground"
                                >
                                    {{ pesan.isi_pesan }}
                                </p>
                            </li>
                        </ul>

                        <p
                            v-else
                            class="py-6 text-center text-sm text-neutral-500"
                            data-test="pesan-belum-ada"
                        >
                            Belum ada tanggapan. Jadilah yang pertama membalas.
                        </p>
                    </DashCard>
                </div>

                <div class="flex flex-col gap-4">
                    <DashCard v-if="daftarTopik.length > 0" judul="Pilih topik">
                        <div class="flex flex-col gap-1.5">
                            <Label for="forum-pilih-topik">Topik</Label>
                            <select
                                id="forum-pilih-topik"
                                v-model.number="topikTerpilih"
                                class="flex h-9 w-full rounded-md border border-neutral-200 bg-white px-3 py-1 text-sm shadow-sm"
                                data-test="pilih-topik"
                            >
                                <option
                                    v-for="topik in daftarTopik"
                                    :key="topik.id"
                                    :value="topik.id"
                                >
                                    {{ topik.judul }}
                                </option>
                            </select>
                        </div>
                    </DashCard>

                    <DashCard judul="Tulis tanggapan">
                        <form
                            class="flex flex-col gap-3"
                            @submit.prevent="kirimPesan"
                        >
                            <div class="flex flex-col gap-1.5">
                                <Label for="forum-isi-pesan">Pesan</Label>
                                <textarea
                                    id="forum-isi-pesan"
                                    v-model="isiPesan"
                                    rows="4"
                                    maxlength="2000"
                                    placeholder="Tulis tanggapan Anda untuk topik ini."
                                    class="flex w-full rounded-md border border-neutral-200 bg-white px-3 py-2 text-sm shadow-sm"
                                    data-test="isi-pesan"
                                />
                                <InputError
                                    :message="kesalahanBidang.isi_pesan"
                                />
                            </div>

                            <Button
                                type="submit"
                                class="w-fit bg-[#245C3B] text-white hover:bg-[#1c4a2f]"
                                :disabled="mengirim"
                                data-test="kirim-pesan"
                            >
                                <MessageSquare class="size-4" />
                                {{
                                    mengirim ? 'Mengirim...' : 'Kirim tanggapan'
                                }}
                            </Button>
                        </form>
                    </DashCard>
                </div>
            </section>
        </template>

        <DashCard
            v-else-if="!memuat && daftarTopik.length === 0"
            judul="Topik diskusi"
        >
            <p class="py-6 text-center text-sm text-neutral-500">
                Belum ada topik diskusi pada event ini.
            </p>
        </DashCard>
    </div>
</template>
