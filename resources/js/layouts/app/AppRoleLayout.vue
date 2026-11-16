<script setup lang="ts">
import { Menu } from '@lucide/vue';
import { ref } from 'vue';
import RoleSidebar from '@/components/layout/RoleSidebar.vue';
import { Toaster } from '@/components/ui/sonner';
import type { BreadcrumbItem } from '@/types';

// Kerangka halaman Admin, EO, dan mitra: sidebar kiri + konten.
// Prop breadcrumbs dipertahankan agar halaman lama tetap kompatibel.
withDefaults(defineProps<{ breadcrumbs?: BreadcrumbItem[] }>(), {
    breadcrumbs: () => [],
});

const menuMobile = ref(false);
</script>

<template>
    <div class="min-h-svh bg-[#F3F4F1] md:flex dark:bg-background">
        <!-- sidebar desktop -->
        <div class="hidden md:block md:w-64 md:shrink-0">
            <div class="sticky top-0 h-svh">
                <RoleSidebar />
            </div>
        </div>

        <!-- sidebar mobile -->
        <div v-if="menuMobile" class="fixed inset-0 z-40 md:hidden">
            <div
                class="absolute inset-0 bg-black/40"
                @click="menuMobile = false"
            />
            <div class="absolute inset-y-0 left-0 w-64">
                <RoleSidebar @navigasi="menuMobile = false" />
            </div>
        </div>

        <div class="flex min-w-0 flex-1 flex-col">
            <header
                class="flex h-14 items-center gap-3 border-b bg-white px-4 md:hidden dark:bg-card"
            >
                <button
                    type="button"
                    aria-label="Buka menu"
                    @click="menuMobile = true"
                >
                    <Menu class="size-5" />
                </button>
                <span class="font-semibold">Lokamitra</span>
            </header>

            <main class="min-w-0 flex-1">
                <slot />
            </main>
        </div>

        <Toaster />
    </div>
</template>
