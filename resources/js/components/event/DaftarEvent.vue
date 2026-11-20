<script setup lang="ts">
import { Link, router } from '@inertiajs/vue3';
import { ArrowRight, CalendarDays, MapPin, Search, Users } from '@lucide/vue';
import { ref } from 'vue';
import DashCard from '@/components/dashboard/DashCard.vue';
import type { FilterEvent, PaginatorKartu, PilihanEvent } from '@/types/event';

// Daftar event publik untuk peran mitra (F-06). Hanya berisi event yang sudah
// dipublikasikan dan belum lewat tanggal selesai. Dipakai bersama oleh
// halaman Pemerintah Daerah dan Vendor/UMKM.
const props = defineProps<{
    events: PaginatorKartu;
    filter: FilterEvent;
    pilihan: PilihanEvent;
    rute: string;
    awalan: string;
}>();

const palet = [
    '#4CC95F',
    '#F5333A',
    '#F58A1F',
    '#3DC1E8',
    '#3CC9B5',
    '#F72554',
];

const q = ref(props.filter.q);

// Terapkan filter ke server; parameter kosong tidak dikirim.
function terapkan(perubahan: Partial<FilterEvent> = {}) {
    const params = { ...props.filter, q: q.value, ...perubahan };
    const bersih = Object.fromEntries(
        Object.entries(params).filter(([, v]) => v !== '' && v !== 'terdekat'),
    );

    router.get(props.rute, bersih, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
}
</script>

<template>
    <div class="flex flex-col gap-4 p-4 md:p-6">
        <header class="flex flex-wrap items-end justify-between gap-3">
            <div>
                <h1 class="text-2xl font-extrabold">Informasi Event</h1>
                <p class="text-sm text-neutral-600 dark:text-muted-foreground">
                    Event yang sudah dipublikasikan Admin. Pilih event untuk
                    melihat detail, regulasi, dan rundown kegiatannya.
                </p>
            </div>
            <p class="text-sm text-neutral-500 dark:text-muted-foreground">
                {{ events.total }} event terpublikasi
            </p>
        </header>

        <DashCard>
            <form
                class="flex flex-wrap items-center gap-2"
                @submit.prevent="terapkan()"
            >
                <label class="relative min-w-52 flex-1">
                    <Search
                        class="absolute top-1/2 left-3 size-4 -translate-y-1/2 text-neutral-400"
                    />
                    <input
                        v-model="q"
                        type="search"
                        placeholder="Cari nama event atau lokasi"
                        class="h-10 w-full rounded-lg border border-neutral-200 bg-white pr-3 pl-9 text-sm outline-none focus:border-[#17564C] dark:border-border dark:bg-card"
                        :data-test="`cari-event-${awalan}`"
                    />
                </label>

                <select
                    :value="filter.kategori"
                    class="h-10 rounded-lg border border-neutral-200 bg-white px-3 text-sm outline-none dark:border-border dark:bg-card"
                    :data-test="`filter-kategori-${awalan}`"
                    @change="
                        terapkan({
                            kategori: ($event.target as HTMLSelectElement)
                                .value,
                        })
                    "
                >
                    <option value="">Semua kategori</option>
                    <option v-for="k in pilihan.kategori" :key="k" :value="k">
                        {{ k }}
                    </option>
                </select>

                <select
                    :value="filter.kota"
                    class="h-10 rounded-lg border border-neutral-200 bg-white px-3 text-sm outline-none dark:border-border dark:bg-card"
                    :data-test="`filter-kota-${awalan}`"
                    @change="
                        terapkan({
                            kota: ($event.target as HTMLSelectElement).value,
                        })
                    "
                >
                    <option value="">Semua kota</option>
                    <option
                        v-for="kota in pilihan.kota"
                        :key="kota"
                        :value="kota"
                    >
                        {{ kota }}
                    </option>
                </select>

                <select
                    :value="filter.urut"
                    class="h-10 rounded-lg border border-neutral-200 bg-white px-3 text-sm outline-none dark:border-border dark:bg-card"
                    :data-test="`urut-event-${awalan}`"
                    @change="
                        terapkan({
                            urut: ($event.target as HTMLSelectElement).value,
                        })
                    "
                >
                    <option value="terdekat">Terdekat</option>
                    <option value="terbaru">Terbaru</option>
                </select>

                <button
                    type="submit"
                    class="h-10 rounded-lg bg-[#17564C] px-4 text-sm font-semibold text-white transition-colors hover:bg-[#12473F]"
                >
                    Cari
                </button>
            </form>
        </DashCard>

        <DashCard>
            <div
                v-if="events.data.length === 0"
                class="flex flex-col items-center gap-2 px-4 py-10 text-center"
                :data-test="`event-${awalan}-kosong`"
            >
                <CalendarDays class="size-8 text-neutral-400" />
                <p class="font-semibold">Belum ada event yang cocok</p>
                <p class="text-sm text-neutral-500 dark:text-muted-foreground">
                    Coba ubah kata kunci atau filter pencarian.
                </p>
            </div>

            <ul
                v-else
                class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3"
                :data-test="`daftar-event-${awalan}`"
            >
                <li
                    v-for="e in events.data"
                    :key="e.id"
                    class="group overflow-hidden rounded-2xl border border-neutral-200/80 bg-white transition-shadow hover:shadow-md dark:border-border dark:bg-card"
                >
                    <div
                        class="relative h-24 overflow-hidden"
                        :style="{ backgroundColor: palet[e.warna] }"
                    >
                        <div
                            class="absolute -top-6 -right-6 size-20 rounded-full bg-white/15"
                        ></div>
                        <div
                            class="absolute right-3 bottom-3 flex w-11 flex-col items-center rounded-lg bg-white py-1 leading-tight shadow"
                        >
                            <span
                                class="text-sm font-extrabold text-neutral-900"
                            >
                                {{ e.tanggal.hari }}
                            </span>
                            <span
                                class="text-[10px] font-bold text-neutral-500"
                            >
                                {{ e.tanggal.bulan }}
                            </span>
                        </div>
                    </div>

                    <div class="p-4">
                        <Link
                            :href="e.href ?? `${rute}/${e.id}`"
                            class="block"
                            :data-test="`tautan-event-${e.id}`"
                        >
                            <span
                                class="inline-block rounded-full bg-[#17564C]/10 px-2.5 py-0.5 text-[11px] font-bold tracking-wide text-[#17564C] uppercase dark:text-emerald-300"
                            >
                                {{ e.kategori ?? 'Event' }}
                            </span>
                            <h2
                                class="mt-1 text-base font-extrabold transition-colors group-hover:text-[#17564C] dark:group-hover:text-emerald-300"
                            >
                                {{ e.nama }}
                            </h2>
                            <p
                                class="mt-2 flex items-center gap-1 text-xs text-neutral-500 dark:text-muted-foreground"
                            >
                                <MapPin class="size-3.5" />
                                {{ e.lokasi ?? 'Lokasi belum diisi' }}
                            </p>
                            <p
                                class="mt-1 flex items-center gap-1 text-xs text-neutral-500 dark:text-muted-foreground"
                            >
                                <Users class="size-3.5" />
                                {{ e.stakeholder }} stakeholder terlibat
                            </p>
                            <span
                                class="mt-3 inline-flex items-center gap-1 text-xs font-semibold text-[#17564C] dark:text-emerald-300"
                            >
                                Lihat detail dan rundown
                                <ArrowRight
                                    class="size-3.5 transition-transform group-hover:translate-x-0.5"
                                />
                            </span>
                        </Link>
                    </div>
                </li>
            </ul>

            <nav
                v-if="events.last_page > 1"
                class="mt-4 flex items-center justify-center gap-4 text-sm"
            >
                <Link
                    v-if="events.prev_page_url"
                    :href="events.prev_page_url"
                    class="rounded-full border border-neutral-200 px-4 py-1.5 font-semibold hover:border-[#17564C] dark:border-border"
                    preserve-scroll
                >
                    &larr; Sebelumnya
                </Link>
                <span class="text-neutral-500 dark:text-muted-foreground">
                    Halaman {{ events.current_page }} dari
                    {{ events.last_page }}
                </span>
                <Link
                    v-if="events.next_page_url"
                    :href="events.next_page_url"
                    class="rounded-full border border-neutral-200 px-4 py-1.5 font-semibold hover:border-[#17564C] dark:border-border"
                    preserve-scroll
                >
                    Berikutnya &rarr;
                </Link>
            </nav>
        </DashCard>
    </div>
</template>
