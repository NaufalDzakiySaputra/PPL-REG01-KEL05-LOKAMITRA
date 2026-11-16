<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import PlaceholderPattern from '@/components/PlaceholderPattern.vue';
import { dashboard } from '@/routes';

defineProps<{
    roleLabel?: string;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Dashboard',
                href: dashboard(),
            },
        ],
    },
});
</script>

<template>
    <Head :title="roleLabel ? `Dashboard ${roleLabel}` : 'Dashboard'" />

    <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
        <header v-if="roleLabel" class="rounded-xl border border-sidebar-border/70 p-5 dark:border-sidebar-border">
            <p class="text-sm text-muted-foreground">Lokamitra</p>
            <h1 class="mt-1 text-2xl font-semibold">Dashboard {{ roleLabel }}</h1>
            <p class="mt-1 text-sm text-muted-foreground">
                Selamat datang, {{ $page.props.auth.user?.name }}.
            </p>
        </header>

        <div class="grid auto-rows-min gap-4 md:grid-cols-3">
            <div class="relative aspect-video overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border">
                <PlaceholderPattern />
            </div>
            <div class="relative aspect-video overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border">
                <PlaceholderPattern />
            </div>
            <div class="relative aspect-video overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border">
                <PlaceholderPattern />
            </div>
        </div>
        <div class="relative min-h-[100vh] flex-1 rounded-xl border border-sidebar-border/70 md:min-h-min dark:border-sidebar-border">
            <PlaceholderPattern />
        </div>
    </div>
</template>
