<?php

namespace Tests\Feature\Api\V1;

use App\Models\User;
use App\Models\VendorUmkm;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleProfileApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_role_profile_api(): void
    {
        $this->getJson('/api/v1/me/role-profile')->assertUnauthorized();
    }

    public function test_vendor_can_view_its_role_profile(): void
    {
        $user = User::factory()->vendor()->create();

        VendorUmkm::create([
            'akun_id' => $user->id,
            'nama_usaha' => 'Kopi Jamal',
            'nama_pemilik' => 'Jamal',
            'bidang_usaha' => 'Kuliner',
            'status_verifikasi' => VendorUmkm::STATUS_DISETUJUI,
        ]);

        $this->actingAs($user)
            ->getJson('/api/v1/me/role-profile')
            ->assertOk()
            ->assertJsonPath('data.role', User::ROLE_VENDOR)
            ->assertJsonPath('data.role_label', 'Vendor/UMKM')
            ->assertJsonCount(4, 'data.fields')
            ->assertJsonPath('data.fields.0.key', 'nama_usaha')
            ->assertJsonPath('data.fields.0.value', 'Kopi Jamal');
    }

    public function test_vendor_can_update_its_role_profile_without_changing_verification_fields(): void
    {
        $user = User::factory()->vendor()->create();

        $profil = VendorUmkm::create([
            'akun_id' => $user->id,
            'nama_usaha' => 'Kopi Lama',
            'nama_pemilik' => 'Jamal',
            'alamat_usaha' => null,
            'bidang_usaha' => 'Kuliner',
            'status_verifikasi' => VendorUmkm::STATUS_DISETUJUI,
        ]);

        $this->actingAs($user)
            ->putJson('/api/v1/me/role-profile', [
                'nama_usaha' => 'Kopi Lokamitra',
                'nama_pemilik' => 'Jamal',
                'alamat_usaha' => 'Purwokerto',
                'bidang_usaha' => 'Kuliner',
                'status_verifikasi' => 'ditolak',
                'admin_id' => 999,
            ])
            ->assertOk()
            ->assertJsonPath('data.fields.0.value', 'Kopi Lokamitra')
            ->assertJsonPath('message', 'Profil peran berhasil diperbarui.');

        $this->assertDatabaseHas('vendor_umkm', [
            'vendor_id' => $profil->vendor_id,
            'nama_usaha' => 'Kopi Lokamitra',
            'status_verifikasi' => VendorUmkm::STATUS_DISETUJUI,
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
            'status_verifikasi' => VendorUmkm::STATUS_DISETUJUI,
        ]);

        $this->actingAs($user)
            ->putJson('/api/v1/me/role-profile', [
                'nama_usaha' => '',
                'nama_pemilik' => '',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['nama_usaha', 'nama_pemilik']);
    }

    public function test_visitor_cannot_access_role_profile_api(): void
    {
        $visitor = User::factory()->pengunjung()->create();

        $this->actingAs($visitor)
            ->getJson('/api/v1/me/role-profile')
            ->assertForbidden()
            ->assertJsonPath('message', 'Anda tidak memiliki akses ke halaman ini.');
    }

    public function test_inactive_account_gets_json_error_and_is_logged_out(): void
    {
        $user = User::factory()->vendor()->menunggu()->create();

        $this->actingAs($user)
            ->getJson('/api/v1/me/role-profile')
            ->assertForbidden()
            ->assertJsonPath('code', 'account_inactive');

        $this->assertGuest();
    }
}
