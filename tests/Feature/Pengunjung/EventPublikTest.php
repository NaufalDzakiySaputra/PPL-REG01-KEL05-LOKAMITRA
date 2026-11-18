<?php

namespace Tests\Feature\Pengunjung;

use App\Models\DokumenEvent;
use App\Models\Eo;
use App\Models\Event;
use App\Models\KontakDaruratEvent;
use App\Models\Timeline;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

// F-06 Informasi Event Publik: daftar dan detail event dapat diakses tanpa login,
// hanya menampilkan event yang dipublikasikan dan belum lewat tanggal selesai.
class EventPublikTest extends TestCase
{
    use RefreshDatabase;

    private function buatEvent(string $status, int $selesaiDalamHari = 30): Event
    {
        $akun = User::factory()->eo()->create();
        $eo = Eo::create([
            'akun_id' => $akun->id,
            'nama_eo_organisasi' => 'EO Uji',
            'status_verifikasi' => 'disetujui',
        ]);

        return Event::create([
            'eo_id' => $eo->eo_id,
            'nama_event' => 'Event Uji Uji Publik',
            'deskripsi' => 'Deskripsi event uji.',
            'lokasi' => 'Alun-alun, Banyumas',
            'kategori_event' => 'Festival Budaya',
            'tanggal_mulai' => today()->subDay(),
            'tanggal_selesai' => today()->addDays($selesaiDalamHari),
            'status_event' => $status,
        ]);
    }

    public function test_pengunjung_tanpa_login_bisa_membuka_daftar_event(): void
    {
        $this->buatEvent(Event::STATUS_DIPUBLIKASIKAN);

        $this->get(route('events.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Event/Index')
                ->has('events.data', 1)
                ->has('filter')
                ->has('pilihan.kategori')
                ->has('pilihan.kota'));
    }

    public function test_daftar_publik_hanya_menampilkan_event_dipublikasikan(): void
    {
        $this->buatEvent(Event::STATUS_DIPUBLIKASIKAN);
        $this->buatEvent(Event::STATUS_DRAFT);
        $this->buatEvent(Event::STATUS_MENUNGGU_VERIFIKASI);
        $this->buatEvent(Event::STATUS_DITUTUP);
        $this->buatEvent(Event::STATUS_DIARSIPKAN);

        $this->get(route('events.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('events.data', 1)
                ->where('events.data.0.nama', 'Event Uji Uji Publik'));
    }

    public function test_event_yang_sudah_lewat_tanggal_selesai_tidak_tampil(): void
    {
        $this->buatEvent(Event::STATUS_DIPUBLIKASIKAN, -3);

        $this->get(route('events.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->has('events.data', 0));
    }

    public function test_detail_event_publik_bisa_dibuka_tanpa_login(): void
    {
        $event = $this->buatEvent(Event::STATUS_DIPUBLIKASIKAN);

        $this->get(route('events.show', $event->event_id))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Event/Show')
                ->where('event.nama', 'Event Uji Uji Publik')
                ->where('event.deskripsi', 'Deskripsi event uji.')
                ->has('event.tanggal_mulai')
                ->has('event.daftar_sampai'));
    }

    public function test_detail_event_non_publik_mengembalikan_404(): void
    {
        foreach ([Event::STATUS_DRAFT, Event::STATUS_MENUNGGU_VERIFIKASI] as $status) {
            $event = $this->buatEvent($status);

            $this->get(route('events.show', $event->event_id))
                ->assertNotFound();

            $event->delete();
        }
    }

    public function test_detail_event_yang_sudah_berakhir_mengembalikan_404(): void
    {
        $event = $this->buatEvent(Event::STATUS_DIPUBLIKASIKAN, -1);

        $this->get(route('events.show', $event->event_id))
            ->assertNotFound();
    }

    public function test_detail_mengirim_rundown_urut_waktu_mulai(): void
    {
        $event = $this->buatEvent(Event::STATUS_DIPUBLIKASIKAN);

        // Dibuat tidak urut untuk memastikan service mengurutkannya.
        Timeline::create([
            'event_id' => $event->event_id,
            'judul_aktivitas' => 'Penutupan',
            'deskripsi' => 'Acara penutup.',
            'waktu_mulai' => today()->setTime(15, 0),
            'waktu_selesai' => today()->setTime(17, 0),
        ]);
        Timeline::create([
            'event_id' => $event->event_id,
            'judul_aktivitas' => 'Pembukaan',
            'deskripsi' => 'Sambutan panitia.',
            'waktu_mulai' => today()->setTime(8, 0),
            'waktu_selesai' => today()->setTime(9, 0),
        ]);

        $this->get(route('events.show', $event->event_id))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('event.rundown', 2)
                ->where('event.rundown.0.judul', 'Pembukaan')
                ->where('event.rundown.1.judul', 'Penutupan'));
    }

    public function test_detail_tanpa_rundown_dokumen_kontak_tetap_aman(): void
    {
        $event = $this->buatEvent(Event::STATUS_DIPUBLIKASIKAN);

        $this->get(route('events.show', $event->event_id))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('event.rundown', 0)
                ->has('event.dokumen', 0)
                ->has('event.kontak_darurat', 0));
    }

    public function test_detail_mengirim_maksimal_dua_kontak_darurat(): void
    {
        $event = $this->buatEvent(Event::STATUS_DIPUBLIKASIKAN);

        KontakDaruratEvent::create(['event_id' => $event->event_id, 'nama' => 'Pos Utama', 'nomor_telepon' => '081111111101']);
        KontakDaruratEvent::create(['event_id' => $event->event_id, 'nama' => 'Koordinator Medis', 'nomor_telepon' => '081111111102']);
        KontakDaruratEvent::create(['event_id' => $event->event_id, 'nama' => 'Keamanan Lapangan', 'nomor_telepon' => '081111111103']);

        $this->get(route('events.show', $event->event_id))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('event.kontak_darurat', 2)
                ->where('event.kontak_darurat.0.nama', 'Pos Utama'));
    }

    public function test_dokumen_publik_bisa_diunduh_tanpa_login(): void
    {
        $event = $this->buatEvent(Event::STATUS_DIPUBLIKASIKAN);
        Storage::disk('public')->put('dokumen-event/denah-uji.pdf', 'isi pdf uji');

        $akun = User::whereHas('eo', fn ($q) => $q->where('eo_id', $event->eo_id))->firstOrFail();
        $dokumen = DokumenEvent::create([
            'event_id' => $event->event_id,
            'pengunggah_akun_id' => $akun->id,
            'nama_dokumen' => 'Denah Venue Uji.pdf',
            'lokasi_file' => 'dokumen-event/denah-uji.pdf',
            'tanggal_upload' => now(),
        ]);

        $this->get(route('events.dokumen', [$event->event_id, $dokumen->dokumen_event_id]))
            ->assertOk();

        Storage::disk('public')->delete('dokumen-event/denah-uji.pdf');
    }

    public function test_dokumen_dari_event_lain_mengembalikan_404(): void
    {
        $eventA = $this->buatEvent(Event::STATUS_DIPUBLIKASIKAN);
        $eventB = $this->buatEvent(Event::STATUS_DIPUBLIKASIKAN);
        Storage::disk('public')->put('dokumen-event/rahasia.pdf', 'isi');

        $akun = User::whereHas('eo', fn ($q) => $q->where('eo_id', $eventA->eo_id))->firstOrFail();
        $dokumen = DokumenEvent::create([
            'event_id' => $eventA->event_id,
            'pengunggah_akun_id' => $akun->id,
            'nama_dokumen' => 'Denah A.pdf',
            'lokasi_file' => 'dokumen-event/rahasia.pdf',
            'tanggal_upload' => now(),
        ]);

        // Dokumen milik event A diakses lewat event B.
        $this->get(route('events.dokumen', [$eventB->event_id, $dokumen->dokumen_event_id]))
            ->assertNotFound();

        Storage::disk('public')->delete('dokumen-event/rahasia.pdf');
    }

    public function test_dokumen_event_non_publik_mengembalikan_404(): void
    {
        $event = $this->buatEvent(Event::STATUS_DRAFT);
        Storage::disk('public')->put('dokumen-event/draft.pdf', 'isi');

        $akun = User::whereHas('eo', fn ($q) => $q->where('eo_id', $event->eo_id))->firstOrFail();
        $dokumen = DokumenEvent::create([
            'event_id' => $event->event_id,
            'pengunggah_akun_id' => $akun->id,
            'nama_dokumen' => 'Denah Draft.pdf',
            'lokasi_file' => 'dokumen-event/draft.pdf',
            'tanggal_upload' => now(),
        ]);

        $this->get(route('events.dokumen', [$event->event_id, $dokumen->dokumen_event_id]))
            ->assertNotFound();

        Storage::disk('public')->delete('dokumen-event/draft.pdf');
    }
}
