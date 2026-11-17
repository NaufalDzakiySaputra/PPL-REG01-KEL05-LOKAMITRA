<?php

namespace Tests\Feature\Api\V1\Auth;

use App\Models\Akademisi;
use App\Models\Eo;
use App\Models\KomunitasLokal;
use App\Models\Media;
use App\Models\PemerintahDaerah;
use App\Models\User;
use App\Models\VendorUmkm;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
    }

    private function daftar(array $data)
    {
        return $this->post('/api/v1/register', $data, [
            'Accept' => 'application/json',
        ]);
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

    private function dataVendor(array $override = []): array
    {
        return array_merge([
            'role' => 'vendor',
            'name' => 'Siti Aminah',
            'email' => 'siti@vendor-contoh.test',
            'nomor_telepon' => '081298765432',
            'nama_usaha' => 'Kopi Siti',
            'nama_pemilik' => 'Siti Aminah',
            'alamat_usaha' => 'Jl. Dago No. 2, Bandung',
            'bidang_usaha' => 'Kuliner',
            'documents' => [
                'nib' => UploadedFile::fake()->create('nib.pdf', 100, 'application/pdf'),
                'identitas_pemilik' => UploadedFile::fake()->create('ktp-pemilik.pdf', 100, 'application/pdf'),
                'profil_katalog' => UploadedFile::fake()->create('katalog.pdf', 100, 'application/pdf'),
                'foto_portofolio' => UploadedFile::fake()->create('portofolio.jpg', 100, 'image/jpeg'),
            ],
            'password' => 'password',
            'password_confirmation' => 'password',
            'persetujuan' => '1',
        ], $override);
    }

    private function dataPemda(array $override = []): array
    {
        return array_merge([
            'role' => 'pemda',
            'name' => 'Rina Kusuma',
            'email' => 'rina@dinas-contoh.test',
            'nomor_telepon' => '081311112222',
            'nama_instansi' => 'Dinas Pariwisata Kota Bandung',
            'jabatan_pendaftar' => 'Kepala Seksi Perizinan',
            'wilayah_kerja' => 'Kota Bandung',
            'bidang_yang_ditangani' => 'Pariwisata',
            'documents' => [
                'surat_tugas' => UploadedFile::fake()->create('surat-tugas.pdf', 100, 'application/pdf'),
                'identitas_pendaftar' => UploadedFile::fake()->create('identitas.pdf', 100, 'application/pdf'),
            ],
            'password' => 'password',
            'password_confirmation' => 'password',
            'persetujuan' => '1',
        ], $override);
    }

    private function dataKomunitas(array $override = []): array
    {
        return array_merge([
            'role' => 'komunitas',
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

    private function dataAkademisi(array $override = []): array
    {
        return array_merge([
            'role' => 'akademisi',
            'name' => 'Dr. Maya Sari',
            'email' => 'maya@kampus-contoh.test',
            'nomor_telepon' => '081333334444',
            'nama_institusi' => 'Universitas Contoh',
            'fakultas_unit_jurusan' => 'Fakultas Pariwisata',
            'bidang_keahlian' => 'Manajemen Event',
            'tautan_profil_riset' => 'https://scholar.example.test/maya',
            'documents' => [
                'identitas_institusi' => UploadedFile::fake()->create('kartu-institusi.pdf', 100, 'application/pdf'),
                'identitas_pendaftar' => UploadedFile::fake()->create('ktp-akademisi.pdf', 100, 'application/pdf'),
            ],
            'password' => 'password',
            'password_confirmation' => 'password',
            'persetujuan' => '1',
        ], $override);
    }

    private function dataMedia(array $override = []): array
    {
        return array_merge([
            'role' => 'media',
            'name' => 'Andi Pratama',
            'email' => 'andi@media-contoh.test',
            'nomor_telepon' => '081344445555',
            'nama_media' => 'Warta Bandung',
            'nama_pemimpin_redaksi' => 'Andi Pratama',
            'wilayah_liputan' => 'Bandung Raya',
            'jenis_media' => 'Online',
            'documents' => [
                'kartu_pers' => UploadedFile::fake()->create('kartu-pers.pdf', 100, 'application/pdf'),
                'identitas_pemimpin' => UploadedFile::fake()->create('identitas-pemimpin.pdf', 100, 'application/pdf'),
                'contoh_publikasi' => UploadedFile::fake()->create('publikasi.pdf', 100, 'application/pdf'),
            ],
            'password' => 'password',
            'password_confirmation' => 'password',
            'persetujuan' => '1',
        ], $override);
    }

    public function test_registration_screen_can_be_rendered(): void
    {
        $this->get(route('register'))->assertOk();
    }

    public function test_registration_screen_offers_all_registrable_roles(): void
    {
        $this->get(route('register'))
            ->assertInertia(fn ($page) => $page
                ->component('auth/Register')
                ->has('roles', 7)
                ->where('roles.0.value', 'eo')
                ->where('roles.1.value', 'vendor')
                ->where('roles.2.value', 'pemda')
                ->where('roles.3.value', 'komunitas')
                ->where('roles.4.value', 'akademisi')
                ->where('roles.5.value', 'media')
                ->where('roles.6.value', 'pengunjung')
                ->has('roleInfo.vendor.title')
                ->has('roleInfo.pengunjung.title')
                ->has('documentRequirements.vendor', 4)
                ->has('documentRequirements.eo', 4));
    }

    public function test_eo_can_register_and_waits_for_verification(): void
    {
        $this->daftar($this->dataEo())
            ->assertCreated();

        $this->assertGuest();

        $akun = User::where('email', 'budi@eo-contoh.test')->firstOrFail();
        $this->assertSame(User::STATUS_MENUNGGU, $akun->status_akun);
        $this->assertNotSame('password', $akun->password);

        $this->assertDatabaseHas('eo', [
            'akun_id' => $akun->id,
            'nama_eo_organisasi' => 'Budi Event Organizer',
            'status_verifikasi' => Eo::STATUS_MENUNGGU,
        ]);
        $this->assertDatabaseCount('dokumen_persyaratan', 3);
        $this->assertDatabaseHas('dokumen_persyaratan', [
            'akun_id' => $akun->id,
            'jenis_dokumen' => 'KTP Penanggung Jawab',
            'sifat_dokumen' => 'wajib',
        ]);
    }

    public function test_eo_registration_requires_account_and_eo_data(): void
    {
        $this->daftar($this->dataEo([
            'name' => '',
            'email' => 'bukan-email',
            'nomor_telepon' => '',
            'nama_eo_organisasi' => '',
        ]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name', 'email', 'nomor_telepon', 'nama_eo_organisasi']);

        $this->assertDatabaseCount('users', 0);
        $this->assertDatabaseCount('eo', 0);
    }

    public function test_eo_requires_three_mandatory_documents(): void
    {
        $data = $this->dataEo();
        unset($data['documents']['profil_legalitas_eo']);

        $this->daftar($data)
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['documents.profil_legalitas_eo']);

        $this->assertDatabaseCount('users', 0);
        $this->assertDatabaseCount('dokumen_persyaratan', 0);
    }

    public function test_eo_can_upload_either_nib_or_npwp_in_one_optional_field(): void
    {
        $data = $this->dataEo();
        $data['documents']['nib_npwp'] = UploadedFile::fake()->create('nib-atau-npwp.pdf', 100, 'application/pdf');

        $this->daftar($data)->assertCreated();

        $akun = User::where('email', 'budi@eo-contoh.test')->firstOrFail();
        $this->assertDatabaseCount('dokumen_persyaratan', 4);
        $this->assertDatabaseHas('dokumen_persyaratan', [
            'akun_id' => $akun->id,
            'jenis_dokumen' => 'NIB atau NPWP (unggah salah satu)',
            'sifat_dokumen' => 'opsional',
        ]);
    }

    public function test_visitor_registration_creates_active_account_without_profile_or_documents(): void
    {
        $this->daftar($this->dataPengunjung())
            ->assertCreated();

        $this->assertGuest();

        $akun = User::where('email', 'jamal@pengunjung-contoh.test')->firstOrFail();

        $this->assertSame(User::ROLE_PENGUNJUNG, $akun->role);
        $this->assertSame(User::STATUS_AKTIF, $akun->status_akun);
        $this->assertDatabaseCount('dokumen_persyaratan', 0);
        $this->assertDatabaseCount('eo', 0);
        $this->assertDatabaseCount('vendor_umkm', 0);

        $this->postJson('/api/v1/login', [
            'email' => $akun->email,
            'password' => 'password',
        ])->assertOk()->assertJsonPath('redirect', route('pengunjung.dashboard'));

        $this->assertAuthenticatedAs($akun);
    }

    public function test_registration_rejects_duplicate_email_and_unconfirmed_password(): void
    {
        User::factory()->create(['email' => 'budi@eo-contoh.test']);

        $this->daftar($this->dataEo([
            'password_confirmation' => 'beda',
        ]))->assertJsonValidationErrors(['email', 'password']);

        $this->assertDatabaseCount('eo', 0);
    }

    public function test_vendor_can_register_with_linked_profile(): void
    {
        $this->daftar($this->dataVendor())
            ->assertCreated();

        $this->assertGuest();
        $akun = User::where('email', 'siti@vendor-contoh.test')->firstOrFail();

        $this->assertSame(User::ROLE_VENDOR, $akun->role);
        $this->assertSame(User::STATUS_MENUNGGU, $akun->status_akun);
        $this->assertDatabaseHas('vendor_umkm', [
            'akun_id' => $akun->id,
            'nama_usaha' => 'Kopi Siti',
            'nama_pemilik' => 'Siti Aminah',
            'status_verifikasi' => VendorUmkm::STATUS_MENUNGGU,
        ]);
        $this->assertTrue($akun->vendor->is(VendorUmkm::firstOrFail()));
        $this->assertDatabaseCount('dokumen_persyaratan', 4);
        $this->assertCount(4, Storage::disk('local')->allFiles('dokumen-persyaratan/'.$akun->id));
    }

    public function test_vendor_registration_requires_business_data(): void
    {
        $this->daftar($this->dataVendor([
            'nama_usaha' => '',
            'nama_pemilik' => '',
        ]))->assertJsonValidationErrors(['nama_usaha', 'nama_pemilik']);

        $this->assertDatabaseCount('vendor_umkm', 0);
    }

    public function test_unsupported_or_missing_role_is_rejected(): void
    {
        $this->daftar($this->dataEo(['role' => 'admin']))
            ->assertJsonValidationErrors('role');

        $this->daftar($this->dataEo(['role' => '']))
            ->assertJsonValidationErrors('role');

        $this->assertDatabaseCount('users', 0);
    }

    public function test_email_is_stored_lowercase(): void
    {
        $this->daftar($this->dataEo([
            'email' => 'Budi@EO-Contoh.test',
        ]));

        $this->assertDatabaseHas('users', [
            'email' => 'budi@eo-contoh.test',
        ]);
    }

    public function test_pemda_can_register_with_linked_profile(): void
    {
        $this->daftar($this->dataPemda())
            ->assertCreated();

        $this->assertGuest();
        $akun = User::where('email', 'rina@dinas-contoh.test')->firstOrFail();

        $this->assertSame(User::ROLE_PEMDA, $akun->role);
        $this->assertSame(User::STATUS_MENUNGGU, $akun->status_akun);
        $this->assertDatabaseHas('pemerintah_daerah', [
            'akun_id' => $akun->id,
            'nama_instansi' => 'Dinas Pariwisata Kota Bandung',
            'jabatan_pendaftar' => 'Kepala Seksi Perizinan',
            'wilayah_kerja' => 'Kota Bandung',
            'bidang_yang_ditangani' => 'Pariwisata',
            'status_verifikasi' => PemerintahDaerah::STATUS_MENUNGGU,
        ]);
        $this->assertTrue($akun->pemerintahDaerah->is(PemerintahDaerah::firstOrFail()));
        $this->assertDatabaseCount('dokumen_persyaratan', 2);
    }

    public function test_pemda_registration_requires_instansi_position_and_region(): void
    {
        $this->daftar($this->dataPemda([
            'nama_instansi' => '',
            'jabatan_pendaftar' => '',
            'wilayah_kerja' => '',
        ]))->assertJsonValidationErrors([
            'nama_instansi', 'jabatan_pendaftar', 'wilayah_kerja',
        ]);

        $this->assertDatabaseCount('users', 0);
        $this->assertDatabaseCount('pemerintah_daerah', 0);
    }

    public function test_komunitas_can_register_with_linked_profile(): void
    {
        $this->daftar($this->dataKomunitas())
            ->assertCreated();

        $this->assertGuest();
        $akun = User::where('email', 'dedi@komunitas-contoh.test')->firstOrFail();

        $this->assertSame(User::ROLE_KOMUNITAS, $akun->role);
        $this->assertSame(User::STATUS_MENUNGGU, $akun->status_akun);
        $this->assertDatabaseHas('komunitas_lokal', [
            'akun_id' => $akun->id,
            'nama_komunitas' => 'Komunitas Seni Angklung',
            'perkiraan_jumlah_anggota' => 25,
            'wilayah_kegiatan' => 'Kota Bandung',
            'bidang_kegiatan' => 'Seni & Budaya',
            'status_verifikasi' => KomunitasLokal::STATUS_MENUNGGU,
        ]);
        $this->assertTrue($akun->komunitasLokal->is(KomunitasLokal::firstOrFail()));
        $this->assertDatabaseCount('dokumen_persyaratan', 3);
    }

    public function test_komunitas_registration_requires_name_and_activity_type(): void
    {
        $this->daftar($this->dataKomunitas([
            'nama_komunitas' => '',
            'bidang_kegiatan' => '',
        ]))->assertJsonValidationErrors(['nama_komunitas', 'bidang_kegiatan']);

        $this->assertDatabaseCount('komunitas_lokal', 0);
    }

    public function test_akademisi_can_register_with_linked_profile(): void
    {
        $this->daftar($this->dataAkademisi())
            ->assertCreated();

        $this->assertGuest();
        $akun = User::where('email', 'maya@kampus-contoh.test')->firstOrFail();

        $this->assertSame(User::ROLE_AKADEMISI, $akun->role);
        $this->assertSame(User::STATUS_MENUNGGU, $akun->status_akun);
        $this->assertDatabaseHas('akademisi', [
            'akun_id' => $akun->id,
            'nama_institusi' => 'Universitas Contoh',
            'fakultas_unit_jurusan' => 'Fakultas Pariwisata',
            'bidang_keahlian' => 'Manajemen Event',
            'tautan_profil_riset' => 'https://scholar.example.test/maya',
            'status_verifikasi' => Akademisi::STATUS_MENUNGGU,
        ]);
        $this->assertTrue($akun->akademisi->is(Akademisi::firstOrFail()));
        $this->assertDatabaseCount('dokumen_persyaratan', 2);
    }

    public function test_akademisi_registration_requires_institution_and_expertise(): void
    {
        $this->daftar($this->dataAkademisi([
            'nama_institusi' => '',
            'bidang_keahlian' => '',
        ]))->assertJsonValidationErrors(['nama_institusi', 'bidang_keahlian']);

        $this->assertDatabaseCount('akademisi', 0);
    }

    public function test_media_can_register_with_linked_profile(): void
    {
        $this->daftar($this->dataMedia())
            ->assertCreated();

        $this->assertGuest();
        $akun = User::where('email', 'andi@media-contoh.test')->firstOrFail();

        $this->assertSame(User::ROLE_MEDIA, $akun->role);
        $this->assertSame(User::STATUS_MENUNGGU, $akun->status_akun);
        $this->assertDatabaseHas('media', [
            'akun_id' => $akun->id,
            'nama_media' => 'Warta Bandung',
            'nama_pemimpin_redaksi' => 'Andi Pratama',
            'wilayah_liputan' => 'Bandung Raya',
            'jenis_media' => 'Online',
            'status_verifikasi' => Media::STATUS_MENUNGGU,
        ]);
        $this->assertTrue($akun->media->is(Media::firstOrFail()));
        $this->assertDatabaseCount('dokumen_persyaratan', 3);
    }

    public function test_media_registration_requires_name_and_media_type(): void
    {
        $this->daftar($this->dataMedia([
            'nama_media' => '',
            'jenis_media' => '',
        ]))->assertJsonValidationErrors(['nama_media', 'jenis_media']);

        $this->assertDatabaseCount('media', 0);
    }

    public function test_vendor_required_documents_must_be_uploaded(): void
    {
        $data = $this->dataVendor();
        unset($data['documents']['nib']);

        $this->daftar($data)
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['documents.nib']);

        $this->assertDatabaseCount('users', 0);
        $this->assertDatabaseCount('dokumen_persyaratan', 0);
    }

    public function test_fields_of_another_role_are_not_required_or_saved(): void
    {
        $this->daftar($this->dataPemda())
            ->assertSessionHasNoErrors();

        $this->assertDatabaseCount('eo', 0);
        $this->assertDatabaseCount('vendor_umkm', 0);
        $this->assertDatabaseCount('komunitas_lokal', 0);
        $this->assertDatabaseCount('akademisi', 0);
        $this->assertDatabaseCount('media', 0);
    }
}
