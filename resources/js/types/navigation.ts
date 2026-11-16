import type { InertiaLinkProps } from '@inertiajs/vue3';
import type { LucideIcon } from '@lucide/vue';

export type BreadcrumbItem = {
    title: string;
    href: NonNullable<InertiaLinkProps['href']>;
};

export type NavItem = {
    title: string;
    href: NonNullable<InertiaLinkProps['href']>;
    icon?: LucideIcon;
    isActive?: boolean;
};

// Menu sidebar per role (dikirim server lewat shared prop 'menu').
export type MenuItemPeran = {
    label: string;
    ikon: string;
    href: string | null;
};

export type MenuPeran = {
    panel: string | null;
    tema: 'terang' | 'gelap';
    peranLabel: string;
    items: MenuItemPeran[];
};
