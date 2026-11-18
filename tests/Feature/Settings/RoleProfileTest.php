<?php

namespace Tests\Feature\Settings;

use App\Models\User;
use App\Models\VendorUmkm;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class RoleProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_vendor_can_view_its_role_profile(): void
    {
        $user = User::factory()->vendor()->create();

        VendorUmkm::create([
            'akun_id' => $user->id,
            'nama_usaha' => 'Kopi Jamal',
            'nama_pemilik' => 'Jamal',
            'bidang_usaha' => 'Kuliner',
            'status_verifikasi' => 'disetujui',
        ]);

        $this->actingAs($user)
            ->get(route('role-profile.edit'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('settings/RoleProfile')
            );
    }

    public function test_visitor_cannot_access_role_profile_management(): void
    {
        $visitor = User::factory()->pengunjung()->create();

        $this->actingAs($visitor)
            ->get(route('role-profile.edit'))
            ->assertForbidden();
    }
}
