<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { onMounted, reactive, ref } from 'vue';
import { toast } from 'vue-sonner';
import DeleteUser from '@/components/DeleteUser.vue';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { ApiError, api } from '@/lib/api';
import { edit } from '@/routes/profile';
import type { ApiResponse } from '@/types/api';
import type { User } from '@/types/auth';

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Profile settings',
                href: edit(),
            },
        ],
    },
});

const form = reactive({
    name: '',
    email: '',
});

const errors = ref<Record<string, string>>({});
const loading = ref(true);
const processing = ref(false);

async function muatProfil(): Promise<void> {
    try {
        const response = await api.get<ApiResponse<User>>('/api/v1/me');

        form.name = response.data.name;
        form.email = response.data.email;
    } catch (error) {
        if (error instanceof ApiError) {
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
        const response = await api.patch<ApiResponse<User>>('/api/v1/me', {
            name: form.name,
            email: form.email,
        });

        toast.success(response.message ?? 'Profil berhasil diperbarui.');

        // Sinkronkan data user yang dibagikan Inertia (nama di header, dll.).
        router.reload({ only: ['auth'] });
    } catch (error) {
        if (error instanceof ApiError) {
            errors.value = error.fieldErrors;
            toast.error(error.message);
        }
    } finally {
        processing.value = false;
    }
}

onMounted(() => {
    void muatProfil();
});
</script>

<template>
    <Head title="Profile settings" />

    <h1 class="sr-only">Profile settings</h1>

    <div class="flex flex-col space-y-6">
        <Heading
            variant="small"
            title="Profile"
            description="Update your name and email address"
        />

        <form class="space-y-6" @submit.prevent="simpan">
            <div class="grid gap-2">
                <Label for="name">Name</Label>
                <Input
                    id="name"
                    v-model="form.name"
                    class="mt-1 block w-full"
                    required
                    autocomplete="name"
                    placeholder="Full name"
                    :disabled="loading || processing"
                />
                <InputError class="mt-2" :message="errors.name" />
            </div>

            <div class="grid gap-2">
                <Label for="email">Email address</Label>
                <Input
                    id="email"
                    v-model="form.email"
                    type="email"
                    class="mt-1 block w-full"
                    required
                    autocomplete="username"
                    placeholder="Email address"
                    :disabled="loading || processing"
                />
                <InputError class="mt-2" :message="errors.email" />
            </div>

            <div class="flex items-center gap-4">
                <Button
                    type="submit"
                    :disabled="loading || processing"
                    data-test="update-profile-button"
                >
                    {{ processing ? 'Saving...' : 'Save' }}
                </Button>
            </div>
        </form>
    </div>

    <DeleteUser />
</template>
