<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { reactive, ref } from 'vue';
import InputError from '@/components/InputError.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { ApiError, api } from '@/lib/api';

defineOptions({
    layout: {
        title: 'Reset password',
        description: 'Please enter your new password below',
    },
});

const props = defineProps<{
    token: string;
    email: string;
    passwordRules: string;
}>();

const form = reactive({
    password: '',
    password_confirmation: '',
});

const inputEmail = ref(props.email);
const errors = ref<Record<string, string>>({});
const pesanError = ref('');
const processing = ref(false);

async function kirim(): Promise<void> {
    processing.value = true;
    errors.value = {};
    pesanError.value = '';

    try {
        await api.post<{ message: string }>('/api/v1/reset-password', {
            token: props.token,
            email: inputEmail.value,
            ...form,
        });

        // Halaman login menampilkan pesan sukses dari sesi.
        window.location.assign('/login');
    } catch (error) {
        if (error instanceof ApiError) {
            errors.value = error.fieldErrors;
            pesanError.value =
                Object.keys(error.fieldErrors).length > 0 ? '' : error.message;
        } else {
            pesanError.value =
                'Tidak dapat terhubung ke server. Silakan coba lagi.';
        }
    } finally {
        processing.value = false;
    }
}
</script>

<template>
    <Head title="Reset password" />

    <div
        v-if="pesanError"
        class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700"
    >
        {{ pesanError }}
    </div>

    <form @submit.prevent="kirim">
        <div class="grid gap-6">
            <div class="grid gap-2">
                <Label for="email">Email</Label>
                <Input
                    id="email"
                    type="email"
                    name="email"
                    autocomplete="email"
                    v-model="inputEmail"
                    class="mt-1 block w-full"
                    readonly
                />
                <InputError :message="errors.email" class="mt-2" />
            </div>

            <div class="grid gap-2">
                <Label for="password">Password</Label>
                <PasswordInput
                    id="password"
                    name="password"
                    v-model="form.password"
                    autocomplete="new-password"
                    class="mt-1 block w-full"
                    autofocus
                    placeholder="Password"
                    :passwordrules="passwordRules"
                    :disabled="processing"
                />
                <InputError :message="errors.password" />
            </div>

            <div class="grid gap-2">
                <Label for="password_confirmation"> Confirm password </Label>
                <PasswordInput
                    id="password_confirmation"
                    name="password_confirmation"
                    v-model="form.password_confirmation"
                    autocomplete="new-password"
                    class="mt-1 block w-full"
                    placeholder="Confirm password"
                    :passwordrules="passwordRules"
                    :disabled="processing"
                />
                <InputError :message="errors.password_confirmation" />
            </div>

            <Button
                type="submit"
                class="mt-4 w-full"
                :disabled="processing"
                data-test="reset-password-button"
            >
                <Spinner v-if="processing" />
                Reset password
            </Button>
        </div>
    </form>
</template>
