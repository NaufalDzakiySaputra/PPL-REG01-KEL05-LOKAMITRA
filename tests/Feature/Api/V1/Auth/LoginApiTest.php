<?php

namespace Tests\Feature\Api\V1\Auth;

use App\Models\User;
use Database\Seeders\AkunContohSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoginApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_login_with_valid_credentials(): void
    {
        $user = User::factory()->vendor()->create();

        $this->postJson('/api/v1/login', [
            'email' => $user->email,
            'password' => 'password',
        ])
            ->assertOk()
            ->assertJsonPath('message', 'Login berhasil.')
            ->assertJsonPath('data.id', $user->id)
            ->assertJsonPath('data.name', $user->name)
            ->assertJsonPath('data.email', $user->email)
            ->assertJsonPath('data.role', User::ROLE_VENDOR)
            ->assertJsonPath('data.status_verifikasi', 'verified')
            ->assertJsonPath('redirect', route('vendor.dashboard'));

        $this->assertAuthenticatedAs($user);
    }

    public function test_remember_flag_is_accepted(): void
    {
        $user = User::factory()->pengunjung()->create();

        $this->postJson('/api/v1/login', [
            'email' => $user->email,
            'password' => 'password',
            'remember' => true,
        ])->assertOk();

        $this->assertAuthenticatedAs($user);
        $this->assertNotNull($user->fresh()->remember_token);
    }

    public function test_user_cannot_login_with_invalid_password(): void
    {
        $user = User::factory()->vendor()->create();

        $this->postJson('/api/v1/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('email');

        $this->assertGuest();
    }

    public function test_unknown_email_cannot_login(): void
    {
        $this->postJson('/api/v1/login', [
            'email' => 'tidak-ada@lokamitra.test',
            'password' => 'password',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('email');

        $this->assertGuest();
    }

    public function test_account_waiting_for_verification_cannot_login(): void
    {
        $user = User::factory()->eo()->menunggu()->create();

        $this->postJson('/api/v1/login', [
            'email' => $user->email,
            'password' => 'password',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('email');

        $this->assertGuest();
    }

    public function test_rejected_account_cannot_login(): void
    {
        $user = User::factory()->eo()->ditolak()->create();

        $this->postJson('/api/v1/login', [
            'email' => $user->email,
            'password' => 'password',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('email');

        $this->assertGuest();
    }

    public function test_login_is_rate_limited(): void
    {
        $user = User::factory()->vendor()->create();

        $data = [
            'email' => $user->email,
            'password' => 'wrong-password',
        ];

        // Lima percobaan pertama masih diproses sebagai validasi login.
        for ($percobaan = 1; $percobaan <= 5; $percobaan++) {
            $this->postJson('/api/v1/login', $data)->assertUnprocessable();
        }

        // Percobaan keenam harus terkena pembatasan.
        $this->postJson('/api/v1/login', $data)->assertTooManyRequests();
    }

    public function test_each_seeded_role_is_redirected_to_its_dashboard(): void
    {
        $this->seed(AkunContohSeeder::class);

        $akunDanRoute = [
            'vendor@lokamitra.test' => 'vendor.dashboard',
            'pemda@lokamitra.test' => 'pemda.dashboard',
            'komunitas@lokamitra.test' => 'komunitas.dashboard',
            'akademisi@lokamitra.test' => 'akademisi.dashboard',
            'media@lokamitra.test' => 'media.dashboard',
            'pengunjung@lokamitra.test' => 'pengunjung.dashboard',
        ];

        foreach ($akunDanRoute as $email => $routeName) {
            $this->postJson('/api/v1/login', [
                'email' => $email,
                'password' => 'password',
            ])
                ->assertOk()
                ->assertJsonPath('redirect', route($routeName));

            $this->assertAuthenticated();

            $this->postJson('/api/v1/logout')->assertOk();
            $this->assertGuest();
        }
    }
}
