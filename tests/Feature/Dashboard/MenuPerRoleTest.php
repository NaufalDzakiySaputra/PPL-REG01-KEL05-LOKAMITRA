<?php

namespace Tests\Feature\Dashboard;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class MenuPerRoleTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_mendapat_menu_admin_bertema_terang(): void
    {
        $this->actingAs(User::factory()->admin()->create())
            ->get(route('admin.dashboard'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('menu.panel', 'PANEL ADMIN')
                ->where('menu.tema', 'terang')
                ->has('menu.items', 6)
                ->where('menu.items.0.label', 'Dashboard Admin')
                ->where('menu.items.0.href', '/admin/dashboard')
                ->where('menu.items.1.href', '/admin/pengajuan'));
    }

    public function test_eo_mendapat_menu_modul_bertema_terang_seperti_admin(): void
    {
        $this->actingAs(User::factory()->eo()->create())
            ->get(route('eo.dashboard'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('menu.panel', 'PANEL EO')
                ->where('menu.tema', 'terang')
                ->where('menu.items.0.href', '/eo/dashboard')
                ->where('menu.items.1.label', 'Event')
                ->has('menu.items', 12)); // dashboard + 10 modul + profil peran
    }

    public function test_item_modul_bernilai_null_selama_route_belum_ada_dan_aktif_setelahnya(): void
    {
        $this->actingAs(User::factory()->eo()->create())
            ->get(route('eo.dashboard'))
            ->assertInertia(fn (Assert $page) => $page
                // null sebelum modul Event dibuat (Tahap 7), '/eo/events' sesudahnya
                ->where('menu.items.1.href', fn ($href) => $href === null || $href === '/eo/events'));
    }

    public function test_pengunjung_tidak_memakai_sidebar(): void
    {
        $this->actingAs(User::factory()->pengunjung()->create())
            ->get(route('pengunjung.dashboard'))
            ->assertInertia(fn (Assert $page) => $page->has('menu.items', 0));
    }
}
