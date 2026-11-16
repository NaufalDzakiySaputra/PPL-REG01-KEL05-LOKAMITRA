import {
    Activity,
    CalendarDays,
    ChartColumn,
    Clock,
    FileText,
    LayoutDashboard,
    Megaphone,
    MessageCircle,
    MessageSquare,
    ShieldCheck,
    SquareCheckBig,
    UserRound,
    Users,
} from '@lucide/vue';
import type { LucideIcon } from '@lucide/vue';

// Nama ikon dari server (RoleMenuService) -> komponen ikon.
export const menuIcons: Record<string, LucideIcon> = {
    dashboard: LayoutDashboard,
    verifikasi: ShieldCheck,
    event: CalendarDays,
    stakeholder: Users,
    forum: MessageSquare,
    chat: MessageCircle,
    dokumen: FileText,
    tugas: SquareCheckBig,
    timeline: Clock,
    pengumuman: Megaphone,
    laporan: ChartColumn,
    riwayat: Activity,
    profil: UserRound,
};
