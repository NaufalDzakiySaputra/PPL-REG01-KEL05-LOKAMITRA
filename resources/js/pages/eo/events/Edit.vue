<script setup lang="ts">
import {Head, Link, useForm} from '@inertiajs/vue3';
import DashCard from '@/components/dashboard/DashCard.vue';
import InputError from '@/components/InputError.vue';

const props = defineProps<{
    event: {
        id: number;
        nama: string;
        kategori: string | null;
        lokasi: string | null;
        deskripsi: string | null;
        tanggal_mulai: string;
        tanggal_selesai: string;
    };
    kategori: string[];
}>();

const hariIni = new Date(Date.now() - new Date().getTimezoneOffset() * 60000)
    .toISOString()
    .slice(0, 10);

const minMulai =
    props.event.tanggal_mulai < hariIni ? props.event.tanggal_mulai : hariIni;

const form = useForm({
    nama_event: props.event.nama,
    kategori_event: props.event.kategori ?? '',
    tanggal_mulai: props.event.tanggal_mulai,
    tanggal_selesai: props.event.tanggal_selesai,
    lokasi: props.event.lokasi ?? '',
    deskripsi: props.event.deskripsi ?? '',
});

function samakanTanggal(): void {
    if (form.tanggal_selesai < form.tanggal_mulai){
        form.tanggal_selesai = form.tanggal_mulai;
    }
}

function simpan(): void {
    form.put(`/eo/events/${props.event.id}`, { preserveScroll: true });
}

const kelasInput = 
    'w-full rounded-lg border border-input bg-background px-3 py-2 text-sm outline-none transition focus-visible:ring-2 focus-visible:ring-ring aria-[invalid=true]:border-destructive';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Event', href: '/eo/events' },
            { title: 'Ubah Event', href: '#' },
        ],
    },
});
</script>
<template>
    <Head title="Ubah Event" />

    <div class="flex flex-col gap-4 p-4 md:p-6">
        <header>
            <h1 class="text-2xl font-extrabold">Ubah Informasi Event</h1>
            <p class="text-sm text-neutral-600 dark:text-muted-foreground">
                Perbarui data agar informasi penyelenggaraan tetap akurat.
            </p>
        </header>

        <DashCard judul="Informasi event">
            <form class="space-y-5" novalidate @submit.prevent="simpan">
                <div class="space-y-2">
                    <label for="nama_event" class="text-sm font-medium">
                        Nama event <span class="text-destructive">*</span>
                    </label>
                    <input id="nama_event" v-model="form.nama_event" type="text"
                        maxlength="255" :class="kelasInput"
                        :aria-invalid="Boolean(form.errors.nama_event)" />
                    <InputError :message="form.errors.nama_event" />
                </div>

                <div class="space-y-2">
                    <label for="kategori_event" class="text-sm font-medium">Kategori</label>
                    <select id="kategori_event" v-model="form.kategori_event"
                        :class="kelasInput"
                        :aria-invalid="Boolean(form.errors.kategori_event)">
                        <option value="">Pilih kategori</option>
                        <option v-for="k in kategori" :key="k" :value="k">{{ k }}</option>
                    </select>
                    <InputError :message="form.errors.kategori_event" />
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="space-y-2">
                        <label for="tanggal_mulai" class="text-sm font-medium">
                            Tanggal mulai <span class="text-destructive">*</span>
                        </label>
                        <input id="tanggal_mulai" v-model="form.tanggal_mulai" type="date"
                            :min="minMulai" :class="kelasInput"
                            :aria-invalid="Boolean(form.errors.tanggal_mulai)"
                            @change="samakanTanggal" />
                        <InputError :message="form.errors.tanggal_mulai" />
                    </div>
                    <div class="space-y-2">
                        <label for="tanggal_selesai" class="text-sm font-medium">
                            Tanggal selesai <span class="text-destructive">*</span>
                        </label>
                        <input id="tanggal_selesai" v-model="form.tanggal_selesai" type="date"
                            :min="form.tanggal_mulai" :class="kelasInput"
                            :aria-invalid="Boolean(form.errors.tanggal_selesai)" />
                        <InputError :message="form.errors.tanggal_selesai" />
                    </div>
                </div>

                <div class="space-y-2">
                    <label for="lokasi" class="text-sm font-medium">
                        Lokasi <span class="text-destructive">*</span>
                    </label>
                    <input id="lokasi" v-model="form.lokasi" type="text" maxlength="255"
                        :class="kelasInput" :aria-invalid="Boolean(form.errors.lokasi)" />
                    <InputError :message="form.errors.lokasi" />
                </div>

                <div class="space-y-2">
                    <label for="deskripsi" class="text-sm font-medium">Deskripsi</label>
                    <textarea id="deskripsi" v-model="form.deskripsi" rows="5"
                        :class="kelasInput" :aria-invalid="Boolean(form.errors.deskripsi)" />
                    <InputError :message="form.errors.deskripsi" />
                </div>

                <div class="flex flex-col-reverse gap-2 border-t pt-4 sm:flex-row sm:justify-end">
                    <Link :href="`/eo/events/${event.id}`"
                        class="inline-flex items-center justify-center rounded-lg border border-[#17564C] px-5 py-2.5 text-sm font-semibold text-[#17564C] hover:bg-[#17564C]/5">
                        Batal
                    </Link>
                    <button type="submit" :disabled="form.processing"
                        class="inline-flex items-center justify-center rounded-lg bg-[#17564C] px-5 py-2.5 text-sm font-semibold text-white hover:opacity-90 disabled:cursor-not-allowed disabled:opacity-50"
                        data-test="simpan-perubahan">
                        {{ form.processing ? 'Menyimpan...' : 'Simpan perubahan' }}
                    </button>
                </div>
            </form>
        </DashCard>
    </div>
</template>