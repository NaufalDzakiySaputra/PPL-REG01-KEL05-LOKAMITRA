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

// Sidebar terang mengikuti Panel Admin untuk semua peran.
// Isi menu tetap berbeda per peran, hanya gaya visualnya yang disamakan.
const emit = defineEmits<{ navigasi: [] }>();

const page = usePage();
const user = computed(() => page.props.auth.user);
const menu = computed(() => page.props.menu);

const { isCurrentOrParentUrl } = useCurrentUrl();
const { getInitials } = useInitials();
</script>

<template>
    <aside
        class="flex h-full flex-col border-r border-neutral-200 bg-white px-3 py-4 text-neutral-700 dark:border-sidebar-border dark:bg-card dark:text-neutral-200"
    >
        <!-- logo -->
        <div class="mb-6 px-2">
            <LokamitraLogo />
            <p
                v-if="menu?.panel"
                class="mt-1 text-[10px] font-bold tracking-wide text-[#245C3B] dark:text-emerald-300"
            >
                {{ menu.panel }}
            </p>
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
                            ? 'bg-[#E3EFE3] font-semibold text-[#245C3B] dark:bg-emerald-500/15 dark:text-emerald-300'
                            : 'hover:bg-neutral-100 dark:hover:bg-white/10',
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
                    class="flex cursor-not-allowed items-center gap-3 rounded-lg px-3 py-2 text-sm text-neutral-400 dark:text-neutral-500"
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
                    class="mt-4 flex w-full items-center gap-3 rounded-xl bg-[#F3F4F1] px-3 py-2.5 text-left text-neutral-900 transition-colors hover:bg-neutral-200/60 dark:bg-white/10 dark:text-white dark:hover:bg-white/15"
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
