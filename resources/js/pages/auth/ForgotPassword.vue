<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { reactive, ref } from 'vue';
import InputError from '@/components/InputError.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { ApiError, api } from '@/lib/api';
import { login } from '@/routes';

defineOptions({
    layout: {
        title: 'Forgot password',
        description: 'Enter your email to receive a password reset link',
    },
});

const props = defineProps<{
    status?: string;
}>();

const form = reactive({
    email: '',
});

const errors = ref<Record<string, string>>({});
const pesanSukses = ref(props.status ?? '');
const pesanError = ref('');
const processing = ref(false);

async function kirim(): Promise<void> {
    processing.value = true;
    errors.value = {};
    pesanSukses.value = '';
    pesanError.value = '';

    try {
        const response = await api.post<{ message: string }>(
            '/api/v1/forgot-password',
            { ...form },
        );

        pesanSukses.value = response.message;
        form.email = '';
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
    <Head title="Forgot password" />

    <div
        v-if="pesanSukses"
        class="mb-4 text-center text-sm font-medium text-green-600"
    >
        {{ pesanSukses }}
    </div>

    <div
        v-if="pesanError"
        class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700"
    >
        {{ pesanError }}
    </div>

    <div class="space-y-6">
        <form class="space-y-6" @submit.prevent="kirim">
            <div class="grid gap-2">
                <Label for="email">Email address</Label>
                <Input
                    id="email"
                    v-model="form.email"
                    type="email"
                    name="email"
                    autocomplete="off"
                    v-focus
                    placeholder="email@example.com"
                    :disabled="processing"
                />
                <InputError :message="errors.email" />
            </div>

            <div class="my-6 flex items-center justify-start">
                <Button
                    type="submit"
                    class="w-full"
                    :disabled="processing"
                    data-test="email-password-reset-link-button"
                >
                    <Spinner v-if="processing" />
                    Email password reset link
                </Button>
            </div>
        </form>

        <div class="space-x-1 text-center text-sm text-muted-foreground">
            <span>Or, return to</span>
            <TextLink :href="login()">log in</TextLink>
        </div>
    </div>
</template>
