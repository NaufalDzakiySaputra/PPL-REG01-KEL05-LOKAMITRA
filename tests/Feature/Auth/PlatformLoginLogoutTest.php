<?php

namespace Tests\Feature\Auth;

use App\Models\Eo;
use App\Models\User;
use Database\Seeders\AdminSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * PBI-003 s/d PBI-006: login & logout Pengelola Platform dan Event Organizer.
 */
class PlatformLoginLogoutTest extends TestCase
{
    use RefreshDatabase;

    // ---------------------------------------------------------------- PBI-003

    public function test_seeded_platform_admin_can_log_in_and_is_sent_to_admin_dashboard()
    {
        $this->seed(AdminSeeder::class);

        $response = $this->post(route('login.store'), [
            'email' => 'admin@lokamitra.test',
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('admin.dashboard'));
    }

    public function test_admin_dashboard_can_be_rendered_for_admin()
    {
        $this->actingAs(User::factory()->admin()->create())
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('admin/Dashboard'));
    }

    public function test_admin_with_wrong_password_sees_error_and_cannot_open_dashboard()
    {
        $admin = User::factory()->admin()->create();

        $this->from(route('login'))->post(route('login.store'), [
            'email' => $admin->email,
            'password' => 'salah-total',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
        $this->get(route('admin.dashboard'))->assertRedirect(route('login'));
    }

    public function test_unknown_email_cannot_log_in()
    {
        $this->post(route('login.store'), [
            'email' => 'tidak-ada@lokamitra.test',
            'password' => 'password',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    // ---------------------------------------------------------------- PBI-004

    public function test_admin_logout_ends_session_and_admin_pages_need_login_again()
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin)->get(route('admin.dashboard'))->assertOk();

        $this->post(route('logout'))->assertRedirect(route('login'));

        $this->assertGuest();
        $this->get(route('admin.dashboard'))->assertRedirect(route('login'));
    }

    public function test_protected_pages_are_not_cacheable_by_the_browser()
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->get(route('admin.dashboard'));

        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
    }

    // ------------------------------------------------------- PBI-005 (login EO)

    public function test_verified_eo_can_log_in_and_is_sent_to_eo_dashboard()
    {
        $akun = User::factory()->eo()->create();
        Eo::create([
            'akun_id' => $akun->id,
            'nama_eo_organisasi' => 'Contoh EO',
            'status_verifikasi' => Eo::STATUS_DISETUJUI,
        ]);

        $response = $this->post(route('login.store'), [
            'email' => $akun->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('eo.dashboard'));
    }

    public function test_eo_dashboard_can_be_rendered_for_eo()
    {
        $this->actingAs(User::factory()->eo()->create())
            ->get(route('eo.dashboard'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('eo/Dashboard'));
    }

    public function test_eo_waiting_for_verification_cannot_log_in()
    {
        $akun = User::factory()->eo()->menunggu()->create();

        $this->from(route('login'))->post(route('login.store'), [
            'email' => $akun->email,
            'password' => 'password',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_rejected_eo_cannot_log_in()
    {
        $akun = User::factory()->eo()->create(['status_akun' => User::STATUS_DITOLAK]);

        $this->post(route('login.store'), [
            'email' => $akun->email,
            'password' => 'password',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    // ------------------------------------------------------ PBI-006 (logout EO)

    public function test_eo_logout_ends_session()
    {
        $akun = User::factory()->eo()->create();

        $this->actingAs($akun)->post(route('logout'))->assertRedirect(route('login'));

        $this->assertGuest();
        $this->get(route('eo.dashboard'))->assertRedirect(route('login'));
    }

    // ------------------------------------------------------- Hak akses peran

    public function test_eo_cannot_open_admin_dashboard()
    {
        $this->actingAs(User::factory()->eo()->create())
            ->get(route('admin.dashboard'))
            ->assertForbidden();
    }

    public function test_admin_cannot_open_eo_dashboard()
    {
        $this->actingAs(User::factory()->admin()->create())
            ->get(route('eo.dashboard'))
            ->assertForbidden();
    }

    public function test_dashboard_route_redirects_each_role_to_its_own_dashboard()
    {
        $this->actingAs(User::factory()->admin()->create())
            ->get(route('dashboard'))
            ->assertRedirect(route('admin.dashboard'));

        $this->actingAs(User::factory()->eo()->create())
            ->get(route('dashboard'))
            ->assertRedirect(route('eo.dashboard'));
    }
}
