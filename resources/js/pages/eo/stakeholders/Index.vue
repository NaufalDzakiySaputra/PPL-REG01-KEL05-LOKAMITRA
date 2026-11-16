
<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import DashCard from '@/components/dashboard/DashCard.vue';

type Stakeholder = {
    id: number;
    type: string;
    kategori: string;
    nama: string;
    nama_akun: string;
    url: string;
};

type Paginator<T> = {
    data: T[];
    links: { url: string | null; label: string; active: boolean }[];
    total: number;
};

type Kategori = {
    value: string;
    label: string;
};

const props = defineProps<{
    stakeholders: Paginator<Stakeholder>;
    filter: {
        kategori: string;
        q: string;
    };
    kategori: Kategori[];
}>();

const kataKunci = ref(props.filter.q);
const kategoriAktif = ref(props.filter.kategori);

function cariStakeholder() {
    router.get(
        '/eo/stakeholders',
        {
            kategori: kategoriAktif.value,
            q: kataKunci.value,
        },
        {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        },
    );
}

function pilihKategori(value: string) {
    kategoriAktif.value = value;
    cariStakeholder();
}

function labelHalaman(label: string): string {
    return label.replace('&laquo;', '«').replace('&raquo;', '»');
}

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Direktori Stakeholder', href: '/eo/stakeholders' },
        ],
    },
});
</script>

<template>
    <Head title="Direktori Stakeholder" />

    <div class="flex flex-col gap-4 p-4 md:p-6">
        <!-- Header -->
        <header>
            <h1 class="text-2xl font-extrabold">
                Direktori Stakeholder
            </h1>
            <p class="mt-1 text-sm text-neutral-600 dark:text-muted-foreground">
                Temukan stakeholder yang sesuai dengan kebutuhan event Anda.
            </p>
        </header>

        <!-- Pencarian -->
        <DashCard>
            <form
                @submit.prevent="cariStakeholder"
                class="flex flex-col gap-3 sm:flex-row"
            >
                <input
                    v-model="kataKunci"
                    type="search"
                    placeholder="Cari nama stakeholder..."
                    class="min-w-0 flex-1 rounded-lg border border-neutral-300 bg-white px-4 py-2.5 text-sm outline-none focus:border-[#17564C] focus:ring-1 focus:ring-[#17564C] dark:border-border dark:bg-card"
                />

                <button
                    type="submit"
                    class="rounded-lg bg-[#17564C] px-5 py-2.5 text-sm font-semibold text-white hover:opacity-90"
                >
                    Cari stakeholder
                </button>
            </form>
        </DashCard>

        <!-- Filter kategori -->
        <section>
            <h2 class="mb-3 text-sm font-bold">
                Kategori Stakeholder
            </h2>

            <div class="flex flex-wrap gap-2">
                <button
                    type="button"
                    @click="pilihKategori('semua')"
                    :class="[
                        'rounded-lg border px-4 py-2 text-sm font-semibold transition',
                        kategoriAktif === 'semua'
                            ? 'border-[#17564C] bg-[#17564C] text-white'
                            : 'border-neutral-300 hover:bg-neutral-100 dark:border-border dark:hover:bg-muted',
                    ]"
                >
                    Semua
                </button>

                <button
                    v-for="item in kategori"
                    :key="item.value"
                    type="button"
                    @click="pilihKategori(item.value)"
                    :class="[
                        'rounded-lg border px-4 py-2 text-sm font-semibold transition',
                        kategoriAktif === item.value
                            ? 'border-[#17564C] bg-[#17564C] text-white'
                            : 'border-neutral-300 hover:bg-neutral-100 dark:border-border dark:hover:bg-muted',
                    ]"
                >
                    {{ item.label }}
                </button>
            </div>
        </section>

        <!-- Daftar stakeholder -->
        <DashCard>
            <div class="mb-4 flex flex-wrap items-center justify-between gap-2">
                <div>
                    <h2 class="font-bold">Daftar Stakeholder</h2>
                    <p class="text-xs text-neutral-500">
                        {{ stakeholders.total }} stakeholder ditemukan
                    </p>
                </div>
            </div>

            <!-- Jika kosong -->
            <div
                v-if="stakeholders.data.length === 0"
                class="flex flex-col items-center gap-2 py-12 text-center"
            >
                <div class="text-4xl">🔎</div>
                <p class="font-semibold">Stakeholder tidak ditemukan</p>
                <p class="text-sm text-neutral-500">
                    Belum ada stakeholder yang sesuai dengan pencarian atau kategori ini.
                </p>
                <button
                    type="button"
                    @click="pilihKategori('semua'); kataKunci = ''; cariStakeholder()"
                    class="mt-2 text-sm font-semibold text-[#17564C] hover:underline"
                >
                    Reset pencarian
                </button>
            </div>

            <!-- Kartu stakeholder -->
            <div v-else class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <article
                    v-for="stakeholder in stakeholders.data"
                    :key="stakeholder.id"
                    class="flex flex-col justify-between rounded-xl border border-neutral-200 p-4 transition hover:border-[#17564C] hover:shadow-sm dark:border-border"
                >
                    <div>
                        <div
                            class="mb-3 flex size-11 items-center justify-center rounded-xl bg-[#17564C]/10 text-lg font-bold text-[#17564C]"
                        >
                            {{ stakeholder.nama.charAt(0).toUpperCase() }}
                        </div>

                        <span
                            class="inline-flex rounded-full bg-emerald-100 px-3 py-1 text-xs font-semibold text-emerald-800"
                        >
                            {{ stakeholder.kategori }}
                        </span>

                        <h3 class="mt-3 font-bold">
                            {{ stakeholder.nama }}
                        </h3>

                        <p class="mt-1 text-xs text-neutral-500">
                            Akun: {{ stakeholder.nama_akun }}
                        </p>
                    </div>

                    <Link
                        :href="stakeholder.url"
                        class="mt-5 inline-flex items-center justify-center rounded-lg border border-[#17564C] px-4 py-2 text-sm font-semibold text-[#17564C] transition hover:bg-[#17564C] hover:text-white"
                    >
                        Lihat Profil
                    </Link>
                </article>
            </div>

            <!-- Pagination -->
            <div
                v-if="stakeholders.links.length > 3"
                class="mt-5 flex flex-wrap justify-end gap-1 border-t pt-4"
            >
                <template
                    v-for="(link, i) in stakeholders.links"
                    :key="i"
                >
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
    </div>
</template>
