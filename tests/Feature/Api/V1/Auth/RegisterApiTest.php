<?php

namespace Tests\Feature\Api\V1\Auth;

use App\Models\DokumenPersyaratan;
use App\Models\Eo;
use App\Models\KomunitasLokal;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class RegisterApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
    }

    private function dataEo(array $override = []): array
    {
        return array_merge([
            'role' => 'eo',
            'name' => 'Budi Santoso',
            'email' => 'budi@eo-contoh.test',
            'nomor_telepon' => '081234567890',
            'nama_eo_organisasi' => 'Budi Event Organizer',
            'website_media_sosial' => 'https://instagram.com/budi.eo',
            'alamat_kantor' => 'Jl. Braga No. 1, Bandung',
            'jenis_event_yang_ditangani' => 'Festival',
            'documents' => [
                'ktp_penanggung_jawab' => UploadedFile::fake()->create('ktp-eo.pdf', 100, 'application/pdf'),
                'profil_legalitas_eo' => UploadedFile::fake()->create('legalitas-eo.pdf', 100, 'application/pdf'),
                'portofolio_event' => UploadedFile::fake()->create('portofolio-event.pdf', 100, 'application/pdf'),
            ],
            'password' => 'password',
            'password_confirmation' => 'password',
            'persetujuan' => '1',
        ], $override);
    }

    private function dataPengunjung(array $override = []): array
    {
        return array_merge([
            'role' => 'pengunjung',
            'name' => 'Jamal Pengunjung',
            'email' => 'jamal@pengunjung-contoh.test',
            'nomor_telepon' => '081234567890',
            'password' => 'password',
            'password_confirmation' => 'password',
        ], $override);
    }

    private function dataKomunitas(array $override = []): array
    {
        return array_merge([
            'role' => 'komunitas_lokal',
            'name' => 'Dedi Hermawan',
            'email' => 'dedi@komunitas-contoh.test',
            'nomor_telepon' => '081322223333',
            'nama_komunitas' => 'Komunitas Seni Angklung',
            'perkiraan_jumlah_anggota' => 25,
            'wilayah_kegiatan' => 'Kota Bandung',
            'bidang_kegiatan' => 'Seni & Budaya',
            'documents' => [
                'surat_keterangan' => UploadedFile::fake()->create('surat-komunitas.pdf', 100, 'application/pdf'),
                'identitas_ketua' => UploadedFile::fake()->create('identitas-ketua.pdf', 100, 'application/pdf'),
                'foto_kegiatan' => UploadedFile::fake()->create('kegiatan.jpg', 100, 'image/jpeg'),
            ],
            'password' => 'password',
            'password_confirmation' => 'password',
            'persetujuan' => '1',
        ], $override);
    }

    private function daftar(array $data)
    {
        return $this->post('/api/v1/register', $data, [
            'Accept' => 'application/json',
        ]);
    }

    public function test_pengunjung_can_register_and_is_active_immediately(): void
    {
        $this->daftar($this->dataPengunjung())
            ->assertCreated()
            ->assertJsonPath('message', 'Registrasi berhasil. Akun Anda sudah aktif, silakan login.')
            ->assertJsonPath('data.role', User::ROLE_PENGUNJUNG)
            ->assertJsonPath('data.status_verifikasi', 'verified');

        $akun = User::where('email', 'jamal@pengunjung-contoh.test')->firstOrFail();

        $this->assertSame(User::STATUS_AKTIF, $akun->status_akun);
        $this->assertDatabaseCount('dokumen_persyaratan', 0);
    }

    public function test_mitra_registration_creates_profile_documents_and_pending_status(): void
    {
        $response = $this->daftar($this->dataEo());

        $response->assertCreated()
            ->assertJsonPath('message', 'Registrasi berhasil. Menunggu verifikasi Admin.')
            ->assertJsonPath('data.role', User::ROLE_EO)
            ->assertJsonPath('data.status_verifikasi', 'pending');

        $akun = User::where('email', 'budi@eo-contoh.test')->firstOrFail();

        $this->assertSame(User::STATUS_MENUNGGU, $akun->status_akun);
        $this->assertDatabaseHas('eo', [
            'akun_id' => $akun->id,
            'nama_eo_organisasi' => 'Budi Event Organizer',
            'status_verifikasi' => Eo::STATUS_MENUNGGU,
        ]);

        $dokumen = DokumenPersyaratan::where('akun_id', $akun->id)->get();

        $this->assertCount(3, $dokumen);
        $this->assertSame('wajib', $dokumen->first()->sifat_dokumen);

        foreach ($dokumen as $item) {
            Storage::disk('local')->assertExists($item->lokasi_file);
        }
    }

    public function test_api_accepts_role_alias_and_stores_database_value(): void
    {
        $this->daftar($this->dataKomunitas())
            ->assertCreated()
            ->assertJsonPath('data.role', User::ROLE_KOMUNITAS)
            ->assertJsonPath('data.role_label', 'Komunitas Lokal')
            ->assertJsonPath('data.status_verifikasi', 'pending');

        $akun = User::where('email', 'dedi@komunitas-contoh.test')->firstOrFail();

        $this->assertSame(User::ROLE_KOMUNITAS, $akun->role);
        $this->assertDatabaseHas('komunitas_lokal', [
            'akun_id' => $akun->id,
            'nama_komunitas' => 'Komunitas Seni Angklung',
            'status_verifikasi' => KomunitasLokal::STATUS_MENUNGGU,
        ]);
    }

    public function test_registration_requires_full_account_and_consent_data(): void
    {
        $this->daftar($this->dataEo([
            'nomor_telepon' => '',
            'persetujuan' => null,
        ]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['nomor_telepon', 'persetujuan']);

        $this->assertDatabaseCount('users', 0);
    }

    public function test_registration_rejects_duplicate_email_and_unconfirmed_password(): void
    {
        User::factory()->create(['email' => 'budi@eo-contoh.test']);

        $this->daftar($this->dataEo([
            'password_confirmation' => 'beda',
        ]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['email', 'password']);

        $this->assertDatabaseCount('eo', 0);
    }
}
