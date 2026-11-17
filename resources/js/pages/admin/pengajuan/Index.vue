<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ClipboardList, Search } from '@lucide/vue';
import { onMounted, reactive, ref } from 'vue';
import DashCard from '@/components/dashboard/DashCard.vue';
import StatCard from '@/components/dashboard/StatCard.vue';
import { ApiError, api } from '@/lib/api';

interface PengajuanItem {
    id: number;
    nama: string;
    email: string;
    nomor_telepon: string | null;
    role: string;
    role_label: string;
    nama_profil: string | null;
    status_akun: string;
    status_verifikasi?: string;
    jumlah_dokumen: number;
    dokumen_menunggu: number;
    tanggal_daftar: string | null;
}

interface Paginator<T> {
    data: T[];
    links: { url: string | null; label: string; active: boolean }[];
    current_page: number;
    last_page: number;
    total: number;
}

interface Ringkasan {
    menunggu: number;
    aktif: number;
    ditolak: number;
}

interface DaftarResponse {
    pengajuan: Paginator<PengajuanItem>;
    ringkasan: Ringkasan;
    filter: { status: string; status_api?: string; role: string; q: string };
    roles: { value: string; label: string }[];
}

const props = defineProps<DaftarResponse>();

const daftar = ref<Paginator<PengajuanItem>>(props.pengajuan);
const ringkasan = ref<Ringkasan>(props.ringkasan);
const roles = ref(props.roles);
const filters = reactive({
    status: props.filter.status_api ?? props.filter.status,
    role: props.filter.role,
    q: props.filter.q,
});

const loading = ref(false);
const errorMessage = ref('');

async function muat(url = '/api/v1/admin/pengajuan'): Promise<void> {
    loading.value = true;
    errorMessage.value = '';

    try {
        const response = await api.get<DaftarResponse>(url);

        daftar.value = response.pengajuan;
        ringkasan.value = response.ringkasan;
        roles.value = response.roles;
        filters.status = response.filter.status_api ?? response.filter.status;
        filters.role = response.filter.role;
        filters.q = response.filter.q;
    } catch (error) {
        errorMessage.value =
            error instanceof ApiError
                ? error.message
                : 'Tidak dapat memuat data pengajuan.';
    } finally {
        loading.value = false;
    }
}

async function cari(): Promise<void> {
    const query = new URLSearchParams({
        status: filters.status,
        role: filters.role,
        q: filters.q,
    });

    await muat(`/api/v1/admin/pengajuan?${query.toString()}`);
}

function muatHalaman(url: string | null): void {
    if (url) void muat(url);
}

onMounted(() => {
    // Data awal sudah dikirim Inertia; muat ulang agar filter dan pagination
    // selalu berasal dari endpoint API yang sama dengan form aksi.
    void muat();
});

function labelStatus(status: string): string {
    return (
        (
            {
                pending: 'Menunggu',
                verified: 'Terverifikasi',
                rejected: 'Ditolak',
                // Nilai database tetap didukung untuk data awal dari Inertia.
                menunggu: 'Menunggu',
                aktif: 'Aktif',
                ditolak: 'Ditolak',
            } as Record<string, string>
        )[status] ?? status
    );
}

function warnaStatus(status: string): string {
    if (status === 'verified' || status === 'aktif')
        return 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300';
    if (status === 'rejected' || status === 'ditolak')
        return 'bg-red-100 text-red-800 dark:bg-red-950 dark:text-red-300';
    return 'bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300';
}

function tanggal(value: string | null): string {
    if (!value) return '-';
    return new Intl.DateTimeFormat('id-ID', {
        dateStyle: 'medium',
        timeStyle: 'short',
    }).format(new Date(value));
}

function labelHalaman(label: string): string {
    return label.replace('&laquo;', '«').replace('&raquo;', '»');
}

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard Pengelola', href: '/admin/dashboard' },
            { title: 'Pengajuan Pendaftaran', href: '/admin/pengajuan' },
        ],
    },
});
</script>

<template>
    <Head title="Pengajuan Pendaftaran" />

    <div class="flex flex-1 flex-col gap-6 p-4 md:p-6">
        <header
            class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between"
        >
            <div>
                <p class="text-sm text-muted-foreground">Pengelolaan akun</p>
                <h1 class="text-2xl font-semibold">Pengajuan Pendaftaran</h1>
                <p class="mt-1 text-sm text-muted-foreground">
                    Periksa profil dan dokumen calon mitra sebelum mengaktifkan
                    akun.
                </p>
            </div>
            <Link
                href="/admin/dashboard"
                class="text-sm font-medium text-primary hover:underline"
                >Kembali ke dashboard</Link
            >
        </header>

        <div
            v-if="errorMessage"
            class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700"
        >
            {{ errorMessage }}
        </div>

        <!-- ringkasan: kartu angka yang sama dengan dashboard -->
        <section class="grid gap-3 sm:grid-cols-3">
            <StatCard
                label="Menunggu verifikasi"
                :nilai="ringkasan.menunggu"
                aksen="admin"
            />
            <StatCard
                label="Akun aktif"
                :nilai="ringkasan.aktif"
                aksen="admin"
            />
            <StatCard
                label="Akun ditolak"
                :nilai="ringkasan.ditolak"
                aksen="admin"
            />
        </section>

        <!-- filter -->
        <DashCard>
            <form
                @submit.prevent="cari"
                class="grid gap-3 md:grid-cols-[minmax(0,1fr)_200px_200px_auto]"
            >
                <label class="flex flex-col gap-1 text-sm">
                    Cari pendaftar
                    <span class="relative">
                        <Search
                            class="absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-muted-foreground"
                        />
                        <input
                            v-model="filters.q"
                            type="search"
                            placeholder="Nama, email, atau profil"
                            class="w-full rounded-lg border bg-background py-2 pr-3 pl-9"
                        />
                    </span>
                </label>
                <label class="flex flex-col gap-1 text-sm">
                    Status akun
                    <select
                        v-model="filters.status"
                        class="rounded-lg border bg-background px-3 py-2"
                    >
                        <option value="pending">Menunggu</option>
                        <option value="verified">Terverifikasi</option>
                        <option value="rejected">Ditolak</option>
                        <option value="semua">Semua status</option>
                    </select>
                </label>
                <label class="flex flex-col gap-1 text-sm">
                    Role
                    <select
                        v-model="filters.role"
                        class="rounded-lg border bg-background px-3 py-2"
                    >
                        <option value="semua">Semua role</option>
                        <option
                            v-for="role in roles"
                            :key="role.value"
                            :value="role.value"
                        >
                            {{ role.label }}
                        </option>
                    </select>
                </label>
                <div class="flex items-end">
                    <button
                        type="submit"
                        :disabled="loading"
                        class="w-full rounded-lg bg-primary px-4 py-2 text-sm font-medium text-primary-foreground disabled:opacity-50"
                    >
                        {{ loading ? 'Memuat...' : 'Terapkan' }}
                    </button>
                </div>
            </form>
        </DashCard>

        <!-- daftar pendaftar -->
        <DashCard judul="Daftar pendaftar">
            <template #aksi>
                <span class="text-xs text-muted-foreground"
                    >{{ daftar.total }} data</span
                >
            </template>

            <div v-if="daftar.data.length" class="overflow-x-auto">
                <table class="w-full min-w-[850px] text-left text-sm">
                    <thead
                        class="border-b text-xs text-muted-foreground uppercase"
                    >
                        <tr>
                            <th class="px-3 py-3 font-medium">Pendaftar</th>
                            <th class="px-3 py-3 font-medium">Role</th>
                            <th class="px-3 py-3 font-medium">Profil</th>
                            <th class="px-3 py-3 font-medium">Dokumen</th>
                            <th class="px-3 py-3 font-medium">Status</th>
                            <th class="px-3 py-3 font-medium">
                                Tanggal daftar
                            </th>
                            <th class="px-3 py-3 font-medium">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        <tr
                            v-for="item in daftar.data"
                            :key="item.id"
                            class="align-top"
                        >
                            <td class="px-3 py-4">
                                <p class="font-medium">{{ item.nama }}</p>
                                <p class="mt-1 text-xs text-muted-foreground">
                                    {{ item.email }}
                                </p>
                                <p
                                    v-if="item.nomor_telepon"
                                    class="text-xs text-muted-foreground"
                                >
                                    {{ item.nomor_telepon }}
                                </p>
                            </td>
                            <td class="px-3 py-4">{{ item.role_label }}</td>
                            <td class="px-3 py-4">
                                {{ item.nama_profil || '-' }}
                            </td>
                            <td class="px-3 py-4">
                                <span>{{ item.jumlah_dokumen }} file</span>
                                <p
                                    v-if="item.dokumen_menunggu"
                                    class="mt-1 text-xs text-amber-700 dark:text-amber-300"
                                >
                                    {{ item.dokumen_menunggu }} menunggu
                                </p>
                            </td>
                            <td class="px-3 py-4">
                                <span
                                    class="inline-flex rounded-full px-2.5 py-1 text-xs font-medium"
                                    :class="
                                        warnaStatus(
                                            item.status_verifikasi ??
                                                item.status_akun,
                                        )
                                    "
                                    >{{
                                        labelStatus(
                                            item.status_verifikasi ??
                                                item.status_akun,
                                        )
                                    }}</span
                                >
                            </td>
                            <td class="px-3 py-4 text-muted-foreground">
                                {{ tanggal(item.tanggal_daftar) }}
                            </td>
                            <td class="px-3 py-4">
                                <Link
                                    :href="`/admin/pengajuan/${item.id}`"
                                    class="inline-flex items-center gap-1 rounded-lg border px-3 py-2 text-xs font-medium hover:bg-muted"
                                >
                                    <ClipboardList class="h-3.5 w-3.5" /> Detail
                                </Link>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div
                v-else
                class="flex flex-col items-center gap-2 px-4 py-12 text-center"
            >
                <ClipboardList class="h-8 w-8 text-muted-foreground" />
                <p class="font-medium">Belum ada pengajuan</p>
                <p class="text-sm text-muted-foreground">
                    Tidak ada data yang sesuai dengan filter saat ini.
                </p>
            </div>

            <div
                v-if="daftar.links.length > 3"
                class="mt-4 flex flex-wrap justify-end gap-1 border-t pt-3"
            >
                <template v-for="(link, index) in daftar.links" :key="index">
                    <button
                        v-if="link.url"
                        type="button"
                        :disabled="loading"
                        class="rounded-md border px-3 py-1.5 text-xs disabled:opacity-50"
                        :class="
                            link.active
                                ? 'bg-primary text-primary-foreground'
                                : 'hover:bg-muted'
                        "
                        @click="muatHalaman(link.url)"
                    >
                        {{ labelHalaman(link.label) }}
                    </button>
                    <span
                        v-else
                        class="rounded-md border px-3 py-1.5 text-xs text-muted-foreground opacity-50"
                        >{{ labelHalaman(link.label) }}</span
                    >
                </template>
            </div>
        </DashCard>
    </div>
</template>
