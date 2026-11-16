<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import { Check, Upload } from '@lucide/vue';
import { computed, reactive, ref } from 'vue';
import InputError from '@/components/InputError.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import {
    authButtonClass,
    authInputClass,
    authLabelClass,
    authLinkClass,
} from '@/lib/authStyles';
import { profilFields, type DocumentRequirement } from '@/lib/registerFields';
import { login } from '@/routes';
import { store } from '@/routes/register';

type RoleInfo = {
    title: string;
    description: string;
    checklist: string[];
};

const props = defineProps<{
    roles: { value: string; label: string }[];
    defaultRole: string;
    passwordRules: string;
    roleInfo: Record<string, RoleInfo>;
    documentRequirements: Record<string, DocumentRequirement[]>;
}>();

const role = ref(props.defaultRole);
const selectedFiles = reactive<Record<string, string>>({});

const currentRoleInfo = computed(() => props.roleInfo[role.value]);
const currentDocuments = computed(
    () => props.documentRequirements[role.value] ?? [],
);
const currentFields = computed(() => profilFields[role.value] ?? []);
const selectedRoleLabel = computed(
    () =>
        props.roles.find((item) => item.value === role.value)?.label ??
        'Profil',
);

function onFileChange(key: string, event: Event): void {
    const input = event.target as HTMLInputElement;
    selectedFiles[key] = input.files?.[0]?.name ?? '';
}

defineOptions({
    layout: {
        title: 'Buat akun Lokamitra',
        description: 'Lengkapi data akun dan profil sesuai peran Anda.',
        branded: true,
    },
});
</script>

<template>
    <Head title="Pendaftaran Akun" />

    <main
        class="grid min-h-svh grid-cols-1 bg-[#F6F7F4] lg:grid-cols-[minmax(320px,0.4fr)_minmax(0,0.6fr)]"
    >
        <!-- Panel informasi yang menyesuaikan peran terpilih -->
        <aside
            class="relative flex flex-col justify-center overflow-hidden rounded-br-[24px] bg-[#0D443D] px-6 py-9 text-white sm:px-10 lg:sticky lg:top-0 lg:h-svh lg:rounded-br-none lg:px-[9%] lg:py-12"
        >
            <div class="mx-auto w-full max-w-[470px]">
                <p class="text-sm font-semibold text-[#F5B52E]">
                    Pendaftaran Akun
                </p>
                <h1
                    class="mt-5 text-3xl leading-tight font-bold tracking-tight sm:text-4xl"
                >
                    {{
                        currentRoleInfo?.title ??
                        'Daftar sebagai Mitra Lokamitra'
                    }}
                </h1>
                <p
                    class="mt-4 text-sm leading-relaxed text-white/80 sm:text-base"
                >
                    {{ currentRoleInfo?.description }}
                </p>

                <div class="mt-8 rounded-xl bg-[#11564D] p-5">
                    <ul class="grid gap-3">
                        <li
                            v-for="item in currentRoleInfo?.checklist ?? []"
                            :key="item"
                            class="flex items-start gap-3 text-sm leading-relaxed text-white/90"
                        >
                            <Check
                                class="mt-0.5 size-4 shrink-0 text-[#F5B52E]"
                            />
                            <span>{{ item }}</span>
                        </li>
                    </ul>
                </div>
            </div>
        </aside>

        <!-- Form pendaftaran -->
        <section
            class="flex justify-center px-4 py-6 sm:px-7 sm:py-9 lg:px-9 lg:py-10"
        >
            <div class="w-full max-w-[760px]">
                <Form
                    v-bind="store.form()"
                    :reset-on-success="['password', 'password_confirmation']"
                    v-slot="{ errors, processing }"
                    class="rounded-xl border border-[#83B67B] bg-white p-5 shadow-sm sm:p-7 lg:p-8"
                >
                    <h2
                        class="mb-7 text-center text-xl font-bold text-[#161A18]"
                    >
                        Formulir pendaftaran
                    </h2>

                    <!-- Pilihan role -->
                    <section class="mb-7 border-b border-[#E8EBE8] pb-6">
                        <h3 class="mb-3 text-base font-bold text-[#151A17]">
                            Pilih peran
                        </h3>
                        <div class="grid grid-cols-2 gap-2 sm:grid-cols-3">
                            <label
                                v-for="item in roles"
                                :key="item.value"
                                :class="[
                                    'cursor-pointer rounded-lg border px-3 py-2.5 text-center text-sm font-semibold transition-colors',
                                    role === item.value
                                        ? 'border-[#216B60] bg-[#216B60] text-white'
                                        : 'border-[#D8DEDA] bg-white text-[#44504A] hover:border-[#216B60]',
                                ]"
                            >
                                <input
                                    v-model="role"
                                    type="radio"
                                    name="role"
                                    :value="item.value"
                                    class="sr-only"
                                />
                                {{ item.label }}
                            </label>
                        </div>
                        <InputError :message="errors.role" />
                    </section>

                    <!-- 1. Data akun -->
                    <section class="mb-7 border-b border-[#E8EBE8] pb-7">
                        <h3 class="mb-4 text-lg font-bold text-[#151A17]">
                            1. Data akun
                        </h3>
                        <div class="grid gap-x-5 gap-y-4 sm:grid-cols-2">
                            <div class="grid gap-1.5">
                                <Label for="name" :class="authLabelClass">{{
                                    role === 'pengunjung'
                                        ? 'Nama lengkap'
                                        : 'Nama lengkap penanggung jawab'
                                }}</Label>
                                <Input
                                    id="name"
                                    name="name"
                                    required
                                    autocomplete="name"
                                    placeholder="Sesuai identitas"
                                    :class="authInputClass"
                                />
                                <InputError :message="errors.name" />
                            </div>
                            <div class="grid gap-1.5">
                                <Label
                                    for="nomor_telepon"
                                    :class="authLabelClass"
                                    >Nomor Telepon</Label
                                >
                                <Input
                                    id="nomor_telepon"
                                    name="nomor_telepon"
                                    type="tel"
                                    required
                                    autocomplete="tel"
                                    placeholder="08XXXXXXXXXX"
                                    :class="authInputClass"
                                />
                                <InputError :message="errors.nomor_telepon" />
                            </div>
                            <div class="grid gap-1.5">
                                <Label for="email" :class="authLabelClass"
                                    >Email</Label
                                >
                                <Input
                                    id="email"
                                    name="email"
                                    type="email"
                                    required
                                    autocomplete="email"
                                    placeholder="nama@gmail.com"
                                    :class="authInputClass"
                                />
                                <p
                                    class="text-[11px] leading-snug text-[#858D88]"
                                >
                                    {{
                                        role === 'pengunjung'
                                            ? 'Gunakan alamat email pribadi yang masih aktif.'
                                            : 'Gunakan email aktif milik usaha atau instansi Anda.'
                                    }}
                                </p>
                                <InputError :message="errors.email" />
                            </div>
                            <div class="grid gap-1.5">
                                <Label for="password" :class="authLabelClass"
                                    >Password</Label
                                >
                                <PasswordInput
                                    id="password"
                                    name="password"
                                    required
                                    autocomplete="new-password"
                                    placeholder="Minimal 8 karakter"
                                    :passwordrules="passwordRules"
                                    :class="authInputClass"
                                />
                                <InputError :message="errors.password" />
                            </div>
                            <div class="grid gap-1.5 sm:col-span-2">
                                <Label
                                    for="password_confirmation"
                                    :class="authLabelClass"
                                    >Konfirmasi password</Label
                                >
                                <PasswordInput
                                    id="password_confirmation"
                                    name="password_confirmation"
                                    required
                                    autocomplete="new-password"
                                    placeholder="Ulangi password"
                                    :passwordrules="passwordRules"
                                    :class="authInputClass"
                                />
                                <InputError
                                    :message="errors.password_confirmation"
                                />
                            </div>
                        </div>
                    </section>

                    <!-- 2. Data profil sesuai role -->
                    <section
                        v-if="role !== 'pengunjung'"
                        class="mb-7 border-b border-[#E8EBE8] pb-7"
                    >
                        <h3 class="mb-4 text-lg font-bold text-[#151A17]">
                            2. Data {{ selectedRoleLabel }}
                        </h3>
                        <div class="grid gap-x-5 gap-y-4 sm:grid-cols-2">
                            <div
                                v-for="field in currentFields"
                                :key="`${role}-${field.name}`"
                                :class="[
                                    'grid gap-1.5',
                                    field.full ? 'sm:col-span-2' : '',
                                ]"
                            >
                                <Label
                                    :for="field.name"
                                    :class="authLabelClass"
                                    >{{ field.label }}</Label
                                >
                                <select
                                    v-if="field.type === 'select'"
                                    :id="field.name"
                                    :name="field.name"
                                    :required="field.required"
                                    :class="[authInputClass, 'appearance-auto']"
                                >
                                    <option value="" selected disabled>
                                        {{ field.placeholder }}
                                    </option>
                                    <option
                                        v-for="option in field.options ?? []"
                                        :key="option"
                                        :value="option"
                                    >
                                        {{ option }}
                                    </option>
                                </select>
                                <Input
                                    v-else
                                    :id="field.name"
                                    :name="field.name"
                                    :type="field.type ?? 'text'"
                                    :required="field.required"
                                    :placeholder="field.placeholder"
                                    :min="
                                        field.type === 'number' ? 1 : undefined
                                    "
                                    :class="authInputClass"
                                />
                                <InputError :message="errors[field.name]" />
                            </div>
                        </div>
                    </section>

                    <!-- 3. Dokumen persyaratan -->
                    <section
                        v-if="currentDocuments.length"
                        class="mb-7 border-b border-[#E8EBE8] pb-7"
                    >
                        <h3 class="text-lg font-bold text-[#151A17]">
                            3. Dokumen Persyaratan
                        </h3>
                        <p class="mb-4 text-xs text-[#858D88]">
                            Format PDF, JPG, atau PNG. Dokumen hanya dapat
                            dilihat oleh Admin. Maksimal 10 MB per dokumen.
                        </p>

                        <div class="grid gap-3">
                            <div
                                v-for="doc in currentDocuments"
                                :key="doc.key"
                                class="rounded-lg border border-[#E2E6E3] p-3.5"
                            >
                                <div
                                    class="flex items-start justify-between gap-3"
                                >
                                    <div>
                                        <h4
                                            class="text-sm font-semibold text-[#28332D]"
                                        >
                                            {{ doc.jenis_dokumen }}
                                        </h4>
                                        <p
                                            class="mt-1 text-xs leading-relaxed text-[#858D88]"
                                        >
                                            {{ doc.deskripsi }}
                                        </p>
                                    </div>
                                    <span
                                        :class="
                                            doc.wajib
                                                ? 'bg-[#F5E4C7] text-[#8A6330]'
                                                : 'bg-[#E8EAE9] text-[#737A76]'
                                        "
                                        class="shrink-0 rounded-full px-2.5 py-1 text-[10px] font-semibold uppercase"
                                    >
                                        {{ doc.wajib ? 'Wajib' : 'Opsional' }}
                                    </span>
                                </div>

                                <div
                                    class="mt-3 flex flex-col gap-2 rounded-md bg-[#F2F3F2] p-2.5 sm:flex-row sm:items-center sm:justify-between"
                                >
                                    <div
                                        class="flex min-w-0 items-center gap-2 text-xs text-[#7C8580]"
                                    >
                                        <Upload class="size-4 shrink-0" />
                                        <span class="truncate">{{
                                            selectedFiles[doc.key] ||
                                            'Belum ada berkas dipilih'
                                        }}</span>
                                    </div>
                                    <label
                                        class="inline-flex cursor-pointer items-center justify-center rounded-md border border-[#216B60] bg-white px-4 py-2 text-xs font-semibold text-[#216B60] hover:bg-[#EFF7F4]"
                                    >
                                        Pilih berkas
                                        <input
                                            type="file"
                                            :name="`documents[${doc.key}]`"
                                            accept=".pdf,.jpg,.jpeg,.png,application/pdf,image/jpeg,image/png"
                                            :required="doc.wajib"
                                            class="sr-only"
                                            @change="
                                                onFileChange(doc.key, $event)
                                            "
                                        />
                                    </label>
                                </div>
                                <InputError
                                    :message="errors[`documents.${doc.key}`]"
                                />
                            </div>
                        </div>
                    </section>

                    <!-- Persetujuan dan status -->
                    <div
                        v-if="role !== 'pengunjung'"
                        class="mb-4 flex items-start gap-2.5"
                    >
                        <input
                            id="persetujuan"
                            type="checkbox"
                            name="persetujuan"
                            value="1"
                            required
                            class="mt-0.5 size-4 rounded border-[#AEB8B2] accent-[#216B60]"
                        />
                        <label
                            for="persetujuan"
                            class="text-xs leading-relaxed text-[#59635E]"
                        >
                            Saya menyatakan data dan dokumen yang diunggah
                            benar, serta menyetujui syarat dan ketentuan
                            penggunaan Lokamitra.
                        </label>
                    </div>
                    <InputError
                        v-if="role !== 'pengunjung'"
                        :message="errors.persetujuan"
                    />

                    <div
                        v-if="role === 'pengunjung'"
                        class="mb-3 rounded-lg border border-[#CDE4D5] bg-[#EFF8F2] px-4 py-3 text-xs leading-relaxed text-[#17613F]"
                    >
                        Akun Pengunjung akan langsung
                        <strong>aktif</strong> setelah pendaftaran berhasil.
                        Kamu bisa langsung login menggunakan email dan password
                        yang dibuat.
                    </div>
                    <div
                        v-else
                        class="mb-3 rounded-lg bg-[#FFF0D4] px-4 py-3 text-xs leading-relaxed text-[#8A6632]"
                    >
                        Setelah dikirim, akun berstatus
                        <strong>Menunggu</strong> verifikasi. Admin akan
                        memeriksa dokumen lalu menyetujui atau menolak disertai
                        alasan. Akun baru dapat dipakai setelah disetujui.
                    </div>

                    <Button
                        type="submit"
                        :class="authButtonClass"
                        :disabled="processing"
                        data-test="register-user-button"
                    >
                        <Spinner v-if="processing" />
                        {{
                            role === 'pengunjung'
                                ? 'Buat Akun Pengunjung'
                                : 'Kirim Pendaftaran'
                        }}
                    </Button>

                    <p class="mt-3 text-center text-xs text-[#59635E]">
                        Sudah punya akun?
                        <TextLink :href="login()" :class="authLinkClass"
                            >Masuk</TextLink
                        >
                    </p>
                </Form>
            </div>
        </section>
    </main>
</template>
