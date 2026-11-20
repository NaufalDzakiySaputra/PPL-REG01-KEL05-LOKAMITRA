<?php

namespace Tests\Feature\Api\V1\Publik;

use App\Models\DokumenEvent;
use App\Models\Eo;
use App\Models\Event;
use App\Models\KontakDaruratEvent;
use App\Models\Timeline;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

// F-06 / PBI-035: informasi event publik lewat REST API tanpa login.
class EventPublikApiTest extends TestCase
{
    use RefreshDatabase;

    private function buatEvent(string $status, int $selesaiDalamHari = 30): Event
    {
        $akun = User::factory()->eo()->create();
        $eo = Eo::create([
            'akun_id' => $akun->id,
            'nama_eo_organisasi' => 'EO Uji',
            'status_verifikasi' => Eo::STATUS_DISETUJUI,
        ]);

        return Event::create([
            'eo_id' => $eo->eo_id,
            'nama_event' => 'Event Uji Publik',
            'deskripsi' => 'Deskripsi event uji.',
            'lokasi' => 'Alun-alun, Banyumas',
            'kategori_event' => 'Festival Budaya',
            'tanggal_mulai' => today()->subDay(),
            'tanggal_selesai' => today()->addDays($selesaiDalamHari),
            'status_event' => $status,
        ]);
    }

    public function test_daftar_event_publik_dapat_dibaca_tanpa_login(): void
    {
        $this->buatEvent(Event::STATUS_DIPUBLIKASIKAN);

        $this->getJson('/api/v1/events')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.nama', 'Event Uji Publik');
    }

    public function test_daftar_hanya_memuat_event_tersedia_umum(): void
    {
        $this->buatEvent(Event::STATUS_DIPUBLIKASIKAN);
        $this->buatEvent(Event::STATUS_DRAFT);
        $this->buatEvent(Event::STATUS_MENUNGGU_VERIFIKASI);
        $this->buatEvent(Event::STATUS_DITUTUP);
        $this->buatEvent(Event::STATUS_DIARSIPKAN);
        $this->buatEvent(Event::STATUS_DIPUBLIKASIKAN, -3);

        $this->getJson('/api/v1/events')
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_detail_event_publik_memuat_informasi_lengkap(): void
    {
        $event = $this->buatEvent(Event::STATUS_DIPUBLIKASIKAN);
        $event->update(['informasi_regulasi' => 'Tiket hanya berlaku sekali masuk.']);

        Timeline::create([
            'event_id' => $event->event_id,
            'judul_aktivitas' => 'Pembukaan',
            'deskripsi' => 'Sambutan panitia.',
            'waktu_mulai' => today()->setTime(8, 0),
            'waktu_selesai' => today()->setTime(9, 0),
        ]);

        DokumenEvent::create([
            'event_id' => $event->event_id,
            'pengunggah_akun_id' => $event->eo->akun_id,
            'nama_dokumen' => 'Denah Venue.pdf',
            'jenis' => DokumenEvent::JENIS_LAYOUT,
            'lokasi_file' => 'dokumen-event/denah.pdf',
            'tanggal_upload' => now(),
        ]);

        KontakDaruratEvent::create([
            'event_id' => $event->event_id,
            'nama' => 'Pos Utama',
            'nomor_telepon' => '081234567801',
        ]);

        $this->getJson("/api/v1/events/{$event->event_id}")
            ->assertOk()
            ->assertJsonPath('data.nama', 'Event Uji Publik')
            ->assertJsonPath('data.informasi_regulasi', 'Tiket hanya berlaku sekali masuk.')
            ->assertJsonPath('data.rundown.0.judul', 'Pembukaan')
            ->assertJsonPath('data.dokumen.0.nama', 'Denah Venue.pdf')
            ->assertJsonPath('data.dokumen.0.jenis', 'layout')
            ->assertJsonPath('data.kontak_darurat.0.nama', 'Pos Utama');
    }

    public function test_detail_event_non_publik_404(): void
    {
        foreach ([Event::STATUS_DRAFT, Event::STATUS_MENUNGGU_VERIFIKASI] as $status) {
            $event = $this->buatEvent($status);

            $this->getJson("/api/v1/events/{$event->event_id}")->assertNotFound();

            $event->delete();
        }
    }

    public function test_detail_event_yang_sudah_berakhir_404(): void
    {
        $event = $this->buatEvent(Event::STATUS_DIPUBLIKASIKAN, -1);

        $this->getJson("/api/v1/events/{$event->event_id}")->assertNotFound();
    }

    public function test_filter_kategori_dan_pencarian(): void
    {
        $this->buatEvent(Event::STATUS_DIPUBLIKASIKAN);

        $this->getJson('/api/v1/events?kategori=Festival%20Budaya')
            ->assertOk()
            ->assertJsonCount(1, 'data');

        $this->getJson('/api/v1/events?kategori=Konser')
            ->assertOk()
            ->assertJsonCount(0, 'data');

        $this->getJson('/api/v1/events?q=Tidak%20Ada')
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }
}
