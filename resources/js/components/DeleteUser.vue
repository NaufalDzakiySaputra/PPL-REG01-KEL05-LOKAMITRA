<script setup lang="ts">
import { ref } from 'vue';
import { toast } from 'vue-sonner';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import { ApiError, api } from '@/lib/api';

const password = ref('');
const errors = ref<Record<string, string>>({});
const processing = ref(false);

async function hapusAkun(): Promise<void> {
    processing.value = true;
    errors.value = {};

    try {
        await api.delete('/api/v1/me', { password: password.value });

        toast.success('Akun berhasil dihapus.');
        window.location.assign('/');
    } catch (error) {
        if (error instanceof ApiError) {
            errors.value = error.fieldErrors;
            toast.error(error.message);
        }
    } finally {
        processing.value = false;
    }
}

function resetForm(): void {
    password.value = '';
    errors.value = {};
}
</script>

<template>
    <div class="space-y-6">
        <Heading
            variant="small"
            title="Delete account"
            description="Delete your account and all of its resources"
        />
        <div
            class="space-y-4 rounded-lg border border-red-100 bg-red-50 p-4 dark:border-red-200/10 dark:bg-red-700/10"
        >
            <div class="relative space-y-0.5 text-red-600 dark:text-red-100">
                <p class="font-medium">Warning</p>
                <p class="text-sm">
                    Please proceed with caution, this cannot be undone.
                </p>
            </div>
            <Dialog>
                <DialogTrigger as-child>
                    <Button
                        variant="destructive"
                        data-test="delete-user-button"
                    >
                        Delete account
                    </Button>
                </DialogTrigger>
                <DialogContent>
                    <form class="space-y-6" @submit.prevent="hapusAkun">
                        <DialogHeader class="space-y-3">
                            <DialogTitle>
                                Are you sure you want to delete your account?
                            </DialogTitle>
                            <DialogDescription>
                                Once your account is deleted, all of its
                                resources and data will also be permanently
                                deleted. Please enter your password to confirm
                                you would like to permanently delete your
                                account.
                            </DialogDescription>
                        </DialogHeader>

                        <div class="grid gap-2">
                            <Label for="password" class="sr-only">
                                Password
                            </Label>
                            <PasswordInput
                                id="password"
                                v-model="password"
                                name="password"
                                placeholder="Password"
                                :disabled="processing"
                            />
                            <InputError :message="errors.password" />
                        </div>

                        <DialogFooter class="gap-2">
                            <DialogClose as-child>
                                <Button
                                    type="button"
                                    variant="secondary"
                                    @click="resetForm"
                                >
                                    Cancel
                                </Button>
                            </DialogClose>

                            <Button
                                type="submit"
                                variant="destructive"
                                :disabled="processing"
                                data-test="confirm-delete-user-button"
                            >
                                {{
                                    processing
                                        ? 'Deleting...'
                                        : 'Delete account'
                                }}
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>
        </div>
    </div>
</template>
