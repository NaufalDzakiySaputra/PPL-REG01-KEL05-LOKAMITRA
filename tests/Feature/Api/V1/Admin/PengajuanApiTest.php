<?php

namespace Tests\Feature\Api\V1\Admin;

use App\Models\Admin;
use App\Models\DokumenPersyaratan;
use App\Models\Eo;
use App\Models\KomunitasLokal;
use App\Models\Notifikasi;
use App\Models\User;
use App\Models\VendorUmkm;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PengajuanApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
    }

    private function buatAdmin(): User
    {
        $akun = User::factory()->admin()->create();

        Admin::create([
            'akun_id' => $akun->id,
        ]);

        return $akun;
    }

    private function buatEoMenunggu(): User
    {
        $akun = User::factory()->eo()->menunggu()->create();

        Eo::create([
            'akun_id' => $akun->id,
            'nama_eo_organisasi' => 'EO Contoh',
            'status_verifikasi' => 'menunggu',
        ]);

        return $akun;
    }

    private function buatKomunitasMenunggu(): User
    {
        $akun = User::factory()->komunitas()->menunggu()->create();

        KomunitasLokal::create([
            'akun_id' => $akun->id,
            'nama_komunitas' => 'Banyumas Creative Hub',
            'bidang_kegiatan' => 'Seni & Budaya',
            'status_verifikasi' => 'menunggu',
        ]);

        return $akun;
    }

    private function buatVendorMenunggu(): User
    {
        $akun = User::factory()->vendor()->menunggu()->create();

        VendorUmkm::create([
            'akun_id' => $akun->id,
            'nama_usaha' => 'UMKM Contoh',
            'nama_pemilik' => 'Pemilik Contoh',
            'bidang_usaha' => 'Kuliner',
            'status_verifikasi' => 'menunggu',
        ]);

        return $akun;
    }

    private function buatDokumen(User $akun, string $jenis, string $status = 'menunggu'): DokumenPersyaratan
    {
        $path = "dokumen-persyaratan/{$akun->id}/".str()->slug($jenis).'.pdf';
        Storage::disk('local')->put($path, "%PDF-1.4\n1 0 obj\n<< /Type /Catalog >>\nendobj\n%%EOF\n");

        return DokumenPersyaratan::create([
            'akun_id' => $akun->id,
            'jenis_dokumen' => $jenis,
            'nama_file' => str()->slug($jenis).'.pdf',
            'format_file' => 'pdf',
            'lokasi_file' => $path,
            'sifat_dokumen' => 'wajib',
            'tanggal_upload' => now(),
            'status_pemeriksaan' => $status,
        ]);
    }

    private function buatSemuaDokumenVendor(User $akun, string $status = 'disetujui'): void
    {
        foreach (config('lokamitra.dokumen_registrasi.vendor', []) as $persyaratan) {
            if ($persyaratan['wajib']) {
                $this->buatDokumen($akun, $persyaratan['jenis_dokumen'], $status);
            }
        }
    }

    private function buatSemuaDokumenEo(User $akun, string $status = 'disetujui'): void
    {
        foreach (config('lokamitra.dokumen_registrasi.eo', []) as $persyaratan) {
            if ($persyaratan['wajib']) {
                $this->buatDokumen($akun, $persyaratan['jenis_dokumen'], $status);
            }
        }
    }

    private function urlPerbarui(int $id): string
    {
        return "/api/v1/admin/pengajuan/{$id}";
    }

    private function urlSetujui(int $id): string
    {
        return "/api/v1/admin/pengajuan/{$id}/approve";
    }

    private function urlTolak(int $id): string
    {
        return "/api/v1/admin/pengajuan/{$id}/reject";
    }

    private function urlPeriksa(int $id, int $dokumen): string
    {
        return "/api/v1/admin/pengajuan/{$id}/documents/{$dokumen}/review";
    }

    private function urlPreview(int $id, int $dokumen): string
    {
        return "/api/v1/admin/pengajuan/{$id}/documents/{$dokumen}/preview";
    }

    private function urlUnduh(int $id, int $dokumen): string
    {
        return "/api/v1/admin/pengajuan/{$id}/documents/{$dokumen}/download";
    }

    public function test_admin_can_view_pending_registration_list(): void
    {
        $admin = $this->buatAdmin();
        $pengaju = $this->buatEoMenunggu();

        $this->actingAs($admin)
            ->getJson('/api/v1/admin/pengajuan')
            ->assertOk()
            ->assertJsonPath('ringkasan.menunggu', 1)
            ->assertJsonPath('pengajuan.data.0.id', $pengaju->id)
            ->assertJsonCount(1, 'pengajuan.data');
    }

    public function test_visitor_accounts_are_not_listed_for_admin_verification(): void
    {
        $admin = $this->buatAdmin();
        $this->buatEoMenunggu();
        User::factory()->pengunjung()->create();

        $this->actingAs($admin)
            ->getJson('/api/v1/admin/pengajuan')
            ->assertOk()
            ->assertJsonPath('ringkasan.menunggu', 1)
            ->assertJsonCount(1, 'pengajuan.data');
    }

    public function test_only_admin_can_access_registration_api(): void
    {
        $pengaju = $this->buatEoMenunggu();

        $this->getJson('/api/v1/admin/pengajuan')->assertUnauthorized();

        $this->actingAs($pengaju)
            ->getJson('/api/v1/admin/pengajuan')
            ->assertForbidden();

        $this->actingAs($this->buatAdmin())
            ->getJson("/api/v1/admin/pengajuan/{$pengaju->id}")
            ->assertOk();
    }

    public function test_admin_can_view_applicant_profile_and_private_document_urls(): void
    {
        $admin = $this->buatAdmin();
        $pengaju = $this->buatVendorMenunggu();
        $dokumen = $this->buatDokumen($pengaju, 'NIB atau surat keterangan usaha');

        $this->actingAs($admin)
            ->getJson("/api/v1/admin/pengajuan/{$pengaju->id}")
            ->assertOk()
            ->assertJsonPath('pengajuan.email', $pengaju->email)
            ->assertJsonPath('pengajuan.profil.0.label', 'Nama usaha')
            ->assertJsonPath('pengajuan.dokumen.0.id', $dokumen->dokumen_id)
            ->assertJsonPath(
                'pengajuan.dokumen.0.file_url',
                $this->urlUnduh($pengaju->id, $dokumen->dokumen_id)
            )
            ->assertJsonPath('dapat_disetujui', false);
    }

    public function test_admin_cannot_approve_vendor_until_all_required_documents_are_approved(): void
    {
        $admin = $this->buatAdmin();
        $pengaju = $this->buatVendorMenunggu();
        $this->buatSemuaDokumenVendor($pengaju, 'menunggu');

        $this->actingAs($admin)
            ->postJson($this->urlSetujui($pengaju->id))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('verifikasi');

        $this->assertSame(User::STATUS_MENUNGGU, $pengaju->fresh()->status_akun);
        $this->assertSame('menunggu', $pengaju->vendor->fresh()->status_verifikasi);
    }

    public function test_admin_cannot_approve_eo_before_required_documents_are_uploaded(): void
    {
        $admin = $this->buatAdmin();
        $pengaju = $this->buatEoMenunggu();

        $this->actingAs($admin)
            ->postJson($this->urlSetujui($pengaju->id))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('verifikasi');

        $this->assertSame(User::STATUS_MENUNGGU, $pengaju->fresh()->status_akun);
    }

    public function test_admin_can_approve_eo_after_required_documents_are_approved(): void
    {
        $admin = $this->buatAdmin();
        $pengaju = $this->buatEoMenunggu();
        $this->buatSemuaDokumenEo($pengaju, 'disetujui');

        $this->actingAs($admin)
            ->postJson($this->urlSetujui($pengaju->id))
            ->assertOk()
            ->assertJsonPath('message', 'Akun pendaftar berhasil disetujui dan sekarang dapat login.')
            ->assertJsonPath('data.status_akun', User::STATUS_AKTIF);

        $this->assertSame(User::STATUS_AKTIF, $pengaju->fresh()->status_akun);
        $this->assertSame('disetujui', $pengaju->eo->fresh()->status_verifikasi);
        $this->assertSame($admin->admin->admin_id, $pengaju->eo->fresh()->admin_id);
        $this->assertDatabaseHas('riwayat_aktivitas', [
            'akun_id' => $admin->id,
            'jenis_aktivitas' => 'akun_disetujui',
        ]);
        $this->assertDatabaseHas('notifikasi', [
            'akun_id' => $pengaju->id,
            'status_baca' => 'belum_dibaca',
        ]);
    }

    public function test_admin_can_approve_vendor_after_all_required_documents_are_approved(): void
    {
        $admin = $this->buatAdmin();
        $pengaju = $this->buatVendorMenunggu();
        $this->buatSemuaDokumenVendor($pengaju, 'disetujui');

        $this->actingAs($admin)
            ->postJson($this->urlSetujui($pengaju->id))
            ->assertOk()
            ->assertJsonPath('data.status_akun', User::STATUS_AKTIF);

        $this->assertSame(User::STATUS_AKTIF, $pengaju->fresh()->status_akun);
        $this->assertSame('disetujui', $pengaju->vendor->fresh()->status_verifikasi);
    }

    public function test_admin_can_reject_account_and_reason_is_recorded(): void
    {
        $admin = $this->buatAdmin();
        $pengaju = $this->buatEoMenunggu();

        $this->actingAs($admin)
            ->postJson($this->urlTolak($pengaju->id), [
                'alasan' => 'Dokumen dan data organisasi tidak sesuai.',
            ])
            ->assertOk()
            ->assertJsonPath('data.status_akun', User::STATUS_DITOLAK);

        $this->assertSame(User::STATUS_DITOLAK, $pengaju->fresh()->status_akun);
        $this->assertSame('ditolak', $pengaju->eo->fresh()->status_verifikasi);
        $this->assertDatabaseHas('riwayat_aktivitas', [
            'akun_id' => $admin->id,
            'jenis_aktivitas' => 'akun_ditolak',
        ]);
        $this->assertStringContainsString(
            'Dokumen dan data organisasi tidak sesuai.',
            Notifikasi::where('akun_id', $pengaju->id)->firstOrFail()->isi_notifikasi
        );
    }

    public function test_reject_accepts_catatan_alias_from_spec(): void
    {
        $admin = $this->buatAdmin();
        $pengaju = $this->buatEoMenunggu();

        $this->actingAs($admin)
            ->postJson($this->urlTolak($pengaju->id), [
                'catatan' => 'Data pendaftar tidak lengkap dan tidak sesuai.',
            ])
            ->assertOk()
            ->assertJsonPath('data.status_akun', User::STATUS_DITOLAK);

        $this->assertStringContainsString(
            'Data pendaftar tidak lengkap dan tidak sesuai.',
            Notifikasi::where('akun_id', $pengaju->id)->firstOrFail()->isi_notifikasi
        );
    }

    public function test_rejecting_account_requires_a_reason(): void
    {
        $admin = $this->buatAdmin();
        $pengaju = $this->buatEoMenunggu();

        $this->actingAs($admin)
            ->postJson($this->urlTolak($pengaju->id), ['alasan' => ''])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('alasan');

        $this->assertSame(User::STATUS_MENUNGGU, $pengaju->fresh()->status_akun);
    }

    public function test_admin_can_review_document_and_notify_applicant(): void
    {
        $admin = $this->buatAdmin();
        $pengaju = $this->buatVendorMenunggu();
        $dokumen = $this->buatDokumen($pengaju, 'NIB atau surat keterangan usaha');

        $this->actingAs($admin)
            ->postJson($this->urlPeriksa($pengaju->id, $dokumen->dokumen_id), [
                'status_pemeriksaan' => 'disetujui',
                'catatan_admin' => 'Dokumen jelas dan sesuai.',
            ])
            ->assertOk()
            ->assertJsonPath('data.status', 'disetujui');

        $this->assertDatabaseHas('dokumen_persyaratan', [
            'dokumen_id' => $dokumen->dokumen_id,
            'admin_id' => $admin->admin->admin_id,
            'status_pemeriksaan' => 'disetujui',
        ]);
        $this->assertDatabaseHas('riwayat_aktivitas', [
            'akun_id' => $admin->id,
            'jenis_aktivitas' => 'dokumen_disetujui',
        ]);
        $this->assertDatabaseHas('notifikasi', [
            'akun_id' => $pengaju->id,
        ]);
    }

    public function test_rejected_document_requires_admin_note(): void
    {
        $admin = $this->buatAdmin();
        $pengaju = $this->buatVendorMenunggu();
        $dokumen = $this->buatDokumen($pengaju, 'NIB atau surat keterangan usaha');

        $this->actingAs($admin)
            ->postJson($this->urlPeriksa($pengaju->id, $dokumen->dokumen_id), [
                'status_pemeriksaan' => 'ditolak',
                'catatan_admin' => '',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('catatan_admin');

        $this->assertSame('menunggu', $dokumen->fresh()->status_pemeriksaan);
    }

    public function test_admin_can_preview_pdf_document_inline_from_private_storage(): void
    {
        $admin = $this->buatAdmin();
        $pengaju = $this->buatVendorMenunggu();
        $dokumen = $this->buatDokumen($pengaju, 'NIB atau surat keterangan usaha');

        $this->actingAs($admin)
            ->get($this->urlPreview($pengaju->id, $dokumen->dokumen_id))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf')
            ->assertHeader('Content-Disposition', 'inline')
            ->assertHeader('X-Content-Type-Options', 'nosniff');
    }

    public function test_non_admin_cannot_preview_private_document(): void
    {
        $pengaju = $this->buatVendorMenunggu();
        $dokumen = $this->buatDokumen($pengaju, 'NIB atau surat keterangan usaha');

        $this->actingAs(User::factory()->vendor()->create())
            ->get($this->urlPreview($pengaju->id, $dokumen->dokumen_id))
            ->assertForbidden();
    }

    public function test_admin_cannot_preview_document_belonging_to_another_applicant(): void
    {
        $admin = $this->buatAdmin();
        $pengaju = $this->buatVendorMenunggu();
        $akunLain = $this->buatEoMenunggu();
        $dokumen = $this->buatDokumen($akunLain, 'Dokumen milik akun lain');

        $this->actingAs($admin)
            ->get($this->urlPreview($pengaju->id, $dokumen->dokumen_id))
            ->assertNotFound();
    }

    public function test_admin_can_download_document_from_private_storage(): void
    {
        $admin = $this->buatAdmin();
        $pengaju = $this->buatVendorMenunggu();
        $dokumen = $this->buatDokumen($pengaju, 'NIB atau surat keterangan usaha');

        $this->actingAs($admin)
            ->get($this->urlUnduh($pengaju->id, $dokumen->dokumen_id))
            ->assertOk();
    }

    public function test_admin_cannot_download_document_belonging_to_another_applicant(): void
    {
        $admin = $this->buatAdmin();
        $pengaju = $this->buatVendorMenunggu();
        $akunLain = $this->buatEoMenunggu();
        $dokumen = $this->buatDokumen($akunLain, 'Dokumen milik akun lain');

        $this->actingAs($admin)
            ->get($this->urlUnduh($pengaju->id, $dokumen->dokumen_id))
            ->assertNotFound();
    }

    public function test_spec_list_uses_pending_status_and_api_role_alias(): void
    {
        $admin = $this->buatAdmin();
        $pengaju = $this->buatKomunitasMenunggu();

        $this->actingAs($admin)
            ->getJson('/api/v1/admin/pengajuan?status=pending')
            ->assertOk()
            ->assertJsonPath('data.0.id', $pengaju->id)
            ->assertJsonPath('data.0.user_name', $pengaju->name)
            ->assertJsonPath('data.0.role', 'komunitas_lokal')
            ->assertJsonPath('data.0.status_verifikasi', 'pending')
            ->assertJsonPath('data.0.created_at', $pengaju->created_at?->toIso8601String())
            ->assertJsonPath('filter.status', User::STATUS_MENUNGGU)
            ->assertJsonPath('filter.status_api', 'pending');
    }

    public function test_spec_patch_can_verified_account(): void
    {
        $admin = $this->buatAdmin();
        $pengaju = $this->buatEoMenunggu();
        $this->buatSemuaDokumenEo($pengaju, 'disetujui');

        $this->actingAs($admin)
            ->patchJson($this->urlPerbarui($pengaju->id), [
                'status' => 'verified',
                'catatan' => 'Dokumen legalitas lengkap dan telah diverifikasi Admin.',
            ])
            ->assertOk()
            ->assertJsonPath(
                'message',
                'Status verifikasi akun mitra berhasil diperbarui.'
            )
            ->assertJsonPath('data.id', $pengaju->id)
            ->assertJsonPath('data.status_verifikasi', 'verified');

        $this->assertSame(User::STATUS_AKTIF, $pengaju->fresh()->status_akun);
        $this->assertSame('disetujui', $pengaju->eo->fresh()->status_verifikasi);
    }

    public function test_spec_patch_can_reject_account_with_catatan(): void
    {
        $admin = $this->buatAdmin();
        $pengaju = $this->buatEoMenunggu();

        $this->actingAs($admin)
            ->patchJson($this->urlPerbarui($pengaju->id), [
                'status' => 'rejected',
                'catatan' => 'Data pendaftar tidak lengkap dan tidak sesuai.',
            ])
            ->assertOk()
            ->assertJsonPath('data.status_verifikasi', 'rejected');

        $this->assertSame(User::STATUS_DITOLAK, $pengaju->fresh()->status_akun);
        $this->assertSame('ditolak', $pengaju->eo->fresh()->status_verifikasi);
        $this->assertStringContainsString(
            'Data pendaftar tidak lengkap dan tidak sesuai.',
            Notifikasi::where('akun_id', $pengaju->id)->firstOrFail()->isi_notifikasi
        );
    }

    public function test_spec_patch_validates_status_and_rejection_note(): void
    {
        $admin = $this->buatAdmin();
        $pengaju = $this->buatEoMenunggu();

        $this->actingAs($admin)
            ->patchJson($this->urlPerbarui($pengaju->id), [
                'status' => 'approved',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('status');

        $this->actingAs($admin)
            ->patchJson($this->urlPerbarui($pengaju->id), [
                'status' => 'rejected',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('catatan');

        $this->assertSame(User::STATUS_MENUNGGU, $pengaju->fresh()->status_akun);
    }

    public function test_only_admin_can_update_verification_status(): void
    {
        $pengaju = $this->buatEoMenunggu();
        $lain = $this->buatEoMenunggu();

        $this->patchJson($this->urlPerbarui($pengaju->id), [
            'status' => 'verified',
        ])->assertUnauthorized();

        $this->actingAs($lain)
            ->patchJson($this->urlPerbarui($pengaju->id), [
                'status' => 'verified',
            ])
            ->assertForbidden();
    }
}
