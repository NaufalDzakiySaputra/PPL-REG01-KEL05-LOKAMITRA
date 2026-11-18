<?php

namespace Tests\Feature\Api\V1;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_profile_api(): void
    {
        $this->getJson('/api/v1/me')->assertUnauthorized();
    }

    public function test_authenticated_user_can_view_profile(): void
    {
        $user = User::factory()->vendor()->create();

        $this->actingAs($user)
            ->getJson('/api/v1/me')
            ->assertOk()
            ->assertJsonPath('data.id', $user->id)
            ->assertJsonPath('data.name', $user->name)
            ->assertJsonPath('data.email', $user->email)
            ->assertJsonPath('data.role', User::ROLE_VENDOR)
            ->assertJsonPath('data.role_label', 'Vendor/UMKM');
    }

    public function test_profile_information_can_be_updated(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->patchJson('/api/v1/me', [
                'name' => 'Test User',
                'email' => 'test@example.com',
            ])
            ->assertOk()
            ->assertJsonPath('data.name', 'Test User')
            ->assertJsonPath('data.email', 'test@example.com')
            ->assertJsonPath('message', 'Profil berhasil diperbarui.');

        $user->refresh();

        $this->assertSame('Test User', $user->name);
        $this->assertSame('test@example.com', $user->email);
        $this->assertNull($user->email_verified_at);
    }

    public function test_email_verification_status_is_unchanged_when_email_is_unchanged(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->patchJson('/api/v1/me', [
                'name' => 'Test User',
                'email' => $user->email,
            ])
            ->assertOk();

        $this->assertNotNull($user->refresh()->email_verified_at);
    }

    public function test_profile_validation_errors_are_returned_as_json(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();

        $this->actingAs($user)
            ->patchJson('/api/v1/me', [
                'name' => '',
                'email' => $other->email,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name', 'email']);
    }

    public function test_user_can_delete_their_account_with_password(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->deleteJson('/api/v1/profile', [
                'password' => 'password',
            ])
            ->assertOk()
            ->assertJsonPath(
                'message',
                'Akun Anda berhasil dihapus secara permanen.'
            );

        $this->assertGuest();
        $this->assertNull($user->fresh());
    }

    public function test_correct_password_is_required_to_delete_account(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->deleteJson('/api/v1/profile', [
                'password' => 'wrong-password',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('password');

        $this->assertNotNull($user->fresh());
    }
}
