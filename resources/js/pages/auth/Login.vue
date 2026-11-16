<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import { Check } from '@lucide/vue';
import InputError from '@/components/InputError.vue';
import LokamitraLogo from '@/components/LokamitraLogo.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { authButtonClass, authInputClass, authLabelClass, authLinkClass } from '@/lib/authStyles';
import { register } from '@/routes';
import { store } from '@/routes/login';
import { request } from '@/routes/password';

defineOptions({
    layout: {
        title: 'Masuk ke Lokamitra',
        description: 'Masuk menggunakan akun Lokamitra Anda.',
        branded: true,
    },
});

defineProps<{
    status?: string;
    canResetPassword: boolean;
}>();

const features = [
    {
        title: 'Komunikasi Kolaboratif',
        description: 'Forum diskusi dan group chat real-time per event atau divisi.',
    },
    {
        title: 'Transparansi Informasi',
        description: 'Dokumen, Rundown, dan pengumuman tersedia untuk semua pihak.',
    },
    {
        title: 'Koordinasi Operasional',
        description: 'Tugas, Timeline, Kalender, dan Dashboard progress dalam satu tempat.',
    },
];
</script>

<template>
    <Head title="Masuk" />

    <main class="grid min-h-svh grid-cols-1 bg-[#F6F4F2] lg:grid-cols-2">
        <!-- Panel informasi Lokamitra -->
        <aside
            class="relative flex min-h-[340px] flex-col justify-center overflow-hidden rounded-br-[26px] bg-[#0D443D] px-7 py-10 text-white sm:px-12 lg:min-h-svh lg:rounded-br-[28px] lg:px-[9%] lg:py-16"
        >
            <div class="relative z-10 mx-auto w-full max-w-[600px]">
                <p class="max-w-[560px] text-3xl leading-tight font-bold tracking-tight sm:text-4xl lg:text-[42px] lg:leading-[1.2]">
                    Satu ruang kerja untuk semua pihak di balik sebuah event.
                </p>
                <p class="mt-7 max-w-[590px] text-base leading-relaxed text-white/80 sm:text-lg">
                    Penyelenggara, Pemerintah Daerah, Vendor, Komunitas, Akademisi, dan media
                    Berkolaborasi di tempat yang sama, terstruktur dan terdokumentasi.
                </p>

                <div class="mt-7 grid gap-6 sm:grid-cols-1">
                    <div v-for="feature in features" :key="feature.title" class="flex items-start gap-3">
                        <span class="mt-0.5 flex size-12 shrink-0 items-center justify-center rounded-xl bg-[#11665A] text-[#F5B52E]">
                            <Check class="size-7 stroke-[3]" />
                        </span>
                        <div>
                            <h2 class="font-bold text-white">{{ feature.title }}</h2>
                            <p class="mt-0.5 text-sm leading-relaxed text-white/85 sm:text-base">
                                {{ feature.description }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </aside>

        <!-- Form login -->
        <section class="flex items-center justify-center px-5 py-10 sm:px-8 lg:min-h-svh lg:px-10 lg:py-14">
            <div class="w-full max-w-[510px]">
                <div class="mb-7 flex justify-center">
                    <LokamitraLogo />
                </div>

                <div class="rounded-2xl border border-[#E5E2DF] bg-white px-6 py-8 shadow-[0_3px_7px_rgba(0,0,0,0.18)] sm:px-9 sm:py-9">
                    <div class="mb-6 text-center">
                        <h1 class="text-3xl font-bold tracking-tight text-black sm:text-[34px]">
                            Selamat datang kembali
                        </h1>
                        <p class="mx-auto mt-2 max-w-[390px] text-sm leading-relaxed text-[#858585]">
                            Masuk menggunakan akun Lokamitra sesuai peran Anda: Admin, Event Organizer,
                            Pemerintah Daerah, Vendor/UMKM, Komunitas Lokal, Akademisi, Media, atau Pengunjung.
                        </p>
                    </div>

                    <div v-if="status" class="mb-5 rounded-lg border border-[#B9DCCB] bg-[#EFF8F2] px-4 py-3 text-sm text-[#17613F]">
                        {{ status }}
                    </div>

                    <Form
                        v-bind="store.form()"
                        :reset-on-success="['password']"
                        v-slot="{ errors, processing }"
                        class="flex flex-col gap-5"
                    >
                        <div class="grid gap-2">
                            <Label for="email" :class="authLabelClass">Email</Label>
                            <Input
                                id="email"
                                type="email"
                                name="email"
                                required
                                v-focus
                                autocomplete="email"
                                placeholder="nama@gmail.com"
                                :class="authInputClass"
                            />
                            <InputError :message="errors.email" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="password" :class="authLabelClass">Password</Label>
                            <PasswordInput
                                id="password"
                                name="password"
                                required
                                autocomplete="current-password"
                                placeholder="Masukkan kata sandi"
                                :class="authInputClass"
                            />
                            <InputError :message="errors.password" />
                            <div v-if="canResetPassword" class="pt-1 text-center">
                                <TextLink :href="request()" class="text-xs font-semibold !text-[#216B60] underline-offset-4 hover:!text-[#0D443D] hover:underline">
                                    Lupa password?
                                </TextLink>
                            </div>
                        </div>

                        <Button
                            type="submit"
                            :class="[authButtonClass, 'mx-auto mt-1 max-w-[280px]']"
                            :disabled="processing"
                            data-test="login-button"
                        >
                            <Spinner v-if="processing" />
                            Masuk
                        </Button>

                        <p class="text-center text-sm text-[#555]">
                            Belum punya akun?
                            <TextLink :href="register()" :class="authLinkClass">Daftar Sekarang</TextLink>
                        </p>
                    </Form>
                </div>
            </div>
        </section>
    </main>
</template>
