<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import AppPengunjungLayout from '@/layouts/app/AppPengunjungLayout.vue';
import AppRoleLayout from '@/layouts/app/AppRoleLayout.vue';
import type { BreadcrumbItem } from '@/types';

const { breadcrumbs = [] } = defineProps<{
    breadcrumbs?: BreadcrumbItem[];
}>();

// Pengunjung memakai bilah atas; role lain memakai sidebar sesuai Figma.
const page = usePage();
const adalahPengunjung = computed(
    () => page.props.auth.user?.role === 'pengunjung',
);
</script>

<template>
    <AppPengunjungLayout v-if="adalahPengunjung">
        <slot />
    </AppPengunjungLayout>
    <AppRoleLayout v-else :breadcrumbs="breadcrumbs">
        <slot />
    </AppRoleLayout>
</template>
