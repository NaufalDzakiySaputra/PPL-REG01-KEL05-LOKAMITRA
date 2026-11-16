<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';
import InputError from '@/components/InputError.vue';

type Field = {
    key: string;
    label: string;
    type: 'text' | 'url' | 'number' | 'textarea';
    required: boolean;
    value: string | number | null;
};

const props = defineProps<{
    roleLabel: string;
    fields: Field[];
}>();

const form = useForm<Record<string, string | number>>(
    Object.fromEntries(props.fields.map((field) => [field.key, field.value ?? ''])),
);

const judul = computed(() => `Profil ${props.roleLabel}`);

function simpan(): void {
    form.put('/profil-peran', {
        preserveScroll: true,
    });
}

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Profil Peran', href: '/profil-peran' },
        ],
    },
});
</script>

<template>
    <Head :title="judul" />

    <div class="flex flex-1 flex-col gap-6 p-4 md:p-6">
        <header>
            <p class="text-sm text-muted-foreground">Pengaturan profil</p>
            <h1 class="mt-1 text-2xl font-semibold">{{ judul }}</h1>
            <p class="mt-1 text-sm text-muted-foreground">
                Perbarui informasi organisasi atau profil peran yang terhubung ke akun Anda.
            </p>
        </header>

        <form @submit.prevent="simpan" class="max-w-3xl space-y-5 rounded-2xl border border-sidebar-border/70 bg-card p-5 dark:border-sidebar-border">
            <div v-for="field in fields" :key="field.key" class="space-y-2">
                <label :for="field.key" class="text-sm font-medium">
                    {{ field.label }}
                    <span v-if="field.required" class="text-destructive">*</span>
                </label>

                <textarea
                    v-if="field.type === 'textarea'"
                    :id="field.key"
                    v-model="form[field.key]"
                    rows="3"
                    class="w-full rounded-lg border border-input bg-background px-3 py-2 text-sm outline-none transition focus-visible:ring-2 focus-visible:ring-ring"
                    :aria-invalid="Boolean(form.errors[field.key])"
                />
                <input
                    v-else
                    :id="field.key"
                    v-model="form[field.key]"
                    :type="field.type"
                    :min="field.type === 'number' ? 1 : undefined"
                    class="w-full rounded-lg border border-input bg-background px-3 py-2 text-sm outline-none transition focus-visible:ring-2 focus-visible:ring-ring"
                    :aria-invalid="Boolean(form.errors[field.key])"
                />
                <InputError :message="form.errors[field.key]" />
            </div>

            <div class="flex flex-col-reverse gap-2 border-t pt-4 sm:flex-row sm:justify-end">
                <button
                    type="submit"
                    :disabled="form.processing"
                    class="inline-flex items-center justify-center rounded-lg bg-primary px-5 py-2.5 text-sm font-medium text-primary-foreground hover:opacity-90 disabled:cursor-not-allowed disabled:opacity-50"
                >
                    {{ form.processing ? 'Menyimpan...' : 'Simpan perubahan' }}
                </button>
            </div>
        </form>
    </div>
</template>
