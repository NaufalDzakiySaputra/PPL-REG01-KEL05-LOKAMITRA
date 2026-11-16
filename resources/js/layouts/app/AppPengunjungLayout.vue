<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import LokamitraLogo from '@/components/LokamitraLogo.vue';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Toaster } from '@/components/ui/sonner';
import UserMenuContent from '@/components/UserMenuContent.vue';
import { useInitials } from '@/composables/useInitials';

// Kerangka beranda Pengunjung (Figma): bilah atas dengan logo, tanpa sidebar.
const page = usePage();
const user = computed(() => page.props.auth.user);
const { getInitials } = useInitials();
</script>

<template>
    <div class="min-h-svh bg-[#F4F2EF] dark:bg-background">
        <header
            class="flex h-16 items-center justify-between bg-[#F4F2EF] px-6 md:px-10 dark:bg-background"
        >
            <Link href="/pengunjung/dashboard">
                <LokamitraLogo />
            </Link>

            <DropdownMenu v-if="user">
                <DropdownMenuTrigger as-child>
                    <button
                        type="button"
                        class="flex size-9 items-center justify-center rounded-full bg-[#F2B632] text-xs font-bold text-[#0F3D3A]"
                        data-test="sidebar-menu-button"
                    >
                        {{ getInitials(user.name) }}
                    </button>
                </DropdownMenuTrigger>
                <DropdownMenuContent
                    class="min-w-56 rounded-lg"
                    align="end"
                    :side-offset="8"
                >
                    <UserMenuContent :user="user" />
                </DropdownMenuContent>
            </DropdownMenu>
        </header>

        <main>
            <slot />
        </main>

        <Toaster />
    </div>
</template>
