<?php

namespace Tests\Feature\Api\V1\Auth;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Laravel\Fortify\Features;
use Tests\TestCase;

class PasswordResetApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->skipUnlessFortifyHas(Features::resetPasswords());
    }

    private function tokenUntuk(User $user): string
    {
        $token = null;

        Notification::assertSentTo(
            $user,
            ResetPassword::class,
            function (ResetPassword $notification) use (&$token): bool {
                $token = $notification->token;

                return true;
            }
        );

        return $token;
    }

    public function test_reset_password_link_screen_can_be_rendered(): void
    {
        $this->get(route('password.request'))->assertOk();
    }

    public function test_reset_password_link_can_be_requested(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        $this->postJson('/api/v1/forgot-password', ['email' => $user->email])
            ->assertOk()
            ->assertJsonPath(
                'message',
                'Tautan reset kata sandi telah dikirim ke email Anda.'
            );

        Notification::assertSentTo($user, ResetPassword::class);
    }

    public function test_reset_password_link_is_not_sent_for_unknown_email(): void
    {
        Notification::fake();

        $this->postJson('/api/v1/forgot-password', [
            'email' => 'tidak-ada@lokamitra.test',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('email');

        Notification::assertNothingSent();
    }

    public function test_reset_password_screen_can_be_rendered(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        $this->postJson('/api/v1/forgot-password', ['email' => $user->email])
            ->assertOk();

        $this->get(route('password.reset', $this->tokenUntuk($user)))
            ->assertOk();
    }

    public function test_password_can_be_reset_with_valid_token(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        $this->postJson('/api/v1/forgot-password', ['email' => $user->email])
            ->assertOk();

        $this->postJson('/api/v1/reset-password', [
            'token' => $this->tokenUntuk($user),
            'email' => $user->email,
            'password' => 'password-baru',
            'password_confirmation' => 'password-baru',
        ])
            ->assertOk()
            ->assertJsonPath('message', 'Kata sandi berhasil diperbarui.');

        $this->assertTrue(Hash::check('password-baru', $user->fresh()->password));
    }

    public function test_password_cannot_be_reset_with_invalid_token(): void
    {
        $user = User::factory()->create();

        $this->postJson('/api/v1/reset-password', [
            'token' => 'invalid-token',
            'email' => $user->email,
            'password' => 'password-baru',
            'password_confirmation' => 'password-baru',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('email');
    }

    public function test_password_reset_requires_matching_confirmation(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        $this->postJson('/api/v1/forgot-password', ['email' => $user->email])
            ->assertOk();

        $this->postJson('/api/v1/reset-password', [
            'token' => $this->tokenUntuk($user),
            'email' => $user->email,
            'password' => 'password-baru',
            'password_confirmation' => 'beda-password',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('password');
    }
}
