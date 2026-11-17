<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Database\Seeders\AkunContohSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_can_be_rendered(): void
    {
        $this->get(route('login'))->assertOk();
    }

    public function test_users_can_authenticate_using_the_login_screen(): void
    {
        $user = User::factory()->vendor()->create();

        $response = $this->postJson('/api/v1/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertOk()
            ->assertJsonPath('redirect', route('vendor.dashboard'));
    }

    public function test_users_cannot_authenticate_with_invalid_password(): void
    {
        $user = User::factory()->vendor()->create();

        $this->postJson('/api/v1/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ])->assertUnprocessable()->assertJsonValidationErrors('email');

        $this->assertGuest();
    }

    public function test_users_can_log_out(): void
    {
        $user = User::factory()->vendor()->create();

        $this->actingAs($user)
            ->postJson('/api/v1/logout')
            ->assertOk();

        $this->assertGuest();
    }

    public function test_users_are_rate_limited(): void
    {
        $user = User::factory()->vendor()->create();

        $data = [
            'email' => $user->email,
            'password' => 'wrong-password',
        ];

        // Lima percobaan pertama masih diproses oleh login.
        for ($percobaan = 1; $percobaan <= 5; $percobaan++) {
            $this->postJson('/api/v1/login', $data)->assertUnprocessable();
        }

        // Percobaan keenam harus terkena pembatasan.
        $this->postJson('/api/v1/login', $data)->assertTooManyRequests();
    }

    public function test_seeded_sample_account_of_every_additional_role_can_log_in(): void
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
            ])->assertOk()->assertJsonPath('redirect', route($routeName));

            $this->assertAuthenticated();
            $this->postJson('/api/v1/logout')->assertOk();
            $this->assertGuest();
        }
    }
}
