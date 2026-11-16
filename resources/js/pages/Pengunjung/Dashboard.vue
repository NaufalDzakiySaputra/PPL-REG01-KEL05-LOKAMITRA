<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ArrowRight, MapPin, Search } from '@lucide/vue';
import { ref } from 'vue';

// Beranda Pengunjung (Figma "Tampilan Pengunjung"): katalog event terverifikasi.
type KartuEvent = {
    id: number;
    nama: string;
    kategori: string | null;
    lokasi: string | null;
    stakeholder: number;
    warna: number;
    tanggal: { hari: string; bulan: string };
    href: string | null;
};

const props = defineProps<{
    events: {
        data: KartuEvent[];
        total: number;
        prev_page_url: string | null;
        next_page_url: string | null;
        current_page: number;
        last_page: number;
    };
    filter: { q: string; kategori: string; kota: string; urut: string };
    pilihan: { kategori: string[]; kota: string[] };
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
function terapkan(perubahan: Partial<typeof props.filter> = {}) {
    const params = { ...props.filter, q: q.value, ...perubahan };
    const bersih = Object.fromEntries(
        Object.entries(params).filter(([, v]) => v !== '' && v !== 'terdekat'),
    );

    router.get('/pengunjung/dashboard', bersih, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
}

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Beranda Pengunjung', href: '/pengunjung/dashboard' },
        ],
    },
});
</script>

<template>
    <Head title="Temukan Event" />

    <!-- hero -->
    <section class="bg-[#267A1E] px-6 py-10 text-white md:px-10">
        <div class="mx-auto max-w-5xl">
            <h1
                class="max-w-xl text-3xl leading-tight font-extrabold md:text-4xl"
                data-test="pengunjung-title"
            >
                Temukan event yang sedang dan akan berlangsung
            </h1>
            <p class="mt-3 max-w-xl text-sm text-white/90">
                Semua event di sini sudah diverifikasi admin. Tanpa perlu akun,
                Anda bisa melihat jadwal, lokasi, dan stakeholder yang terlibat.
            </p>

            <form class="mt-6 flex max-w-xl gap-3" @submit.prevent="terapkan()">
                <label class="relative flex-1">
                    <Search
                        class="absolute top-1/2 left-3 size-4 -translate-y-1/2 text-neutral-400"
                    />
                    <input
                        v-model="q"
                        type="search"
                        placeholder="Cari nama event"
                        class="h-11 w-full rounded-lg bg-white pr-3 pl-10 text-sm text-neutral-900 outline-none placeholder:text-neutral-400"
                    />
                </label>
                <button
                    type="submit"
                    class="rounded-lg bg-[#E3A62B] px-6 text-sm font-bold text-neutral-900 hover:bg-[#d09820]"
                >
                    Cari
                </button>
            </form>
        </div>
    </section>

    <section class="mx-auto max-w-5xl px-6 py-8 md:px-10">
        <!-- chip kategori -->
        <div class="flex flex-wrap gap-2">
            <button
                type="button"
                :class="[
                    'rounded-full border px-4 py-1.5 text-sm font-semibold',
                    filter.kategori === ''
                        ? 'border-[#267A1E] bg-[#267A1E] text-white'
                        : 'border-neutral-300 bg-white text-neutral-800',
                ]"
                @click="terapkan({ kategori: '' })"
            >
                Semua
            </button>
            <button
                v-for="k in pilihan.kategori"
                :key="k"
                type="button"
                :class="[
                    'rounded-full border px-4 py-1.5 text-sm font-semibold',
                    filter.kategori === k
                        ? 'border-[#267A1E] bg-[#267A1E] text-white'
                        : 'border-neutral-300 bg-white text-neutral-800',
                ]"
                @click="terapkan({ kategori: k })"
            >
                {{ k }}
            </button>
        </div>

        <div class="mt-5 flex flex-wrap items-center justify-between gap-3">
            <p class="text-sm">
                Menampilkan
                <strong>{{ events.total }} event</strong> terverifikasi
            </p>
            <div class="flex gap-2">
                <select
                    :value="filter.kota"
                    class="rounded-md border border-neutral-300 bg-white px-3 py-1.5 text-xs text-neutral-800"
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
                    class="rounded-md border border-neutral-300 bg-white px-3 py-1.5 text-xs text-neutral-800"
                    @change="
                        terapkan({
                            urut: ($event.target as HTMLSelectElement).value,
                        })
                    "
                >
                    <option value="terdekat">Terdekat</option>
                    <option value="terbaru">Terbaru</option>
                </select>
            </div>
        </div>

        <!-- kosong -->
        <p
            v-if="events.data.length === 0"
            class="mt-12 text-center text-sm text-neutral-500"
        >
            Belum ada event yang cocok dengan pencarian Anda.
        </p>

        <!-- kartu event -->
        <ul v-else class="mt-5 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
            <li
                v-for="e in events.data"
                :key="e.id"
                class="overflow-hidden rounded-2xl border border-neutral-300 bg-white text-neutral-900 shadow-sm"
            >
                <div
                    class="relative h-28"
                    :style="{ backgroundColor: palet[e.warna] }"
                >
                    <div
                        class="absolute right-3 -bottom-3 flex flex-col items-center rounded-lg bg-white px-2 py-0.5 leading-tight shadow"
                    >
                        <span class="text-sm font-extrabold">{{
                            e.tanggal.hari
                        }}</span>
                        <span class="text-[10px] font-bold">{{
                            e.tanggal.bulan
                        }}</span>
                    </div>
                </div>
                <div class="p-4">
                    <p class="text-[11px] font-bold text-amber-900">
                        {{ e.kategori }}
                    </p>
                    <h3 class="mt-0.5 text-base font-extrabold">
                        {{ e.nama }}
                    </h3>
                    <p
                        class="mt-1.5 flex items-center gap-1 text-xs text-neutral-500"
                    >
                        <MapPin class="size-3.5" /> {{ e.lokasi }}
                    </p>
                    <p class="mt-1 text-[11px] text-neutral-400">
                        {{ e.stakeholder }} Stakeholder terlibat
                    </p>
                    <Link
                        v-if="e.href"
                        :href="e.href"
                        class="mt-3 inline-flex items-center gap-1 text-xs font-semibold text-[#267A1E]"
                    >
                        Lihat detail <ArrowRight class="size-3.5" />
                    </Link>
                    <span
                        v-else
                        class="mt-3 inline-flex items-center gap-1 text-xs font-semibold text-neutral-400"
                        title="Segera tersedia"
                    >
                        Lihat detail <ArrowRight class="size-3.5" />
                    </span>
                </div>
            </li>
        </ul>

        <!-- halaman -->
        <nav
            v-if="events.last_page > 1"
            class="mt-8 flex items-center justify-center gap-3 text-sm"
        >
            <Link
                v-if="events.prev_page_url"
                :href="events.prev_page_url"
                class="rounded-md border border-neutral-300 bg-white px-4 py-1.5 text-neutral-800"
                preserve-scroll
            >
                &larr; Sebelumnya
            </Link>
            <span class="text-neutral-500">
                Halaman {{ events.current_page }} dari {{ events.last_page }}
            </span>
            <Link
                v-if="events.next_page_url"
                :href="events.next_page_url"
                class="rounded-md border border-neutral-300 bg-white px-4 py-1.5 text-neutral-800"
                preserve-scroll
            >
                Berikutnya &rarr;
            </Link>
        </nav>
    </section>
</template>
