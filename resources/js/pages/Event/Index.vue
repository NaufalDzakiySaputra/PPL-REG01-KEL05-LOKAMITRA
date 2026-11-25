<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ArrowRight, MapPin, Search } from '@lucide/vue';
import { ref } from 'vue';
import AppPublikLayout from '@/layouts/app/AppPublikLayout.vue';

// Daftar event publik (F-06): hanya event terverifikasi & dipublikasikan, tanpa login.
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
const gambarHeroGagal = ref(false);

// Terapkan filter ke server; parameter kosong tidak dikirim.
function terapkan(perubahan: Partial<typeof props.filter> = {}) {
    const params = { ...props.filter, q: q.value, ...perubahan };
    const bersih = Object.fromEntries(
        Object.entries(params).filter(([, v]) => v !== '' && v !== 'terdekat'),
    );

    router.get('/events', bersih, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
}
</script>

<template>
    <Head title="Event Publik" />

    <AppPublikLayout>
        <!-- hero -->
        <section
            class="relative overflow-hidden bg-[#0D443D] px-6 py-16 text-white"
        >
            <!-- Foto latar dari Unsplash; lisensi Unsplash. Fallback: gradasi hijau di bawahnya. -->
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

            <!-- dekorasi: glow blobs -->
            <div
                class="pointer-events-none absolute -top-24 -right-24 h-96 w-96 rounded-full bg-[#216B60]/30 blur-3xl"
            ></div>
            <div
                class="pointer-events-none absolute -bottom-32 -left-16 h-80 w-80 rounded-full bg-[#F5B52E]/10 blur-3xl"
            ></div>
            <!-- dekorasi: grid dots -->
            <div
                class="pointer-events-none absolute inset-0 opacity-[0.07]"
                style="
                    background-image: radial-gradient(
                        white 1px,
                        transparent 1px
                    );
                    background-size: 24px 24px;
                "
            ></div>

            <div class="relative mx-auto max-w-6xl">
                <span
                    class="inline-block animate-pulse rounded-full bg-white/10 px-3 py-1 text-xs font-semibold tracking-wide text-[#F5B52E] uppercase"
                >
                    ✦ Event Lokamitra
                </span>
                <h1
                    class="mt-4 max-w-xl text-3xl leading-tight font-extrabold md:text-4xl"
                    data-test="event-publik-title"
                >
                    Temukan event yang sedang dan
                    <span class="text-[#F5B52E]">akan berlangsung</span>
                </h1>
                <p class="mt-3 max-w-xl text-sm text-white/80">
                    Semua event di sini sudah diverifikasi admin. Tanpa perlu
                    akun, Anda bisa melihat jadwal, lokasi, dan stakeholder yang
                    terlibat.
                </p>

                <form
                    class="mt-8 flex max-w-xl gap-3"
                    @submit.prevent="terapkan()"
                >
                    <label class="relative flex-1">
                        <Search
                            class="absolute top-1/2 left-4 size-4 -translate-y-1/2 text-neutral-400"
                        />
                        <input
                            v-model="q"
                            type="search"
                            placeholder="Cari nama event"
                            class="h-12 w-full rounded-full bg-white pr-4 pl-11 text-sm text-neutral-900 shadow-lg outline-none placeholder:text-neutral-400 focus:ring-2 focus:ring-[#F5B52E]"
                        />
                    </label>
                    <button
                        type="submit"
                        class="rounded-full bg-[#F5B52E] px-7 text-sm font-bold text-neutral-900 shadow-lg transition-all hover:-translate-y-0.5 hover:bg-[#e5a81e] active:translate-y-0"
                    >
                        Cari
                    </button>
                </form>

                <!-- statistik singkat -->
                <div class="mt-10 flex flex-wrap gap-8">
                    <div>
                        <p class="text-2xl font-extrabold text-[#F5B52E]">
                            {{ events.total }}
                        </p>
                        <p class="text-xs text-white/70">Event terverifikasi</p>
                    </div>
                    <div class="border-l border-white/20 pl-8">
                        <p class="text-2xl font-extrabold text-[#F5B52E]">
                            {{ pilihan.kategori.length }}
                        </p>
                        <p class="text-xs text-white/70">Kategori acara</p>
                    </div>
                    <div class="border-l border-white/20 pl-8">
                        <p class="text-2xl font-extrabold text-[#F5B52E]">
                            {{ pilihan.kota.length }}
                        </p>
                        <p class="text-xs text-white/70">Kota terjangkau</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- strip berjalan -->
        <div
            class="overflow-hidden border-b border-[#E5E2DF] bg-white py-3"
            aria-hidden="true"
        >
            <div class="marquee flex gap-8 whitespace-nowrap">
                <span
                    v-for="n in 2"
                    :key="n"
                    class="flex gap-8 text-xs font-semibold tracking-wide text-[#0D443D]/60 uppercase"
                >
                    <template v-for="i in 6" :key="i">
                        <span class="flex items-center gap-2">
                            <span
                                class="size-1.5 rounded-full bg-[#F5B52E]"
                            ></span>
                            Event terverifikasi admin
                        </span>
                        <span class="flex items-center gap-2">
                            <span
                                class="size-1.5 rounded-full bg-[#216B60]"
                            ></span>
                            Gratis untuk pengunjung
                        </span>
                        <span class="flex items-center gap-2">
                            <span
                                class="size-1.5 rounded-full bg-[#F5B52E]"
                            ></span>
                            {{ pilihan.kota.length }} kota terjangkau
                        </span>
                    </template>
                </span>
            </div>
        </div>

        <section class="mx-auto max-w-6xl px-6 py-10">
            <!-- chip kategori -->
            <div class="flex flex-wrap items-center gap-2">
                <button
                    type="button"
                    :class="[
                        'rounded-full border px-4 py-1.5 text-sm font-semibold transition-colors',
                        filter.kategori === ''
                            ? 'border-[#216B60] bg-[#216B60] text-white'
                            : 'border-[#E5E2DF] bg-white text-neutral-700 hover:border-[#216B60]',
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
                        'rounded-full border px-4 py-1.5 text-sm font-semibold transition-colors',
                        filter.kategori === k
                            ? 'border-[#216B60] bg-[#216B60] text-white'
                            : 'border-[#E5E2DF] bg-white text-neutral-700 hover:border-[#216B60]',
                    ]"
                    @click="terapkan({ kategori: k })"
                >
                    {{ k }}
                </button>
            </div>

            <!-- ringkasan & filter tambahan -->
            <div class="mt-6 flex flex-wrap items-center justify-between gap-3">
                <p class="text-sm text-neutral-600">
                    Menampilkan
                    <strong class="text-neutral-900">{{ events.total }}</strong>
                    event terverifikasi
                </p>
                <div class="flex gap-2">
                    <select
                        :value="filter.kota"
                        class="rounded-full border border-[#E5E2DF] bg-white px-4 py-1.5 text-xs text-neutral-800 outline-none"
                        @change="
                            terapkan({
                                kota: ($event.target as HTMLSelectElement)
                                    .value,
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
                        class="rounded-full border border-[#E5E2DF] bg-white px-4 py-1.5 text-xs text-neutral-800 outline-none"
                        @change="
                            terapkan({
                                urut: ($event.target as HTMLSelectElement)
                                    .value,
                            })
                        "
                    >
                        <option value="terdekat">Terdekat</option>
                        <option value="terbaru">Terbaru</option>
                    </select>
                </div>
            </div>

            <hr class="mt-6 border-[#E5E2DF]" />

            <!-- kosong -->
            <div
                v-if="events.data.length === 0"
                class="py-20 text-center"
                data-test="event-kosong"
            >
                <div
                    class="mx-auto flex size-16 items-center justify-center rounded-full bg-[#0D443D]/5"
                >
                    <Search class="size-7 text-[#216B60]" />
                </div>
                <p class="mt-4 font-semibold text-neutral-700">
                    Belum ada event yang cocok
                </p>
                <p class="mt-1 text-sm text-neutral-500">
                    Coba ubah kata kunci atau filter pencarian Anda.
                </p>
            </div>

            <!-- kartu event -->
            <ul v-else class="mt-8 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                <li
                    v-for="e in events.data"
                    :key="e.id"
                    class="group overflow-hidden rounded-2xl border border-[#E5E2DF] bg-white shadow-sm transition-all duration-300 hover:-translate-y-1 hover:shadow-xl"
                >
                    <div
                        class="relative h-32 overflow-hidden"
                        :style="{ backgroundColor: palet[e.warna] }"
                    >
                        <!-- dekorasi lingkaran transparan -->
                        <div
                            class="absolute -top-6 -right-6 h-24 w-24 rounded-full bg-white/15 transition-transform duration-500 group-hover:scale-125"
                        ></div>
                        <div
                            class="absolute -bottom-8 left-8 h-16 w-16 rounded-full bg-white/10"
                        ></div>
                        <div
                            class="absolute right-4 -bottom-4 flex w-12 flex-col items-center rounded-xl bg-white py-1 leading-tight shadow"
                        >
                            <span class="text-sm font-extrabold">{{
                                e.tanggal.hari
                            }}</span>
                            <span
                                class="text-[10px] font-bold text-neutral-500"
                                >{{ e.tanggal.bulan }}</span
                            >
                        </div>
                    </div>
                    <div class="p-5 pt-6">
                        <Link
                            :href="`/events/${e.id}`"
                            class="block text-neutral-900"
                            data-test="kartu-event"
                        >
                            <span
                                class="inline-block rounded-full bg-[#216B60]/10 px-2.5 py-0.5 text-[11px] font-bold tracking-wide text-[#216B60] uppercase"
                            >
                                {{ e.kategori }}
                            </span>
                            <h3
                                class="mt-1 text-base font-extrabold text-neutral-900 transition-colors group-hover:text-[#216B60]"
                            >
                                {{ e.nama }}
                            </h3>
                            <p
                                class="mt-2 flex items-center gap-1 text-xs text-neutral-500"
                            >
                                <MapPin class="size-3.5" /> {{ e.lokasi }}
                            </p>
                            <p class="mt-1 text-[11px] text-neutral-400">
                                {{ e.stakeholder }} stakeholder terlibat
                            </p>
                            <span
                                class="mt-4 inline-flex items-center gap-1 text-xs font-semibold text-[#155F55] transition-colors hover:text-[#0D443D]"
                            >
                                Lihat detail event
                                <ArrowRight
                                    class="size-3.5 transition-transform group-hover:translate-x-0.5"
                                />
                            </span>
                        </Link>
                    </div>
                </li>
            </ul>

            <!-- halaman -->
            <nav
                v-if="events.last_page > 1"
                class="mt-10 flex items-center justify-center gap-4 text-sm"
            >
                <Link
                    v-if="events.prev_page_url"
                    :href="events.prev_page_url"
                    class="rounded-full border border-[#E5E2DF] bg-white px-5 py-1.5 font-semibold text-neutral-800 transition-colors hover:border-[#216B60]"
                    preserve-scroll
                >
                    &larr; Sebelumnya
                </Link>
                <span class="text-neutral-500">
                    Halaman {{ events.current_page }} dari
                    {{ events.last_page }}
                </span>
                <Link
                    v-if="events.next_page_url"
                    :href="events.next_page_url"
                    class="rounded-full border border-[#E5E2DF] bg-white px-5 py-1.5 font-semibold text-neutral-800 transition-colors hover:border-[#216B60]"
                    preserve-scroll
                >
                    Berikutnya &rarr;
                </Link>
            </nav>
        </section>
    </AppPublikLayout>
</template>

<style scoped>
.marquee {
    animation: marquee 30s linear infinite;
}

@keyframes marquee {
    from {
        transform: translateX(0);
    }
    to {
        transform: translateX(-50%);
    }
}
</style>
