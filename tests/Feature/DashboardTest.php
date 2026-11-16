<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_the_login_page(): void
    {
        $this->get(route('dashboard'))
            ->assertRedirect(route('login'));
    }

    public function test_authenticated_users_are_redirected_to_their_role_dashboard(): void
    {
        $user = User::factory()->vendor()->create();

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertRedirect(route('vendor.dashboard'));

        $this->get(route('vendor.dashboard'))->assertOk();
    }
}
