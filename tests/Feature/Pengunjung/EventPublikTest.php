<?php

namespace Tests\Feature\Pengunjung;

use App\Models\Eo;
use App\Models\Event;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
}
