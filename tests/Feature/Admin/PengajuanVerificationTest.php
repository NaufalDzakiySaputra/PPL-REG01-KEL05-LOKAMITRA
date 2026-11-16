<?php

namespace Tests\Feature\Admin;

use App\Models\Admin;
use App\Models\DokumenPersyaratan;
use App\Models\Eo;
use App\Models\Notifikasi;
use App\Models\User;
use App\Models\VendorUmkm;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PengajuanVerificationTest extends TestCase
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

    public function test_admin_can_view_pending_registration_list(): void
    {
        $admin = $this->buatAdmin();
        $pengaju = $this->buatEoMenunggu();

        $this->actingAs($admin)
            ->get(route('admin.pengajuan.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('admin/pengajuan/Index')
                ->where('ringkasan.menunggu', 1)
                ->has('pengajuan.data', 1)
                ->where('pengajuan.data.0.id', $pengaju->id)
            );
    }

    public function test_visitor_accounts_are_not_listed_for_admin_verification(): void
    {
        $admin = $this->buatAdmin();
        $this->buatEoMenunggu();
        User::factory()->pengunjung()->create();

        $this->actingAs($admin)
            ->get(route('admin.pengajuan.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('ringkasan.menunggu', 1)
                ->has('pengajuan.data', 1));
    }

    public function test_only_admin_can_open_registration_management(): void
    {
        $pengaju = $this->buatEoMenunggu();

        $this->get(route('admin.pengajuan.index'))
            ->assertRedirect(route('login'));

        $this->actingAs(User::factory()->eo()->create())
            ->get(route('admin.pengajuan.index'))
            ->assertForbidden();

        $this->actingAs($this->buatAdmin())
            ->get(route('admin.pengajuan.show', $pengaju))
            ->assertOk();
    }

    public function test_admin_can_view_applicant_profile_and_private_documents(): void
    {
        $admin = $this->buatAdmin();
        $pengaju = $this->buatVendorMenunggu();
        $dokumen = $this->buatDokumen($pengaju, 'NIB atau surat keterangan usaha');

        $this->actingAs($admin)
            ->get(route('admin.pengajuan.show', $pengaju))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('admin/pengajuan/Show')
                ->where('pengajuan.email', $pengaju->email)
                ->where('pengajuan.profil.0.label', 'Nama usaha')
                ->where('pengajuan.dokumen.0.id', $dokumen->dokumen_id)
                ->where('dapat_disetujui', false)
            );
    }

    public function test_admin_cannot_approve_vendor_until_all_required_documents_are_approved(): void
    {
        $admin = $this->buatAdmin();
        $pengaju = $this->buatVendorMenunggu();
        $this->buatSemuaDokumenVendor($pengaju, 'menunggu');

        $this->actingAs($admin)
            ->post(route('admin.pengajuan.approve', $pengaju))
            ->assertSessionHasErrors('verifikasi');

        $this->assertSame(User::STATUS_MENUNGGU, $pengaju->fresh()->status_akun);
        $this->assertSame('menunggu', $pengaju->vendor->fresh()->status_verifikasi);
    }

    public function test_admin_cannot_approve_eo_before_required_documents_are_uploaded(): void
    {
        $admin = $this->buatAdmin();
        $pengaju = $this->buatEoMenunggu();

        $this->actingAs($admin)
            ->post(route('admin.pengajuan.approve', $pengaju))
            ->assertSessionHasErrors('verifikasi');

        $this->assertSame(User::STATUS_MENUNGGU, $pengaju->fresh()->status_akun);
    }

    public function test_admin_can_approve_eo_after_required_documents_are_approved(): void
    {
        $admin = $this->buatAdmin();
        $pengaju = $this->buatEoMenunggu();
        $this->buatSemuaDokumenEo($pengaju, 'disetujui');

        $this->actingAs($admin)
            ->post(route('admin.pengajuan.approve', $pengaju))
            ->assertRedirect(route('admin.pengajuan.show', $pengaju));

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
            ->post(route('admin.pengajuan.approve', $pengaju))
            ->assertRedirect(route('admin.pengajuan.show', $pengaju));

        $this->assertSame(User::STATUS_AKTIF, $pengaju->fresh()->status_akun);
        $this->assertSame('disetujui', $pengaju->vendor->fresh()->status_verifikasi);
    }

    public function test_admin_can_reject_account_and_reason_is_recorded(): void
    {
        $admin = $this->buatAdmin();
        $pengaju = $this->buatEoMenunggu();

        $this->actingAs($admin)
            ->post(route('admin.pengajuan.reject', $pengaju), [
                'alasan' => 'Dokumen dan data organisasi tidak sesuai.',
            ])
            ->assertRedirect(route('admin.pengajuan.show', $pengaju));

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

    public function test_rejecting_account_requires_a_reason(): void
    {
        $admin = $this->buatAdmin();
        $pengaju = $this->buatEoMenunggu();

        $this->actingAs($admin)
            ->post(route('admin.pengajuan.reject', $pengaju), ['alasan' => ''])
            ->assertSessionHasErrors('alasan');

        $this->assertSame(User::STATUS_MENUNGGU, $pengaju->fresh()->status_akun);
    }

    public function test_admin_can_review_document_and_notify_applicant(): void
    {
        $admin = $this->buatAdmin();
        $pengaju = $this->buatVendorMenunggu();
        $dokumen = $this->buatDokumen($pengaju, 'NIB atau surat keterangan usaha');

        $this->actingAs($admin)
            ->post(route('admin.pengajuan.documents.review', [$pengaju, $dokumen]), [
                'status_pemeriksaan' => 'disetujui',
                'catatan_admin' => 'Dokumen jelas dan sesuai.',
            ])
            ->assertRedirect(route('admin.pengajuan.show', $pengaju));

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
            ->post(route('admin.pengajuan.documents.review', [$pengaju, $dokumen]), [
                'status_pemeriksaan' => 'ditolak',
                'catatan_admin' => '',
            ])
            ->assertSessionHasErrors('catatan_admin');

        $this->assertSame('menunggu', $dokumen->fresh()->status_pemeriksaan);
    }

    public function test_admin_can_preview_pdf_document_inline_from_private_storage(): void
    {
        $admin = $this->buatAdmin();
        $pengaju = $this->buatVendorMenunggu();
        $dokumen = $this->buatDokumen($pengaju, 'NIB atau surat keterangan usaha');

        $this->actingAs($admin)
            ->get(route('admin.pengajuan.documents.preview', [$pengaju, $dokumen]))
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
            ->get(route('admin.pengajuan.documents.preview', [$pengaju, $dokumen]))
            ->assertForbidden();
    }

    public function test_admin_cannot_preview_document_belonging_to_another_applicant(): void
    {
        $admin = $this->buatAdmin();
        $pengaju = $this->buatVendorMenunggu();
        $akunLain = $this->buatEoMenunggu();
        $dokumen = $this->buatDokumen($akunLain, 'Dokumen milik akun lain');

        $this->actingAs($admin)
            ->get(route('admin.pengajuan.documents.preview', [$pengaju, $dokumen]))
            ->assertNotFound();
    }

    public function test_admin_can_download_document_from_private_storage(): void
    {
        $admin = $this->buatAdmin();
        $pengaju = $this->buatVendorMenunggu();
        $dokumen = $this->buatDokumen($pengaju, 'NIB atau surat keterangan usaha');

        $this->actingAs($admin)
            ->get(route('admin.pengajuan.documents.download', [$pengaju, $dokumen]))
            ->assertOk();
    }

    public function test_admin_cannot_download_document_belonging_to_another_applicant(): void
    {
        $admin = $this->buatAdmin();
        $pengaju = $this->buatVendorMenunggu();
        $akunLain = $this->buatEoMenunggu();
        $dokumen = $this->buatDokumen($akunLain, 'Dokumen milik akun lain');

        $this->actingAs($admin)
            ->get(route('admin.pengajuan.documents.download', [$pengaju, $dokumen]))
            ->assertNotFound();
    }
}
