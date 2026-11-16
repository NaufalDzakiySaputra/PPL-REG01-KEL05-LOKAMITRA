<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { Search, ClipboardList } from '@lucide/vue';
import DashCard from '@/components/dashboard/DashCard.vue';
import StatCard from '@/components/dashboard/StatCard.vue';

interface PengajuanItem {
    id: number;
    nama: string;
    email: string;
    nomor_telepon: string | null;
    role: string;
    role_label: string;
    nama_profil: string | null;
    status_akun: string;
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

const props = defineProps<{
    pengajuan: Paginator<PengajuanItem>;
    ringkasan: { menunggu: number; aktif: number; ditolak: number };
    filter: { status: string; role: string; q: string };
    roles: { value: string; label: string }[];
}>();

const filters = useForm({
    status: props.filter.status,
    role: props.filter.role,
    q: props.filter.q,
});

function cari(): void {
    filters.get('/admin/pengajuan', {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
}

function labelStatus(status: string): string {
    return ({ menunggu: 'Menunggu', aktif: 'Aktif', ditolak: 'Ditolak' } as Record<string, string>)[status] ?? status;
}

function warnaStatus(status: string): string {
    if (status === 'aktif') return 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300';
    if (status === 'ditolak') return 'bg-red-100 text-red-800 dark:bg-red-950 dark:text-red-300';
    return 'bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300';
}

function tanggal(value: string | null): string {
    if (!value) return '-';
    return new Intl.DateTimeFormat('id-ID', { dateStyle: 'medium', timeStyle: 'short' }).format(new Date(value));
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
        <header class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-sm text-muted-foreground">Pengelolaan akun</p>
                <h1 class="text-2xl font-semibold">Pengajuan Pendaftaran</h1>
                <p class="mt-1 text-sm text-muted-foreground">Periksa profil dan dokumen calon mitra sebelum mengaktifkan akun.</p>
            </div>
            <Link href="/admin/dashboard" class="text-sm font-medium text-primary hover:underline">Kembali ke dashboard</Link>
        </header>

        <!-- ringkasan: kartu angka yang sama dengan dashboard -->
        <section class="grid gap-3 sm:grid-cols-3">
            <StatCard label="Menunggu verifikasi" :nilai="ringkasan.menunggu" aksen="admin" />
            <StatCard label="Akun aktif" :nilai="ringkasan.aktif" aksen="admin" />
            <StatCard label="Akun ditolak" :nilai="ringkasan.ditolak" aksen="admin" />
        </section>

        <!-- filter -->
        <DashCard>
            <form @submit.prevent="cari" class="grid gap-3 md:grid-cols-[minmax(0,1fr)_200px_200px_auto]">
                <label class="flex flex-col gap-1 text-sm">
                    Cari pendaftar
                    <span class="relative">
                        <Search class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                        <input v-model="filters.q" type="search" placeholder="Nama, email, atau profil" class="w-full rounded-lg border bg-background py-2 pl-9 pr-3" />
                    </span>
                </label>
                <label class="flex flex-col gap-1 text-sm">
                    Status akun
                    <select v-model="filters.status" class="rounded-lg border bg-background px-3 py-2">
                        <option value="menunggu">Menunggu</option>
                        <option value="aktif">Aktif</option>
                        <option value="ditolak">Ditolak</option>
                        <option value="semua">Semua status</option>
                    </select>
                </label>
                <label class="flex flex-col gap-1 text-sm">
                    Role
                    <select v-model="filters.role" class="rounded-lg border bg-background px-3 py-2">
                        <option value="semua">Semua role</option>
                        <option v-for="role in roles" :key="role.value" :value="role.value">{{ role.label }}</option>
                    </select>
                </label>
                <div class="flex items-end">
                    <button type="submit" :disabled="filters.processing" class="w-full rounded-lg bg-primary px-4 py-2 text-sm font-medium text-primary-foreground disabled:opacity-50">Terapkan</button>
                </div>
            </form>
        </DashCard>

        <!-- daftar pendaftar -->
        <DashCard judul="Daftar pendaftar">
            <template #aksi>
                <span class="text-xs text-muted-foreground">{{ pengajuan.total }} data</span>
            </template>

            <div v-if="pengajuan.data.length" class="overflow-x-auto">
                <table class="w-full min-w-[850px] text-left text-sm">
                    <thead class="border-b text-xs uppercase text-muted-foreground">
                        <tr>
                            <th class="px-3 py-3 font-medium">Pendaftar</th>
                            <th class="px-3 py-3 font-medium">Role</th>
                            <th class="px-3 py-3 font-medium">Profil</th>
                            <th class="px-3 py-3 font-medium">Dokumen</th>
                            <th class="px-3 py-3 font-medium">Status</th>
                            <th class="px-3 py-3 font-medium">Tanggal daftar</th>
                            <th class="px-3 py-3 font-medium">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        <tr v-for="item in pengajuan.data" :key="item.id" class="align-top">
                            <td class="px-3 py-4">
                                <p class="font-medium">{{ item.nama }}</p>
                                <p class="mt-1 text-xs text-muted-foreground">{{ item.email }}</p>
                                <p v-if="item.nomor_telepon" class="text-xs text-muted-foreground">{{ item.nomor_telepon }}</p>
                            </td>
                            <td class="px-3 py-4">{{ item.role_label }}</td>
                            <td class="px-3 py-4">{{ item.nama_profil || '-' }}</td>
                            <td class="px-3 py-4">
                                <span>{{ item.jumlah_dokumen }} file</span>
                                <p v-if="item.dokumen_menunggu" class="mt-1 text-xs text-amber-700 dark:text-amber-300">{{ item.dokumen_menunggu }} menunggu</p>
                            </td>
                            <td class="px-3 py-4">
                                <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-medium" :class="warnaStatus(item.status_akun)">{{ labelStatus(item.status_akun) }}</span>
                            </td>
                            <td class="px-3 py-4 text-muted-foreground">{{ tanggal(item.tanggal_daftar) }}</td>
                            <td class="px-3 py-4">
                                <Link :href="`/admin/pengajuan/${item.id}`" class="inline-flex items-center gap-1 rounded-lg border px-3 py-2 text-xs font-medium hover:bg-muted">
                                    <ClipboardList class="h-3.5 w-3.5" /> Detail
                                </Link>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div v-else class="flex flex-col items-center gap-2 px-4 py-12 text-center">
                <ClipboardList class="h-8 w-8 text-muted-foreground" />
                <p class="font-medium">Belum ada pengajuan</p>
                <p class="text-sm text-muted-foreground">Tidak ada data yang sesuai dengan filter saat ini.</p>
            </div>

            <div v-if="pengajuan.links.length > 3" class="mt-4 flex flex-wrap justify-end gap-1 border-t pt-3">
                <template v-for="(link, index) in pengajuan.links" :key="index">
                    <Link v-if="link.url" :href="link.url" preserve-state preserve-scroll class="rounded-md border px-3 py-1.5 text-xs" :class="link.active ? 'bg-primary text-primary-foreground' : 'hover:bg-muted'">
                        {{ labelHalaman(link.label) }}
                    </Link>
                    <span v-else class="rounded-md border px-3 py-1.5 text-xs text-muted-foreground opacity-50">{{ labelHalaman(link.label) }}</span>
                </template>
            </div>
        </DashCard>
    </div>
</template>