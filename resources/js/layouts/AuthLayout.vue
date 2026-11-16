<script setup lang="ts">
import AuthSimpleLayout from '@/layouts/auth/AuthSimpleLayout.vue';

defineProps<{
    title?: string;
    description?: string;
    branded?: boolean;
}>();
</script>

<template>
    <div v-if="branded" class="min-h-svh w-full bg-[#F6F4F2]">
        <slot />
    </div>

    <AuthSimpleLayout v-else :title="title" :description="description">
        <slot />
    </AuthSimpleLayout>
</template>
