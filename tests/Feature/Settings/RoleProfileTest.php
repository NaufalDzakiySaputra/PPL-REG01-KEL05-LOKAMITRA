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

    public function test_vendor_can_update_its_profile_without_changing_verification_fields(): void
    {
        $user = User::factory()->vendor()->create();

        $profil = VendorUmkm::create([
            'akun_id' => $user->id,
            'nama_usaha' => 'Kopi Lama',
            'nama_pemilik' => 'Jamal',
            'alamat_usaha' => null,
            'bidang_usaha' => 'Kuliner',
            'status_verifikasi' => 'disetujui',
        ]);

        $this->actingAs($user)
            ->put(route('role-profile.update'), [
                'nama_usaha' => 'Kopi Lokamitra',
                'nama_pemilik' => 'Jamal',
                'alamat_usaha' => 'Purwokerto',
                'bidang_usaha' => 'Kuliner',
                'status_verifikasi' => 'ditolak',
                'admin_id' => 999,
            ])
            ->assertRedirect(route('role-profile.edit'));

        $this->assertDatabaseHas('vendor_umkm', [
            'vendor_id' => $profil->vendor_id,
            'nama_usaha' => 'Kopi Lokamitra',
            'status_verifikasi' => 'disetujui',
            'admin_id' => null,
        ]);

        $this->assertDatabaseHas('riwayat_aktivitas', [
            'akun_id' => $user->id,
            'jenis_aktivitas' => 'profil_peran_diperbarui',
        ]);
    }

    public function test_required_role_profile_fields_are_validated(): void
    {
        $user = User::factory()->vendor()->create();

        VendorUmkm::create([
            'akun_id' => $user->id,
            'nama_usaha' => 'Kopi Lama',
            'nama_pemilik' => 'Jamal',
            'status_verifikasi' => 'disetujui',
        ]);

        $this->actingAs($user)
            ->from(route('role-profile.edit'))
            ->put(route('role-profile.update'), [
                'nama_usaha' => '',
                'nama_pemilik' => '',
            ])
            ->assertRedirect(route('role-profile.edit'))
            ->assertSessionHasErrors(['nama_usaha', 'nama_pemilik']);
    }

    public function test_visitor_cannot_access_role_profile_management(): void
    {
        $visitor = User::factory()->pengunjung()->create();

        $this->actingAs($visitor)
            ->get(route('role-profile.edit'))
            ->assertForbidden();
    }
}
