<?php

namespace Tests\Feature\Api\V1\Eo;

use App\Models\DokumenEvent;
use App\Models\Eo;
use App\Models\Event;
use App\Models\KontakDaruratEvent;
use App\Models\Timeline;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

// PBI-035: EO menyimpan rundown, layout/dokumen, dan kontak darurat lewat REST API.
class InformasiPublikEventApiTest extends TestCase
{
    use RefreshDatabase;

    private function eoAktif(): array
    {
        $user = User::factory()->eo()->create();
        $eo = Eo::create([
            'akun_id' => $user->id,
            'nama_eo_organisasi' => 'EO Banyumas Creative',
            'status_verifikasi' => Eo::STATUS_DISETUJUI,
        ]);

        return [$user, $eo];
    }

    private function buatEvent(Eo $eo, array $override = []): Event
    {
        return Event::create(array_merge([
            'eo_id' => $eo->eo_id,
            'nama_event' => 'Festival Budaya Banyumas',
            'kategori_event' => 'Festival Budaya',
            'tanggal_mulai' => now()->subDay()->toDateString(),
            'tanggal_selesai' => now()->addDays(3)->toDateString(),
            'lokasi' => 'Alun-alun, Banyumas',
            'deskripsi' => 'Deskripsi event.',
            'status_event' => Event::STATUS_DIPUBLIKASIKAN,
        ], $override));
    }

    public function test_rundown_yang_disimpan_eo_tampil_di_detail_publik(): void
    {
        [$user, $eo] = $this->eoAktif();
        $event = $this->buatEvent($eo);

        $this->actingAs($user)
            ->postJson("/api/v1/eo/events/{$event->event_id}/rundown", [
                'judul_aktivitas' => 'Pembukaan',
                'deskripsi' => 'Sambutan panitia.',
                'waktu_mulai' => now()->setTime(8, 0)->toDateTimeString(),
                'waktu_selesai' => now()->setTime(9, 0)->toDateTimeString(),
            ])
            ->assertCreated()
            ->assertJsonPath('message', 'Rundown berhasil ditambahkan.')
            ->assertJsonPath('data.judul', 'Pembukaan');

        // Bukti AC: informasi yang disimpan EO langsung terbaca pengunjung.
        $this->getJson("/api/v1/events/{$event->event_id}")
            ->assertOk()
            ->assertJsonPath('data.rundown.0.judul', 'Pembukaan');
    }

    public function test_rundown_publik_urut_waktu_mulai(): void
    {
        [$user, $eo] = $this->eoAktif();
        $event = $this->buatEvent($eo);

        Timeline::create([
            'event_id' => $event->event_id,
            'judul_aktivitas' => 'Penutupan',
            'waktu_mulai' => today()->setTime(15, 0),
            'waktu_selesai' => today()->setTime(17, 0),
        ]);
        Timeline::create([
            'event_id' => $event->event_id,
            'judul_aktivitas' => 'Pembukaan',
            'waktu_mulai' => today()->setTime(8, 0),
            'waktu_selesai' => today()->setTime(9, 0),
        ]);

        $this->getJson("/api/v1/events/{$event->event_id}")
            ->assertOk()
            ->assertJsonPath('data.rundown.0.judul', 'Pembukaan')
            ->assertJsonPath('data.rundown.1.judul', 'Penutupan');

        $this->actingAs($user)
            ->getJson("/api/v1/eo/events/{$event->event_id}/informasi-publik")
            ->assertOk()
            ->assertJsonPath('data.rundown.0.judul', 'Pembukaan');
    }

    public function test_rundown_dapat_diubah_dan_dihapus(): void
    {
        [$user, $eo] = $this->eoAktif();
        $event = $this->buatEvent($eo);
        $rundown = Timeline::create([
            'event_id' => $event->event_id,
            'judul_aktivitas' => 'Judul Lama',
            'waktu_mulai' => today()->setTime(8, 0),
        ]);

        $this->actingAs($user)
            ->putJson("/api/v1/eo/events/{$event->event_id}/rundown/{$rundown->timeline_id}", [
                'judul_aktivitas' => 'Judul Baru',
                'waktu_mulai' => today()->setTime(9, 0)->toDateTimeString(),
            ])
            ->assertOk()
            ->assertJsonPath('data.judul', 'Judul Baru');

        $this->actingAs($user)
            ->deleteJson("/api/v1/eo/events/{$event->event_id}/rundown/{$rundown->timeline_id}")
            ->assertOk();

        $this->assertDatabaseMissing('timeline', ['timeline_id' => $rundown->timeline_id]);
    }

    public function test_rundown_event_lain_tidak_dapat_diubah(): void
    {
        [$user, $eo] = $this->eoAktif();
        [, $eoLain] = $this->eoAktif();
        $event = $this->buatEvent($eo);
        $eventLain = $this->buatEvent($eoLain);
        $rundown = Timeline::create([
            'event_id' => $eventLain->event_id,
            'judul_aktivitas' => 'Milik EO Lain',
            'waktu_mulai' => today()->setTime(8, 0),
        ]);

        $this->actingAs($user)
            ->putJson("/api/v1/eo/events/{$event->event_id}/rundown/{$rundown->timeline_id}", [
                'judul_aktivitas' => 'Dibajak',
                'waktu_mulai' => today()->setTime(9, 0)->toDateTimeString(),
            ])
            ->assertNotFound();

        $this->assertDatabaseHas('timeline', [
            'timeline_id' => $rundown->timeline_id,
            'judul_aktivitas' => 'Milik EO Lain',
        ]);
    }

    public function test_validasi_rundown_gagal_422(): void
    {
        [$user, $eo] = $this->eoAktif();
        $event = $this->buatEvent($eo);

        $this->actingAs($user)
            ->postJson("/api/v1/eo/events/{$event->event_id}/rundown", [
                'judul_aktivitas' => '',
                'waktu_mulai' => today()->setTime(10, 0)->toDateTimeString(),
                'waktu_selesai' => today()->setTime(9, 0)->toDateTimeString(),
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['judul_aktivitas', 'waktu_selesai']);
    }

    public function test_upload_layout_dan_dokumen_tampil_di_publik(): void
    {
        Storage::fake('local');

        [$user, $eo] = $this->eoAktif();
        $event = $this->buatEvent($eo);

        $this->actingAs($user)
            ->postJson("/api/v1/eo/events/{$event->event_id}/dokumen", [
                'nama_dokumen' => 'Denah Venue.pdf',
                'jenis' => DokumenEvent::JENIS_LAYOUT,
                'file' => UploadedFile::fake()->create('denah.pdf', 100, 'application/pdf'),
            ])
            ->assertCreated()
            ->assertJsonPath('data.nama', 'Denah Venue.pdf')
            ->assertJsonPath('data.jenis', 'layout');

        $this->actingAs($user)
            ->postJson("/api/v1/eo/events/{$event->event_id}/dokumen", [
                'nama_dokumen' => 'Panduan Peserta.pdf',
                'jenis' => DokumenEvent::JENIS_DOKUMEN,
                'file' => UploadedFile::fake()->create('panduan.pdf', 100, 'application/pdf'),
            ])
            ->assertCreated();

        $this->getJson("/api/v1/events/{$event->event_id}")
            ->assertOk()
            ->assertJsonCount(2, 'data.dokumen')
            ->assertJsonPath('data.dokumen.0.jenis', 'layout')
            ->assertJsonPath('data.dokumen.1.jenis', 'dokumen')
            ->assertJsonPath(
                'data.dokumen.0.unduh_url',
                "/events/{$event->event_id}/dokumen/".DokumenEvent::first()->dokumen_event_id,
            );
    }

    public function test_upload_dokumen_format_tidak_didukung_422(): void
    {
        Storage::fake('local');

        [$user, $eo] = $this->eoAktif();
        $event = $this->buatEvent($eo);

        $this->actingAs($user)
            ->postJson("/api/v1/eo/events/{$event->event_id}/dokumen", [
                'nama_dokumen' => 'Skrip.exe',
                'jenis' => DokumenEvent::JENIS_DOKUMEN,
                'file' => UploadedFile::fake()->create('skrip.exe', 10),
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['file']);
    }

    public function test_hapus_dokumen_menghapus_berkas_di_disk(): void
    {
        Storage::fake('local');

        [$user, $eo] = $this->eoAktif();
        $event = $this->buatEvent($eo);

        $lokasi = UploadedFile::fake()
            ->create('denah.pdf', 10, 'application/pdf')
            ->store("dokumen-event/{$event->event_id}", 'local');

        $dokumen = DokumenEvent::create([
            'event_id' => $event->event_id,
            'pengunggah_akun_id' => $user->id,
            'nama_dokumen' => 'Denah.pdf',
            'jenis' => DokumenEvent::JENIS_LAYOUT,
            'lokasi_file' => $lokasi,
            'tanggal_upload' => now(),
        ]);

        $this->actingAs($user)
            ->deleteJson("/api/v1/eo/events/{$event->event_id}/dokumen/{$dokumen->dokumen_event_id}")
            ->assertOk();

        Storage::disk('local')->assertMissing($lokasi);
        $this->assertDatabaseMissing('dokumen_event', ['dokumen_event_id' => $dokumen->dokumen_event_id]);
    }

    public function test_kontak_darurat_dibatasi_maksimal_dua(): void
    {
        [$user, $eo] = $this->eoAktif();
        $event = $this->buatEvent($eo);

        foreach (['Pos Utama', 'Koordinator Medis'] as $i => $nama) {
            $this->actingAs($user)
                ->postJson("/api/v1/eo/events/{$event->event_id}/kontak-darurat", [
                    'nama' => $nama,
                    'nomor_telepon' => '08123456780'.($i + 1),
                ])
                ->assertCreated();
        }

        $this->actingAs($user)
            ->postJson("/api/v1/eo/events/{$event->event_id}/kontak-darurat", [
                'nama' => 'Keamanan Lapangan',
                'nomor_telepon' => '081234567803',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['nama']);

        $this->assertSame(2, KontakDaruratEvent::count());

        // Publik tetap hanya menerima dua kontak.
        $this->getJson("/api/v1/events/{$event->event_id}")
            ->assertOk()
            ->assertJsonCount(2, 'data.kontak_darurat');
    }

    public function test_kontak_darurat_dapat_diubah_dan_dihapus(): void
    {
        [$user, $eo] = $this->eoAktif();
        $event = $this->buatEvent($eo);
        $kontak = KontakDaruratEvent::create([
            'event_id' => $event->event_id,
            'nama' => 'Nama Lama',
            'nomor_telepon' => '081200000001',
        ]);

        $this->actingAs($user)
            ->putJson("/api/v1/eo/events/{$event->event_id}/kontak-darurat/{$kontak->kontak_darurat_event_id}", [
                'nama' => 'Nama Baru',
                'nomor_telepon' => '081200000002',
            ])
            ->assertOk()
            ->assertJsonPath('data.nama', 'Nama Baru');

        $this->actingAs($user)
            ->deleteJson("/api/v1/eo/events/{$event->event_id}/kontak-darurat/{$kontak->kontak_darurat_event_id}")
            ->assertOk();

        $this->assertDatabaseMissing('kontak_darurat_event', [
            'kontak_darurat_event_id' => $kontak->kontak_darurat_event_id,
        ]);
    }

    public function test_informasi_regulasi_tersimpan_lewat_update_event(): void
    {
        [$user, $eo] = $this->eoAktif();
        $event = $this->buatEvent($eo);

        $this->actingAs($user)
            ->putJson("/api/v1/eo/events/{$event->event_id}", [
                'nama_event' => $event->nama_event,
                'kategori_event' => $event->kategori_event,
                'tanggal_mulai' => $event->tanggal_mulai->toDateString(),
                'tanggal_selesai' => $event->tanggal_selesai->toDateString(),
                'lokasi' => $event->lokasi,
                'informasi_regulasi' => 'Dilarang membawa benda tajam.',
            ])
            ->assertOk();

        $this->getJson("/api/v1/events/{$event->event_id}")
            ->assertOk()
            ->assertJsonPath('data.informasi_regulasi', 'Dilarang membawa benda tajam.');
    }

    public function test_ringkasan_informasi_publik_untuk_eo(): void
    {
        [$user, $eo] = $this->eoAktif();
        $event = $this->buatEvent($eo, ['informasi_regulasi' => 'Regulasi uji.']);

        $this->actingAs($user)
            ->getJson("/api/v1/eo/events/{$event->event_id}/informasi-publik")
            ->assertOk()
            ->assertJsonPath('data.informasi_regulasi', 'Regulasi uji.')
            ->assertJsonPath('data.batas_kontak_darurat', 2)
            ->assertJsonPath('data.dapat_diubah', true);
    }

    public function test_eo_lain_ditolak_403(): void
    {
        [, $eoPemilik] = $this->eoAktif();
        [$userLain] = $this->eoAktif();
        $event = $this->buatEvent($eoPemilik);

        $this->actingAs($userLain)
            ->postJson("/api/v1/eo/events/{$event->event_id}/rundown", [
                'judul_aktivitas' => 'Rundown Bajakan',
                'waktu_mulai' => today()->setTime(8, 0)->toDateTimeString(),
            ])
            ->assertForbidden();

        $this->actingAs($userLain)
            ->getJson("/api/v1/eo/events/{$event->event_id}/informasi-publik")
            ->assertForbidden();
    }

    public function test_event_ditutup_tidak_dapat_ditambah_informasi(): void
    {
        [$user, $eo] = $this->eoAktif();
        $event = $this->buatEvent($eo, ['status_event' => Event::STATUS_DITUTUP]);

        $this->actingAs($user)
            ->postJson("/api/v1/eo/events/{$event->event_id}/rundown", [
                'judul_aktivitas' => 'Rundown Setelah Ditutup',
                'waktu_mulai' => today()->setTime(8, 0)->toDateTimeString(),
            ])
            ->assertForbidden();
    }

    public function test_tamu_dan_non_eo_ditolak(): void
    {
        [, $eo] = $this->eoAktif();
        $event = $this->buatEvent($eo);

        $this->postJson("/api/v1/eo/events/{$event->event_id}/rundown", [
            'judul_aktivitas' => 'Rundown Tamu',
            'waktu_mulai' => today()->setTime(8, 0)->toDateTimeString(),
        ])->assertUnauthorized();

        $vendor = User::factory()->vendor()->create();

        $this->actingAs($vendor)
            ->postJson("/api/v1/eo/events/{$event->event_id}/rundown", [
                'judul_aktivitas' => 'Rundown Vendor',
                'waktu_mulai' => today()->setTime(8, 0)->toDateTimeString(),
            ])
            ->assertForbidden();
    }
}
