<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { reactive, ref } from 'vue';
import { toast } from 'vue-sonner';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { ApiError, api } from '@/lib/api';
import { edit } from '@/routes/security';

// oxfmt-ignore
type Props = {
    passwordRules: string;
};

const props = defineProps<Props>();

const form = reactive({
    current_password: '',
    password: '',
    password_confirmation: '',
});

const errors = ref<Record<string, string>>({});
const processing = ref(false);

async function simpan(): Promise<void> {
    processing.value = true;
    errors.value = {};

    try {
        const response = await api.put<{ message: string }>(
            '/api/v1/me/password',
            { ...form },
        );

        form.current_password = '';
        form.password = '';
        form.password_confirmation = '';

        toast.success(response.message ?? 'Password berhasil diperbarui.');
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
        breadcrumbs: [
            {
                title: 'Security settings',
                href: edit(),
            },
        ],
    },
});
</script>

<template>
    <Head title="Security settings" />

    <h1 class="sr-only">Security settings</h1>

    <div class="space-y-6">
        <Heading
            variant="small"
            title="Update password"
            description="Ensure your account is using a long, random password to stay secure"
        />

        <form class="space-y-6" @submit.prevent="simpan">
            <div class="grid gap-2">
                <Label for="current_password">Current password</Label>
                <PasswordInput
                    id="current_password"
                    v-model="form.current_password"
                    class="mt-1 block w-full"
                    autocomplete="current-password"
                    placeholder="Current password"
                    :disabled="processing"
                />
                <InputError :message="errors.current_password" />
            </div>

            <div class="grid gap-2">
                <Label for="password">New password</Label>
                <PasswordInput
                    id="password"
                    v-model="form.password"
                    class="mt-1 block w-full"
                    autocomplete="new-password"
                    placeholder="New password"
                    :passwordrules="props.passwordRules"
                    :disabled="processing"
                />
                <InputError :message="errors.password" />
            </div>

            <div class="grid gap-2">
                <Label for="password_confirmation">Confirm password</Label>
                <PasswordInput
                    id="password_confirmation"
                    v-model="form.password_confirmation"
                    class="mt-1 block w-full"
                    autocomplete="new-password"
                    placeholder="Confirm password"
                    :passwordrules="props.passwordRules"
                    :disabled="processing"
                />
                <InputError :message="errors.password_confirmation" />
            </div>

            <div class="flex items-center gap-4">
                <Button
                    type="submit"
                    :disabled="processing"
                    data-test="update-password-button"
                >
                    {{ processing ? 'Saving...' : 'Save' }}
                </Button>
            </div>
        </form>
    </div>
</template>
