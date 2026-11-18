<script setup lang="ts">
import { Link, usePage } from "@inertiajs/vue3";
import { CalendarDays, LogIn } from "@lucide/vue";
import { computed } from "vue";
import LokamitraLogo from "@/components/LokamitraLogo.vue";
import { Toaster } from "@/components/ui/sonner";
import { login } from "@/routes";
import type { BreadcrumbItem } from "@/types";

// Kerangka halaman publik: header tanpa login, pengunjung bisa melihat event.
withDefaults(defineProps<{ breadcrumbs?: BreadcrumbItem[] }>(), {
    breadcrumbs: () => [],
});

const page = usePage();
const user = computed(() => page.props.auth.user);
</script>

<template>
    <div class="min-h-svh bg-[#F6F4F2]">
        <header
            class="sticky top-0 z-40 border-b border-[#E5E2DF] bg-[#F6F4F2]/95 backdrop-blur"
        >
            <div
                class="flex h-16 w-full items-center justify-between px-6 md:px-10"
            >
                <Link href="/" class="flex items-center">
                    <LokamitraLogo />
                </Link>

                <nav class="flex items-center gap-4">
                    <Link
                        href="/events"
                        class="flex items-center gap-1.5 text-sm font-semibold text-[#0D443D] transition-colors hover:text-[#216B60]"
                    >
                        <CalendarDays class="size-4" /> Event
                    </Link>

                    <Link
                        v-if="user"
                        href="/pengunjung/dashboard"
                        class="rounded-full bg-[#216B60] px-5 py-2 text-sm font-semibold text-white transition-colors hover:bg-[#18584F]"
                    >
                        Dashboard
                    </Link>
                    <Link
                        v-else
                        :href="login()"
                        class="flex items-center gap-1.5 rounded-full bg-[#216B60] px-5 py-2 text-sm font-semibold text-white transition-colors hover:bg-[#18584F]"
                        data-test="tombol-masuk"
                    >
                        <LogIn class="size-4" /> Masuk
                    </Link>
                </nav>
            </div>
        </header>

        <main>
            <slot />
        </main>

        <footer class="border-t border-[#E5E2DF] py-6">
            <div
                class="w-full px-6 text-center text-xs text-neutral-500 md:px-10"
            >
                &copy; {{ new Date().getFullYear() }} Lokamitra &middot;
                Platform event kota
            </div>
        </footer>

        <Toaster />
    </div>
</template>
