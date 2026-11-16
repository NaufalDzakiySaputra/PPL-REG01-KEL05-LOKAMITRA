<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { ChevronsUpDown } from '@lucide/vue';
import { computed } from 'vue';
import LokamitraLogo from '@/components/LokamitraLogo.vue';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import UserMenuContent from '@/components/UserMenuContent.vue';
import { useCurrentUrl } from '@/composables/useCurrentUrl';
import { useInitials } from '@/composables/useInitials';
import { menuIcons } from '@/lib/menuIcons';

// Sidebar sesuai Figma: Admin = terang + "PANEL ADMIN", role lain = hijau gelap.
// Varian terang (Admin) kini punya pasangan dark: agar ikut mode gelap.
const emit = defineEmits<{ navigasi: [] }>();

const page = usePage();
const user = computed(() => page.props.auth.user);
const menu = computed(() => page.props.menu);
const gelap = computed(() => menu.value?.tema === 'gelap');

const { isCurrentOrParentUrl } = useCurrentUrl();
const { getInitials } = useInitials();

const kelas = computed(() =>
    gelap.value
        ? {
              panel: 'bg-[#0F3D3A] text-white/80',
              aktif: 'bg-[#1B5550] text-white font-semibold',
              biasa: 'hover:bg-white/10 hover:text-white',
              kartu: 'bg-white/10 text-white hover:bg-white/15',
              nonaktif: 'text-white/40',
          }
        : {
              panel: 'bg-white text-neutral-700 border-r border-neutral-200 dark:border-sidebar-border dark:bg-card dark:text-neutral-200',
              aktif: 'bg-[#E3EFE3] text-[#245C3B] font-semibold dark:bg-emerald-500/15 dark:text-emerald-300',
              biasa: 'hover:bg-neutral-100 dark:hover:bg-white/10',
              kartu: 'bg-[#F3F4F1] text-neutral-900 hover:bg-neutral-200/60 dark:bg-white/10 dark:text-white dark:hover:bg-white/15',
              nonaktif: 'text-neutral-400 dark:text-neutral-500',
          },
);
</script>

<template>
    <aside :class="['flex h-full flex-col px-3 py-4', kelas.panel]">
        <!-- logo -->
        <div class="mb-6 px-2">
            <template v-if="gelap">
                <div class="flex items-center gap-2">
                    <span
                        class="flex size-8 items-center justify-center rounded-lg bg-[#F2B632] text-lg font-bold text-[#0F3D3A]"
                    >
                        L
                    </span>
                    <span class="text-lg font-bold text-white">Lokamitra</span>
                </div>
            </template>
            <template v-else>
                <LokamitraLogo />
                <p
                    v-if="menu?.panel"
                    class="mt-1 text-[10px] font-bold tracking-wide text-[#245C3B] dark:text-emerald-300"
                >
                    {{ menu.panel }}
                </p>
            </template>
        </div>

        <!-- menu -->
        <nav class="flex-1 space-y-1 overflow-y-auto">
            <template v-for="item in menu?.items ?? []" :key="item.label">
                <Link
                    v-if="item.href"
                    :href="item.href"
                    :class="[
                        'flex items-center gap-3 rounded-lg px-3 py-2 text-sm transition-colors',
                        isCurrentOrParentUrl(item.href)
                            ? kelas.aktif
                            : kelas.biasa,
                    ]"
                    @click="emit('navigasi')"
                >
                    <component
                        :is="menuIcons[item.ikon]"
                        class="size-4 shrink-0"
                    />
                    {{ item.label }}
                </Link>
                <span
                    v-else
                    :class="[
                        'flex cursor-not-allowed items-center gap-3 rounded-lg px-3 py-2 text-sm',
                        kelas.nonaktif,
                    ]"
                    title="Segera tersedia"
                >
                    <component
                        :is="menuIcons[item.ikon]"
                        class="size-4 shrink-0"
                    />
                    {{ item.label }}
                </span>
            </template>
        </nav>

        <!-- kartu pengguna -->
        <DropdownMenu v-if="user">
            <DropdownMenuTrigger as-child>
                <button
                    type="button"
                    :class="[
                        'mt-4 flex w-full items-center gap-3 rounded-xl px-3 py-2.5 text-left transition-colors',
                        kelas.kartu,
                    ]"
                    data-test="sidebar-menu-button"
                >
                    <span
                        class="flex size-8 shrink-0 items-center justify-center rounded-full bg-[#F2B632] text-xs font-bold text-[#0F3D3A]"
                    >
                        {{ getInitials(user.name) }}
                    </span>
                    <span class="min-w-0 flex-1 leading-tight">
                        <span class="block truncate text-sm font-semibold">
                            {{ user.name }}
                        </span>
                        <span class="block truncate text-xs opacity-70">
                            {{ menu?.peranLabel }}
                        </span>
                    </span>
                    <ChevronsUpDown class="size-4 shrink-0 opacity-60" />
                </button>
            </DropdownMenuTrigger>
            <DropdownMenuContent
                class="min-w-56 rounded-lg"
                side="top"
                align="start"
                :side-offset="8"
            >
                <UserMenuContent :user="user" />
            </DropdownMenuContent>
        </DropdownMenu>
    </aside>
</template>
