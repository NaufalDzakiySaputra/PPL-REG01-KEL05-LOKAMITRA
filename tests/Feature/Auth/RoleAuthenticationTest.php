<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Database\Seeders\AdminSeeder;
use Database\Seeders\AkunContohSeeder;
use Database\Seeders\EoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_sample_accounts_of_all_eight_roles_can_log_in_to_their_own_dashboard_and_log_out(): void
    {
        $this->seed([
            AdminSeeder::class,
            EoSeeder::class,
            AkunContohSeeder::class,
        ]);

        $akunDanRoute = [
            'admin@lokamitra.test' => 'admin.dashboard',
            'eo@lokamitra.test' => 'eo.dashboard',
            'vendor@lokamitra.test' => 'vendor.dashboard',
            'pemda@lokamitra.test' => 'pemda.dashboard',
            'komunitas@lokamitra.test' => 'komunitas.dashboard',
            'akademisi@lokamitra.test' => 'akademisi.dashboard',
            'media@lokamitra.test' => 'media.dashboard',
            'pengunjung@lokamitra.test' => 'pengunjung.dashboard',
        ];

        foreach ($akunDanRoute as $email => $routeName) {
            $response = $this->postJson('/api/v1/login', [
                'email' => $email,
                'password' => 'password',
            ]);

            $response->assertOk()->assertJsonPath('redirect', route($routeName));
            $this->assertAuthenticated();

            $this->postJson('/api/v1/logout')->assertOk();
            $this->assertGuest();
        }
    }

    public function test_waiting_account_cannot_log_in(): void
    {
        $user = User::factory()->vendor()->menunggu()->create();

        $this->postJson('/api/v1/login', [
            'email' => $user->email,
            'password' => 'password',
        ])->assertUnprocessable()->assertJsonValidationErrors('email');

        $this->assertGuest();
    }

    public function test_rejected_account_cannot_log_in(): void
    {
        $user = User::factory()->vendor()->ditolak()->create();

        $this->postJson('/api/v1/login', [
            'email' => $user->email,
            'password' => 'password',
        ])->assertUnprocessable()->assertJsonValidationErrors('email');

        $this->assertGuest();
    }

    public function test_non_active_account_with_existing_session_is_logged_out(): void
    {
        $user = User::factory()->vendor()->menunggu()->create();

        $this->actingAs($user)
            ->get(route('vendor.dashboard'))
            ->assertRedirect(route('login'));

        $this->assertGuest();
    }

    public function test_dashboard_route_redirects_all_roles_to_their_own_dashboard(): void
    {
        $roleDanRoute = [
            'admin' => 'admin.dashboard',
            'eo' => 'eo.dashboard',
            'vendor' => 'vendor.dashboard',
            'pemda' => 'pemda.dashboard',
            'komunitas' => 'komunitas.dashboard',
            'akademisi' => 'akademisi.dashboard',
            'media' => 'media.dashboard',
            'pengunjung' => 'pengunjung.dashboard',
        ];

        foreach ($roleDanRoute as $role => $routeName) {
            $user = User::factory()->{$role}()->create();

            $this->actingAs($user)
                ->get(route('dashboard'))
                ->assertRedirect(route($routeName));
        }
    }

    public function test_user_cannot_access_dashboard_belonging_to_another_role(): void
    {
        $roleDanRoute = [
            'admin' => 'admin.dashboard',
            'eo' => 'eo.dashboard',
            'vendor' => 'vendor.dashboard',
            'pemda' => 'pemda.dashboard',
            'komunitas' => 'komunitas.dashboard',
            'akademisi' => 'akademisi.dashboard',
            'media' => 'media.dashboard',
            'pengunjung' => 'pengunjung.dashboard',
        ];

        foreach ($roleDanRoute as $role => $routeName) {
            foreach ($roleDanRoute as $targetRole => $targetRoute) {
                if ($role === $targetRole) {
                    continue;
                }

                $user = User::factory()->{$role}()->create();

                $this->actingAs($user)
                    ->get(route($targetRoute))
                    ->assertForbidden();
            }
        }
    }

    public function test_protected_dashboard_response_is_not_cached(): void
    {
        $user = User::factory()->admin()->create();

        $response = $this->actingAs($user)->get(route('admin.dashboard'));

        $response->assertOk();
        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
    }
}
