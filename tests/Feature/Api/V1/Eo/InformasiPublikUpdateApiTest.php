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
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

// PBI-036: EO pemilik memperbarui informasi publik event (rundown, layout/dokumen,
// regulasi, kontak darurat) dan perubahan langsung terbaca di papan informasi publik.
class InformasiPublikUpdateApiTest extends TestCase
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

    private function buatRundown(Event $event, array $override = []): Timeline
    {
        return Timeline::create(array_merge([
            'event_id' => $event->event_id,
            'judul_aktivitas' => 'Judul Lama',
            'deskripsi' => 'Deskripsi lama.',
            'waktu_mulai' => today()->setTime(8, 0),
            'waktu_selesai' => today()->setTime(9, 0),
        ], $override));
    }

    private function buatDokumen(Event $event, User $user, array $override = []): DokumenEvent
    {
        return DokumenEvent::create(array_merge([
            'event_id' => $event->event_id,
            'pengunggah_akun_id' => $user->id,
            'nama_dokumen' => 'Denah Lama.pdf',
            'jenis' => DokumenEvent::JENIS_LAYOUT,
            'lokasi_file' => "dokumen-event/{$event->event_id}/lama.pdf",
            'tanggal_upload' => now(),
        ], $override));
    }

    public function test_rundown_dapat_diperbarui_dan_tampil_di_papan_informasi_publik(): void
    {
        [$user, $eo] = $this->eoAktif();
        $event = $this->buatEvent($eo);
        $rundown = $this->buatRundown($event);

        $this->actingAs($user)
            ->putJson("/api/v1/eo/events/{$event->event_id}/rundown/{$rundown->timeline_id}", [
                'judul_aktivitas' => 'Pembukaan Resmi',
                'deskripsi' => 'Sambutan panitia dan tokoh daerah.',
                'waktu_mulai' => today()->setTime(7, 30)->toDateTimeString(),
                'waktu_selesai' => today()->setTime(9, 0)->toDateTimeString(),
            ])
            ->assertOk()
            ->assertJsonPath('message', 'Rundown berhasil diperbarui.')
            ->assertJsonPath('data.judul', 'Pembukaan Resmi');

        $this->assertDatabaseHas('timeline', [
            'timeline_id' => $rundown->timeline_id,
            'judul_aktivitas' => 'Pembukaan Resmi',
        ]);

        // Bukti AC: perubahan langsung terbaca pengunjung tanpa menunggu event baru.
        $this->getJson("/api/v1/events/{$event->event_id}")
            ->assertOk()
            ->assertJsonPath('data.rundown.0.judul', 'Pembukaan Resmi')
            ->assertJsonPath('data.rundown.0.deskripsi', 'Sambutan panitia dan tokoh daerah.');

        // Papan informasi yang benar-benar dilihat pengunjung (halaman Inertia).
        $this->get(route('events.show', $event->event_id))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Event/Show')
                ->where('event.rundown.0.judul', 'Pembukaan Resmi'));
    }

    public function test_rundown_milik_event_lain_tidak_dapat_diperbarui(): void
    {
        [$user, $eo] = $this->eoAktif();
        [, $eoLain] = $this->eoAktif();
        $event = $this->buatEvent($eo);
        $eventLain = $this->buatEvent($eoLain);
        $rundown = $this->buatRundown($eventLain);

        $this->actingAs($user)
            ->putJson("/api/v1/eo/events/{$event->event_id}/rundown/{$rundown->timeline_id}", [
                'judul_aktivitas' => 'Dibajak',
                'waktu_mulai' => today()->setTime(9, 0)->toDateTimeString(),
            ])
            ->assertNotFound();

        $this->assertDatabaseHas('timeline', [
            'timeline_id' => $rundown->timeline_id,
            'judul_aktivitas' => 'Judul Lama',
        ]);
    }

    public function test_kontak_darurat_dapat_diperbarui_tanpa_menghitung_ulang_kuota(): void
    {
        [$user, $eo] = $this->eoAktif();
        $event = $this->buatEvent($eo);
        $kontak = KontakDaruratEvent::create([
            'event_id' => $event->event_id,
            'nama' => 'Pos Lama',
            'nomor_telepon' => '081200000001',
        ]);
        KontakDaruratEvent::create([
            'event_id' => $event->event_id,
            'nama' => 'Koordinator Medis',
            'nomor_telepon' => '081200000002',
        ]);

        $this->actingAs($user)
            ->putJson("/api/v1/eo/events/{$event->event_id}/kontak-darurat/{$kontak->kontak_darurat_event_id}", [
                'nama' => 'Pos Utama',
                'nomor_telepon' => '081200000009',
            ])
            ->assertOk()
            ->assertJsonPath('data.nama', 'Pos Utama')
            ->assertJsonPath('data.nomor_telepon', '081200000009');

        // Kuota dua kontak tidak bertambah hanya karena salah satunya diubah.
        $this->actingAs($user)
            ->postJson("/api/v1/eo/events/{$event->event_id}/kontak-darurat", [
                'nama' => 'Kontak Ketiga',
                'nomor_telepon' => '081200000003',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['nama']);

        $this->getJson("/api/v1/events/{$event->event_id}")
            ->assertOk()
            ->assertJsonPath('data.kontak_darurat.0.nama', 'Pos Utama');
    }

    public function test_dokumen_dapat_diubah_tanpa_mengganti_berkas(): void
    {
        Storage::fake('local');

        [$user, $eo] = $this->eoAktif();
        $event = $this->buatEvent($eo);
        Storage::disk('local')->put("dokumen-event/{$event->event_id}/lama.pdf", 'isi lama');
        $dokumen = $this->buatDokumen($event, $user);

        $this->actingAs($user)
            ->putJson("/api/v1/eo/events/{$event->event_id}/dokumen/{$dokumen->dokumen_event_id}", [
                'nama_dokumen' => 'Denah Baru.pdf',
                'jenis' => DokumenEvent::JENIS_DOKUMEN,
            ])
            ->assertOk()
            ->assertJsonPath('message', 'Dokumen berhasil diperbarui.')
            ->assertJsonPath('data.nama', 'Denah Baru.pdf')
            ->assertJsonPath('data.jenis', 'dokumen');

        $segar = $dokumen->fresh();

        $this->assertSame("dokumen-event/{$event->event_id}/lama.pdf", $segar->lokasi_file);
        Storage::disk('local')->assertExists($segar->lokasi_file);

        $this->getJson("/api/v1/events/{$event->event_id}")
            ->assertOk()
            ->assertJsonPath('data.dokumen.0.nama', 'Denah Baru.pdf');
    }

    public function test_mengganti_berkas_dokumen_menghapus_berkas_lama(): void
    {
        Storage::fake('local');

        [$user, $eo] = $this->eoAktif();
        $event = $this->buatEvent($eo);
        $lokasiLama = "dokumen-event/{$event->event_id}/lama.pdf";
        Storage::disk('local')->put($lokasiLama, 'isi lama');
        $dokumen = $this->buatDokumen($event, $user, ['lokasi_file' => $lokasiLama]);

        $this->actingAs($user)
            ->putJson("/api/v1/eo/events/{$event->event_id}/dokumen/{$dokumen->dokumen_event_id}", [
                'nama_dokumen' => 'Denah Revisi.pdf',
                'jenis' => DokumenEvent::JENIS_LAYOUT,
                'file' => UploadedFile::fake()->create('denah-revisi.pdf', 200, 'application/pdf'),
            ])
            ->assertOk();

        $lokasiBaru = $dokumen->fresh()->lokasi_file;

        $this->assertNotSame($lokasiLama, $lokasiBaru);
        Storage::disk('local')->assertMissing($lokasiLama);
        Storage::disk('local')->assertExists($lokasiBaru);
    }

    public function test_dokumen_milik_event_lain_tidak_dapat_diubah(): void
    {
        [$user, $eo] = $this->eoAktif();
        [, $eoLain] = $this->eoAktif();
        $event = $this->buatEvent($eo);
        $eventLain = $this->buatEvent($eoLain);
        $dokumen = $this->buatDokumen($eventLain, $user);

        $this->actingAs($user)
            ->putJson("/api/v1/eo/events/{$event->event_id}/dokumen/{$dokumen->dokumen_event_id}", [
                'nama_dokumen' => 'Dibajak.pdf',
                'jenis' => DokumenEvent::JENIS_DOKUMEN,
            ])
            ->assertNotFound();

        $this->assertDatabaseHas('dokumen_event', [
            'dokumen_event_id' => $dokumen->dokumen_event_id,
            'nama_dokumen' => 'Denah Lama.pdf',
        ]);
    }

    public function test_regulasi_dapat_diperbarui_dan_tampil_di_papan_informasi_publik(): void
    {
        [$user, $eo] = $this->eoAktif();
        $event = $this->buatEvent($eo, ['informasi_regulasi' => 'Regulasi lama.']);

        $this->actingAs($user)
            ->putJson("/api/v1/eo/events/{$event->event_id}/informasi-regulasi", [
                'informasi_regulasi' => 'Dilarang membawa benda tajam dan hewan peliharaan.',
            ])
            ->assertOk()
            ->assertJsonPath('message', 'Informasi & regulasi berhasil diperbarui.')
            ->assertJsonPath(
                'data.informasi_regulasi',
                'Dilarang membawa benda tajam dan hewan peliharaan.',
            );

        $this->getJson("/api/v1/events/{$event->event_id}")
            ->assertOk()
            ->assertJsonPath(
                'data.informasi_regulasi',
                'Dilarang membawa benda tajam dan hewan peliharaan.',
            );
    }

    public function test_regulasi_dapat_dikosongkan(): void
    {
        [$user, $eo] = $this->eoAktif();
        $event = $this->buatEvent($eo, ['informasi_regulasi' => 'Regulasi lama.']);

        $this->actingAs($user)
            ->putJson("/api/v1/eo/events/{$event->event_id}/informasi-regulasi", [
                'informasi_regulasi' => null,
            ])
            ->assertOk()
            ->assertJsonPath('data.informasi_regulasi', null);

        $this->assertNull($event->fresh()->informasi_regulasi);
    }

    public function test_event_ditutup_tidak_dapat_diperbarui(): void
    {
        [$user, $eo] = $this->eoAktif();
        $event = $this->buatEvent($eo, ['status_event' => Event::STATUS_DITUTUP]);
        $rundown = $this->buatRundown($event);
        $kontak = KontakDaruratEvent::create([
            'event_id' => $event->event_id,
            'nama' => 'Pos Utama',
            'nomor_telepon' => '081200000001',
        ]);
        $dokumen = $this->buatDokumen($event, $user);

        $this->actingAs($user)
            ->putJson("/api/v1/eo/events/{$event->event_id}/rundown/{$rundown->timeline_id}", [
                'judul_aktivitas' => 'Setelah Ditutup',
                'waktu_mulai' => today()->setTime(9, 0)->toDateTimeString(),
            ])
            ->assertForbidden();

        $this->actingAs($user)
            ->putJson("/api/v1/eo/events/{$event->event_id}/kontak-darurat/{$kontak->kontak_darurat_event_id}", [
                'nama' => 'Setelah Ditutup',
                'nomor_telepon' => '081200000002',
            ])
            ->assertForbidden();

        $this->actingAs($user)
            ->putJson("/api/v1/eo/events/{$event->event_id}/dokumen/{$dokumen->dokumen_event_id}", [
                'nama_dokumen' => 'Setelah Ditutup.pdf',
                'jenis' => DokumenEvent::JENIS_DOKUMEN,
            ])
            ->assertForbidden();

        $this->actingAs($user)
            ->putJson("/api/v1/eo/events/{$event->event_id}/informasi-regulasi", [
                'informasi_regulasi' => 'Setelah ditutup.',
            ])
            ->assertForbidden();
    }

    public function test_eo_lain_tidak_dapat_memperbarui_informasi_publik(): void
    {
        [, $eoPemilik] = $this->eoAktif();
        [$userLain] = $this->eoAktif();
        $event = $this->buatEvent($eoPemilik);
        $rundown = $this->buatRundown($event);

        $this->actingAs($userLain)
            ->putJson("/api/v1/eo/events/{$event->event_id}/rundown/{$rundown->timeline_id}", [
                'judul_aktivitas' => 'Bajakan',
                'waktu_mulai' => today()->setTime(9, 0)->toDateTimeString(),
            ])
            ->assertForbidden();

        $this->actingAs($userLain)
            ->putJson("/api/v1/eo/events/{$event->event_id}/informasi-regulasi", [
                'informasi_regulasi' => 'Bajakan',
            ])
            ->assertForbidden();
    }

    public function test_tamu_dan_non_eo_tidak_dapat_memperbarui(): void
    {
        [, $eo] = $this->eoAktif();
        $event = $this->buatEvent($eo);
        $rundown = $this->buatRundown($event);

        $this->putJson("/api/v1/eo/events/{$event->event_id}/rundown/{$rundown->timeline_id}", [
            'judul_aktivitas' => 'Tamu',
            'waktu_mulai' => today()->setTime(9, 0)->toDateTimeString(),
        ])->assertUnauthorized();

        $vendor = User::factory()->vendor()->create();

        $this->actingAs($vendor)
            ->putJson("/api/v1/eo/events/{$event->event_id}/informasi-regulasi", [
                'informasi_regulasi' => 'Vendor',
            ])
            ->assertForbidden();
    }

    public function test_validasi_pembaruan_gagal_422(): void
    {
        Storage::fake('local');

        [$user, $eo] = $this->eoAktif();
        $event = $this->buatEvent($eo);
        $rundown = $this->buatRundown($event);
        $kontak = KontakDaruratEvent::create([
            'event_id' => $event->event_id,
            'nama' => 'Pos Utama',
            'nomor_telepon' => '081200000001',
        ]);
        $dokumen = $this->buatDokumen($event, $user);

        $this->actingAs($user)
            ->putJson("/api/v1/eo/events/{$event->event_id}/rundown/{$rundown->timeline_id}", [
                'judul_aktivitas' => '',
                'waktu_mulai' => today()->setTime(10, 0)->toDateTimeString(),
                'waktu_selesai' => today()->setTime(9, 0)->toDateTimeString(),
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['judul_aktivitas', 'waktu_selesai']);

        $this->actingAs($user)
            ->putJson("/api/v1/eo/events/{$event->event_id}/kontak-darurat/{$kontak->kontak_darurat_event_id}", [
                'nama' => 'Pos Utama',
                'nomor_telepon' => 'bukan nomor',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['nomor_telepon']);

        $this->actingAs($user)
            ->putJson("/api/v1/eo/events/{$event->event_id}/dokumen/{$dokumen->dokumen_event_id}", [
                'nama_dokumen' => 'Skrip.exe',
                'jenis' => 'sembarang',
                'file' => UploadedFile::fake()->create('skrip.exe', 10),
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['jenis', 'file']);

        $this->actingAs($user)
            ->putJson("/api/v1/eo/events/{$event->event_id}/informasi-regulasi", [
                'informasi_regulasi' => str_repeat('a', 50001),
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['informasi_regulasi']);
    }

    public function test_perubahan_hanya_tampil_publik_setelah_event_dipublikasikan(): void
    {
        [$user, $eo] = $this->eoAktif();
        $event = $this->buatEvent($eo, ['status_event' => Event::STATUS_DRAFT]);
        $rundown = $this->buatRundown($event);

        $this->actingAs($user)
            ->putJson("/api/v1/eo/events/{$event->event_id}/rundown/{$rundown->timeline_id}", [
                'judul_aktivitas' => 'Rundown Draft Diperbarui',
                'waktu_mulai' => today()->setTime(9, 0)->toDateTimeString(),
            ])
            ->assertOk();

        // AC: papan informasi mengikuti status publikasi event.
        $this->getJson("/api/v1/events/{$event->event_id}")->assertNotFound();

        $event->update([
            'status_event' => Event::STATUS_DIPUBLIKASIKAN,
            'tanggal_diverifikasi' => now(),
        ]);

        $this->getJson("/api/v1/events/{$event->event_id}")
            ->assertOk()
            ->assertJsonPath('data.rundown.0.judul', 'Rundown Draft Diperbarui');
    }
}
