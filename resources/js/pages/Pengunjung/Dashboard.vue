<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ArrowRight, MapPin, Search } from '@lucide/vue';
import { ref } from 'vue';
import DashCard from '@/components/dashboard/DashCard.vue';
import StatCard from '@/components/dashboard/StatCard.vue';

// Dashboard Pengunjung: katalog event terverifikasi (F-06).
// Gaya visual mengikuti Dashboard Admin supaya konsisten lintas peran.
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

const q = ref(props.filter.q);
const gambarHeroGagal = ref(false);

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
    <Head title="Dashboard Pengunjung" />

    <div class="flex flex-col gap-4 p-4 md:p-6">
        <!-- Hero dengan foto latar (F-06). Fallback: gradasi hijau bila gambar gagal dimuat. -->
        <section
            class="relative overflow-hidden rounded-2xl bg-[#0D443D] px-6 py-10 text-white"
            data-test="hero-pengunjung"
        >
            <!-- Foto: Unsplash, lisensi Unsplash. -->
            <img
                v-if="!gambarHeroGagal"
                src="https://images.unsplash.com/photo-1492684223066-81342ee5ff30?auto=format&fit=crop&w=1600&q=80"
                alt="Suasana kerumunan pada sebuah festival"
                class="absolute inset-0 h-full w-full object-cover"
                loading="eager"
                decoding="async"
                @error="gambarHeroGagal = true"
            />
            <div
                class="absolute inset-0 bg-gradient-to-r from-[#0D443D]/95 via-[#0D443D]/80 to-[#0D443D]/50"
            ></div>

            <div class="relative max-w-2xl">
                <span
                    class="inline-block rounded-full bg-white/10 px-3 py-1 text-xs font-semibold tracking-wide text-[#F5B52E] uppercase"
                >
                    ✦ Event Lokamitra
                </span>
                <h1
                    class="mt-3 text-2xl leading-tight font-extrabold md:text-3xl"
                >
                    Temukan event yang menghubungkan Banyumas.
                </h1>
                <p class="mt-2 text-sm text-white/80">
                    Jelajahi acara terverifikasi, lihat jadwal dan lokasi, lalu
                    kenali stakeholder di balik setiap event.
                </p>
                <a
                    href="#daftar-event"
                    class="mt-5 inline-flex items-center gap-1.5 rounded-full bg-[#F5B52E] px-5 py-2 text-sm font-bold text-neutral-900 transition-colors hover:bg-[#e5a81e]"
                >
                    Jelajahi event
                    <ArrowRight class="size-4" />
                </a>
            </div>
        </section>

        <header class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <h1
                    class="text-2xl font-extrabold"
                    data-test="pengunjung-title"
                >
                    Dashboard Pengunjung
                </h1>
                <p class="text-sm text-neutral-600 dark:text-muted-foreground">
                    Temukan event yang sudah diverifikasi admin. Lihat jadwal,
                    lokasi, dan stakeholder yang terlibat.
                </p>
            </div>
        </header>

        <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <StatCard
                aksen="admin"
                label="Event terverifikasi"
                :nilai="props.events.total"
                catatan="Siap untuk dijelajahi"
                nada-catatan="hijau"
            />
            <StatCard
                aksen="admin"
                label="Kategori acara"
                :nilai="props.pilihan.kategori.length"
                catatan="Pilihan katalog"
            />
            <StatCard
                aksen="admin"
                label="Kota terjangkau"
                :nilai="props.pilihan.kota.length"
                catatan="Lokasi event"
            />
            <StatCard
                aksen="admin"
                label="Event di halaman ini"
                :nilai="props.events.data.length"
                :catatan="`Halaman ${props.events.current_page} dari ${props.events.last_page}`"
            />
        </section>

        <DashCard judul="Cari event">
            <template #aksi>
                <span class="text-xs text-neutral-500"
                    >Filter katalog event</span
                >
            </template>

            <form class="flex max-w-xl gap-3" @submit.prevent="terapkan()">
                <label class="relative flex-1">
                    <Search
                        class="absolute top-1/2 left-3 size-4 -translate-y-1/2 text-neutral-400"
                    />
                    <input
                        v-model="q"
                        type="search"
                        placeholder="Cari nama event"
                        class="h-9 w-full rounded-md border border-neutral-200 bg-white pr-3 pl-9 text-sm shadow-sm outline-none placeholder:text-neutral-400 focus:ring-2 focus:ring-[#245C3B]/30"
                    />
                </label>
                <button
                    type="submit"
                    class="rounded-lg bg-[#245C3B] px-4 py-2 text-sm font-semibold text-white hover:bg-[#1c4a2f]"
                >
                    Cari
                </button>
            </form>

            <div class="mt-4 flex flex-wrap items-center gap-2">
                <button
                    type="button"
                    :class="[
                        'rounded-full px-3 py-1 text-xs font-semibold',
                        props.filter.kategori === ''
                            ? 'bg-[#245C3B] text-white'
                            : 'bg-neutral-100 text-neutral-700 hover:bg-neutral-200',
                    ]"
                    @click="terapkan({ kategori: '' })"
                >
                    Semua
                </button>
                <button
                    v-for="k in props.pilihan.kategori"
                    :key="k"
                    type="button"
                    :class="[
                        'rounded-full px-3 py-1 text-xs font-semibold',
                        props.filter.kategori === k
                            ? 'bg-[#245C3B] text-white'
                            : 'bg-neutral-100 text-neutral-700 hover:bg-neutral-200',
                    ]"
                    @click="terapkan({ kategori: k })"
                >
                    {{ k }}
                </button>
            </div>

            <div class="mt-4 flex flex-wrap gap-2">
                <select
                    :value="props.filter.kota"
                    class="rounded-lg border border-neutral-200 bg-white px-3 py-1.5 text-xs text-neutral-800 outline-none"
                    @change="
                        terapkan({
                            kota: ($event.target as HTMLSelectElement).value,
                        })
                    "
                >
                    <option value="">Semua kota</option>
                    <option
                        v-for="kota in props.pilihan.kota"
                        :key="kota"
                        :value="kota"
                    >
                        {{ kota }}
                    </option>
                </select>
                <select
                    :value="props.filter.urut"
                    class="rounded-lg border border-neutral-200 bg-white px-3 py-1.5 text-xs text-neutral-800 outline-none"
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
        </DashCard>

        <DashCard judul="Daftar event">
            <template #aksi>
                <span class="text-xs text-neutral-500">
                    Menampilkan {{ props.events.total }} event terverifikasi
                </span>
            </template>

            <p
                v-if="props.events.data.length === 0"
                class="py-6 text-center text-sm text-neutral-500"
                data-test="event-kosong"
            >
                Belum ada event yang cocok. Coba ubah kata kunci atau filter
                pencarian Anda.
            </p>

            <ul
                v-else
                class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3"
                id="daftar-event"
                data-test="daftar-event"
            >
                <li
                    v-for="e in props.events.data"
                    :key="e.id"
                    class="rounded-2xl border border-neutral-200/80 bg-white p-5"
                >
                    <div class="flex items-start justify-between gap-3">
                        <span
                            class="rounded-full bg-emerald-100 px-3 py-1 text-xs font-semibold text-emerald-800"
                        >
                            {{ e.kategori }}
                        </span>
                        <div
                            class="flex size-12 shrink-0 flex-col items-center justify-center rounded-xl bg-[#FBE9C4] leading-tight text-[#8A5A00]"
                        >
                            <span class="text-base font-extrabold">{{
                                e.tanggal.hari
                            }}</span>
                            <span class="text-[10px] font-semibold">{{
                                e.tanggal.bulan
                            }}</span>
                        </div>
                    </div>
                    <h2 class="mt-3 text-base font-extrabold">
                        {{ e.nama }}
                    </h2>
                    <p
                        class="mt-2 flex items-center gap-1 text-xs text-neutral-500"
                    >
                        <MapPin class="size-3.5" /> {{ e.lokasi }}
                    </p>
                    <p class="mt-1 text-xs text-neutral-500">
                        {{ e.stakeholder }} stakeholder terlibat
                    </p>
                    <Link
                        v-if="e.href"
                        :href="e.href"
                        class="mt-4 inline-flex items-center gap-1 text-xs font-semibold text-[#245C3B] hover:underline"
                    >
                        Lihat detail event
                        <ArrowRight class="size-3.5" />
                    </Link>
                    <span
                        v-else
                        class="mt-4 inline-flex cursor-not-allowed items-center gap-1 text-xs font-semibold text-neutral-400"
                        title="Segera tersedia"
                    >
                        Lihat detail event
                        <ArrowRight class="size-3.5" />
                    </span>
                </li>
            </ul>

            <nav
                v-if="props.events.last_page > 1"
                class="mt-4 flex items-center justify-center gap-4 text-sm"
            >
                <Link
                    v-if="props.events.prev_page_url"
                    :href="props.events.prev_page_url"
                    class="rounded-lg border border-[#245C3B] px-4 py-1.5 text-xs font-semibold text-[#245C3B] hover:bg-[#245C3B]/5"
                    preserve-scroll
                >
                    &larr; Sebelumnya
                </Link>
                <span class="text-xs text-neutral-500">
                    Halaman {{ props.events.current_page }} dari
                    {{ props.events.last_page }}
                </span>
                <Link
                    v-if="props.events.next_page_url"
                    :href="props.events.next_page_url"
                    class="rounded-lg border border-[#245C3B] px-4 py-1.5 text-xs font-semibold text-[#245C3B] hover:bg-[#245C3B]/5"
                    preserve-scroll
                >
                    Berikutnya &rarr;
                </Link>
            </nav>
        </DashCard>
    </div>
</template>
