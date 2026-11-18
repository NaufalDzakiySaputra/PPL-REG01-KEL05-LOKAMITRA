<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { computed, onMounted, reactive, ref } from 'vue';
import { toast } from 'vue-sonner';
import InputError from '@/components/InputError.vue';
import { ApiError, api } from '@/lib/api';
import type { ApiResponse, RoleProfile, RoleProfileField } from '@/types/api';

const roleLabel = ref('');
const fields = ref<RoleProfileField[]>([]);
const form = reactive<Record<string, string | number>>({});
const errors = ref<Record<string, string>>({});
const loading = ref(true);
const processing = ref(false);
const errorMessage = ref('');

const judul = computed(() => `Profil ${roleLabel.value}`);

function isiForm(data: RoleProfile): void {
    roleLabel.value = data.role_label;
    fields.value = data.fields;

    Object.keys(form).forEach((key) => delete form[key]);

    data.fields.forEach((field) => {
        form[field.key] = field.value ?? '';
    });
}

async function muatProfil(): Promise<void> {
    try {
        const response = await api.get<ApiResponse<RoleProfile>>(
            '/api/v1/me/role-profile',
        );

        isiForm(response.data);
    } catch (error) {
        if (error instanceof ApiError) {
            errorMessage.value = error.message;
            toast.error(error.message);
        }
    } finally {
        loading.value = false;
    }
}

async function simpan(): Promise<void> {
    processing.value = true;
    errors.value = {};

    try {
        const response = await api.put<ApiResponse<RoleProfile>>(
            '/api/v1/me/role-profile',
            { ...form },
        );

        isiForm(response.data);
        toast.success(response.message ?? 'Profil peran berhasil diperbarui.');
    } catch (error) {
        if (error instanceof ApiError) {
            errors.value = error.fieldErrors;
            toast.error(error.message);
        }
    } finally {
        processing.value = false;
    }
}

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Profil Peran', href: '/profil-peran' }],
    },
});

onMounted(() => {
    void muatProfil();
});
</script>

<template>
    <Head :title="judul" />

    <div class="flex flex-1 flex-col gap-6 p-4 md:p-6">
        <header>
            <p class="text-sm text-muted-foreground">Pengaturan profil</p>
            <h1 class="mt-1 text-2xl font-semibold">
                {{ roleLabel ? judul : 'Profil Peran' }}
            </h1>
            <p class="mt-1 text-sm text-muted-foreground">
                Perbarui informasi organisasi atau profil peran yang terhubung
                ke akun Anda.
            </p>
        </header>

        <p v-if="loading" class="text-sm text-muted-foreground">
            Memuat profil...
        </p>

        <p
            v-else-if="errorMessage"
            class="rounded-lg border border-destructive/30 bg-destructive/10 p-3 text-sm text-destructive"
        >
            {{ errorMessage }}
        </p>

        <form
            v-else
            class="max-w-3xl space-y-5 rounded-2xl border border-sidebar-border/70 bg-card p-5 dark:border-sidebar-border"
            @submit.prevent="simpan"
        >
            <div v-for="field in fields" :key="field.key" class="space-y-2">
                <label :for="field.key" class="text-sm font-medium">
                    {{ field.label }}
                    <span v-if="field.required" class="text-destructive"
                        >*</span
                    >
                </label>

                <textarea
                    v-if="field.type === 'textarea'"
                    :id="field.key"
                    v-model="form[field.key]"
                    rows="3"
                    class="w-full rounded-lg border border-input bg-background px-3 py-2 text-sm transition outline-none focus-visible:ring-2 focus-visible:ring-ring"
                    :aria-invalid="Boolean(errors[field.key])"
                    :disabled="processing"
                />
                <input
                    v-else
                    :id="field.key"
                    v-model="form[field.key]"
                    :type="field.type"
                    :min="field.type === 'number' ? 1 : undefined"
                    class="w-full rounded-lg border border-input bg-background px-3 py-2 text-sm transition outline-none focus-visible:ring-2 focus-visible:ring-ring"
                    :aria-invalid="Boolean(errors[field.key])"
                    :disabled="processing"
                />
                <InputError :message="errors[field.key]" />
            </div>

            <div
                class="flex flex-col-reverse gap-2 border-t pt-4 sm:flex-row sm:justify-end"
            >
                <button
                    type="submit"
                    :disabled="processing"
                    class="inline-flex items-center justify-center rounded-lg bg-primary px-5 py-2.5 text-sm font-medium text-primary-foreground hover:opacity-90 disabled:cursor-not-allowed disabled:opacity-50"
                >
                    {{ processing ? 'Menyimpan...' : 'Simpan perubahan' }}
                </button>
            </div>
        </form>
    </div>
</template>
