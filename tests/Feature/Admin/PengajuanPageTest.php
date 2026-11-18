<?php

namespace Tests\Feature\Admin;

use App\Models\Admin;
use App\Models\Eo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class PengajuanPageTest extends TestCase
{
    use RefreshDatabase;

    private function buatAdmin(): User
    {
        $akun = User::factory()->admin()->create();

        Admin::create([
            'akun_id' => $akun->id,
        ]);

        return $akun;
    }

    public function test_halaman_daftar_pengajuan_dapat_dibuka_admin(): void
    {
        $admin = $this->buatAdmin();
        $pengaju = User::factory()->eo()->menunggu()->create();

        Eo::create([
            'akun_id' => $pengaju->id,
            'nama_eo_organisasi' => 'EO Contoh',
            'status_verifikasi' => 'menunggu',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.pengajuan.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/pengajuan/Index')
                ->where('ringkasan.menunggu', 1)
                ->has('pengajuan.data', 1)
            );
    }

    public function test_halaman_detail_pengajuan_dapat_dibuka_admin(): void
    {
        $admin = $this->buatAdmin();
        $pengaju = User::factory()->eo()->menunggu()->create();

        Eo::create([
            'akun_id' => $pengaju->id,
            'nama_eo_organisasi' => 'EO Contoh',
            'status_verifikasi' => 'menunggu',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.pengajuan.show', $pengaju))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/pengajuan/Show')
                ->where('pengajuan.id', $pengaju->id)
            );
    }

    public function test_halaman_pengajuan_hanya_untuk_admin(): void
    {
        $this->get(route('admin.pengajuan.index'))
            ->assertRedirect(route('login'));

        $this->actingAs(User::factory()->eo()->create())
            ->get(route('admin.pengajuan.index'))
            ->assertForbidden();
    }
}
