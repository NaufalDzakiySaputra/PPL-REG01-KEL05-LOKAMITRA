<?php

namespace Tests\Feature\Media;

use App\Models\DokumenEvent;
use App\Models\Eo;
use App\Models\Event;
use App\Models\KontakDaruratEvent;
use App\Models\Timeline;
use App\Models\User;
use App\Support\FormatTanggal;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

// F-06 Informasi Event Publik (PBI-042): Media dapat melihat informasi
// event dan jadwal kegiatan, tetapi hanya untuk event yang sudah dipublikasikan.
class EventDetailTest extends TestCase
{
    use RefreshDatabase;

    private ?Eo $eo = null;

    private ?User $akunEo = null;

    private function media(): User
    {
        return User::factory()->media()->create();
    }

    private function eo(): Eo
    {
        if ($this->eo === null) {
            $this->akunEo = User::factory()->eo()->create();
            $this->eo = Eo::create([
                'akun_id' => $this->akunEo->id,
                'nama_eo_organisasi' => 'EO Uji',
                'website_media_sosial' => 'https://eo-uji.test',
                'alamat_kantor' => 'Jalan Merdeka No. 12, Banyumas',
                'status_verifikasi' => 'disetujui',
            ]);
        }

        return $this->eo;
    }

    private function buatEvent(string $status, int $selesaiDalamHari = 30): Event
    {
        return Event::create([
            'eo_id' => $this->eo()->eo_id,
            'nama_event' => 'Festival Budaya Banyumas',
            'deskripsi' => 'Deskripsi event uji.',
            'lokasi' => 'Alun-alun, Banyumas',
            'kategori_event' => 'Festival Budaya',
            'informasi_regulasi' => 'Dilarang membawa benda tajam.',
            'tanggal_mulai' => today()->subDay(),
            'tanggal_selesai' => today()->addDays($selesaiDalamHari),
            'status_event' => $status,
        ]);
    }

    public function test_media_melihat_daftar_event_terpublikasi(): void
    {
        $this->buatEvent(Event::STATUS_DIPUBLIKASIKAN);

        $this->actingAs($this->media())
            ->get(route('media.events.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('media/events/Index')
                ->has('events.data', 1)
                ->where('events.data.0.nama', 'Festival Budaya Banyumas')
                ->where('events.data.0.kategori', 'Festival Budaya')
                ->has('events.data.0.tanggal.hari')
                ->has('filter')
                ->has('pilihan.kategori')
                ->has('pilihan.kota'));
    }

    public function test_daftar_hanya_memuat_event_dipublikasikan_dan_belum_berakhir(): void
    {
        $this->buatEvent(Event::STATUS_DIPUBLIKASIKAN);
        $this->buatEvent(Event::STATUS_DRAFT);
        $this->buatEvent(Event::STATUS_MENUNGGU_VERIFIKASI);
        $this->buatEvent(Event::STATUS_DITUTUP);
        $this->buatEvent(Event::STATUS_DIARSIPKAN);
        $this->buatEvent(Event::STATUS_DIPUBLIKASIKAN, -3);

        $this->actingAs($this->media())
            ->get(route('media.events.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('events.data', 1)
                ->where('events.data.0.nama', 'Festival Budaya Banyumas'));
    }

    public function test_kartu_daftar_mengarah_ke_detail_area_media(): void
    {
        $event = $this->buatEvent(Event::STATUS_DIPUBLIKASIKAN);

        $this->actingAs($this->media())
            ->get(route('media.events.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where(
                    'events.data.0.href',
                    "/media/events/{$event->event_id}",
                ));
    }

    public function test_media_melihat_detail_event_dan_informasi_kegiatan(): void
    {
        $event = $this->buatEvent(Event::STATUS_DIPUBLIKASIKAN);

        $this->actingAs($this->media())
            ->get(route('media.events.show', $event->event_id))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('media/events/Show')
                ->where('event.id', $event->event_id)
                ->where('event.nama', 'Festival Budaya Banyumas')
                ->where('event.deskripsi', 'Deskripsi event uji.')
                ->where('event.lokasi', 'Alun-alun, Banyumas')
                ->where('event.kategori', 'Festival Budaya')
                ->where(
                    'event.informasi_regulasi',
                    'Dilarang membawa benda tajam.',
                )
                ->where(
                    'event.tanggal_mulai',
                    FormatTanggal::panjang($event->tanggal_mulai),
                )
                ->where(
                    'event.tanggal_selesai',
                    FormatTanggal::panjang($event->tanggal_selesai),
                )
                ->where('event.sedang_berjalan', true)
                ->has('event.daftar_sampai'));
    }

    public function test_detail_memuat_kontak_dan_identitas_penyelenggara(): void
    {
        $event = $this->buatEvent(Event::STATUS_DIPUBLIKASIKAN);

        KontakDaruratEvent::create([
            'event_id' => $event->event_id,
            'nama' => 'Humas EO Uji',
            'nomor_telepon' => '081200000077',
        ]);

        // AC: media dapat melihat kontak penyelenggara yang tersedia.
        $this->actingAs($this->media())
            ->get(route('media.events.show', $event->event_id))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('event.penyelenggara.nama', 'EO Uji')
                ->where(
                    'event.penyelenggara.website',
                    'https://eo-uji.test',
                )
                ->where(
                    'event.penyelenggara.alamat',
                    'Jalan Merdeka No. 12, Banyumas',
                )
                ->has('event.kontak_darurat', 1)
                ->where('event.kontak_darurat.0.nama', 'Humas EO Uji')
                ->where(
                    'event.kontak_darurat.0.nomor',
                    '081200000077',
                ));
    }

    public function test_detail_memuat_jadwal_dan_rundown_urut_waktu(): void
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

        // AC: media dapat melihat informasi event dan jadwal kegiatan.
        $this->actingAs($this->media())
            ->get(route('media.events.show', $event->event_id))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('event.rundown', 2)
                ->where('event.rundown.0.judul', 'Pembukaan')
                ->where('event.rundown.0.deskripsi', 'Sambutan panitia.')
                ->where(
                    'event.rundown.0.waktu_mulai',
                    FormatTanggal::denganJam(today()->setTime(8, 0)),
                )
                ->where('event.rundown.0.waktu_selesai', '09:00')
                ->where('event.rundown.1.judul', 'Penutupan')
                ->where('event.rundown.1.waktu_selesai', '17:00'));
    }

    public function test_detail_menampilkan_event_yang_dipilih_bukan_event_lain(): void
    {
        $lain = $this->buatEvent(Event::STATUS_DIPUBLIKASIKAN);
        $lain->update([
            'nama_event' => 'Pasar Rakyat Solo',
            'lokasi' => 'Solo, Jawa Tengah',
            'informasi_regulasi' => 'Wajib memakai alas kaki tertutup.',
            'tanggal_mulai' => today()->addDays(10),
            'tanggal_selesai' => today()->addDays(12),
        ]);

        $dipilih = $this->buatEvent(Event::STATUS_DIPUBLIKASIKAN);
        $dipilih->update([
            'nama_event' => 'Karnaval Banyumas',
            'lokasi' => 'Purwokerto, Banyumas',
            'informasi_regulasi' => 'Dilarang memarkir kendaraan di bahu jalan.',
            'tanggal_mulai' => today()->addDays(2),
            'tanggal_selesai' => today()->addDays(4),
        ]);

        Timeline::create([
            'event_id' => $dipilih->event_id,
            'judul_aktivitas' => 'Karnaval Keliling',
            'waktu_mulai' => today()->addDays(2)->setTime(9, 0),
            'waktu_selesai' => today()->addDays(2)->setTime(11, 0),
        ]);

        // AC: informasi yang ditampilkan sesuai dengan event yang dipilih.
        $this->actingAs($this->media())
            ->get(route('media.events.show', $dipilih->event_id))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('event.id', $dipilih->event_id)
                ->where('event.nama', 'Karnaval Banyumas')
                ->where('event.lokasi', 'Purwokerto, Banyumas')
                ->where(
                    'event.informasi_regulasi',
                    'Dilarang memarkir kendaraan di bahu jalan.',
                )
                ->where(
                    'event.tanggal_mulai',
                    FormatTanggal::panjang($dipilih->tanggal_mulai),
                )
                ->has('event.rundown', 1)
                ->where('event.rundown.0.judul', 'Karnaval Keliling'));
    }

    public function test_jadwal_dan_rundown_event_lain_tidak_ikut_tampil(): void
    {
        $lain = $this->buatEvent(Event::STATUS_DIPUBLIKASIKAN);
        $lain->update(['nama_event' => 'Pasar Rakyat Solo']);

        Timeline::create([
            'event_id' => $lain->event_id,
            'judul_aktivitas' => 'Bazar UMKM',
            'waktu_mulai' => today()->addDays(10)->setTime(8, 0),
            'waktu_selesai' => today()->addDays(10)->setTime(12, 0),
        ]);

        DokumenEvent::create([
            'event_id' => $lain->event_id,
            'pengunggah_akun_id' => $this->akunEo->id,
            'nama_dokumen' => 'Denah Pasar Rakyat.pdf',
            'lokasi_file' => 'dokumen-event/denah-pasar.pdf',
            'tanggal_upload' => now(),
        ]);

        KontakDaruratEvent::create([
            'event_id' => $lain->event_id,
            'nama' => 'Pos Keamanan Pasar',
            'nomor_telepon' => '081200000009',
        ]);

        $dipilih = $this->buatEvent(Event::STATUS_DIPUBLIKASIKAN);
        $dipilih->update(['nama_event' => 'Karnaval Banyumas']);

        Timeline::create([
            'event_id' => $dipilih->event_id,
            'judul_aktivitas' => 'Karnaval Keliling',
            'waktu_mulai' => today()->addDays(2)->setTime(9, 0),
            'waktu_selesai' => today()->addDays(2)->setTime(11, 0),
        ]);

        DokumenEvent::create([
            'event_id' => $dipilih->event_id,
            'pengunggah_akun_id' => $this->akunEo->id,
            'nama_dokumen' => 'Denah Karnaval.pdf',
            'lokasi_file' => 'dokumen-event/denah-karnaval.pdf',
            'tanggal_upload' => now(),
        ]);

        KontakDaruratEvent::create([
            'event_id' => $dipilih->event_id,
            'nama' => 'Pos Medis Karnaval',
            'nomor_telepon' => '081200000002',
        ]);

        // Jadwal, rundown, dokumen, dan kontak milik event lain tidak boleh ikut.
        $this->actingAs($this->media())
            ->get(route('media.events.show', $dipilih->event_id))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('event.id', $dipilih->event_id)
                ->has('event.rundown', 1)
                ->where('event.rundown.0.judul', 'Karnaval Keliling')
                ->has('event.dokumen', 1)
                ->where('event.dokumen.0.nama', 'Denah Karnaval.pdf')
                ->has('event.kontak_darurat', 1)
                ->where(
                    'event.kontak_darurat.0.nama',
                    'Pos Medis Karnaval',
                ));
    }

    public function test_setiap_kartu_daftar_menaut_ke_event_yang_sesuai(): void
    {
        $pertama = $this->buatEvent(Event::STATUS_DIPUBLIKASIKAN);
        $pertama->update([
            'nama_event' => 'Event Pertama',
            'tanggal_mulai' => today()->addDays(2),
        ]);

        $kedua = $this->buatEvent(Event::STATUS_DIPUBLIKASIKAN);
        $kedua->update([
            'nama_event' => 'Event Kedua',
            'tanggal_mulai' => today()->addDays(5),
        ]);

        $this->actingAs($this->media())
            ->get(route('media.events.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('events.data', 2)
                ->where('events.data.0.nama', 'Event Pertama')
                ->where(
                    'events.data.0.href',
                    "/media/events/{$pertama->event_id}",
                )
                ->where('events.data.1.nama', 'Event Kedua')
                ->where(
                    'events.data.1.href',
                    "/media/events/{$kedua->event_id}",
                ));
    }

    public function test_dokumen_event_dapat_diunduh_media_dari_halaman_detail(): void
    {
        Storage::fake('local');

        $event = $this->buatEvent(Event::STATUS_DIPUBLIKASIKAN);
        Storage::disk('local')->put('dokumen-event/denah-media.pdf', 'isi pdf uji');

        $dokumen = DokumenEvent::create([
            'event_id' => $event->event_id,
            'pengunggah_akun_id' => $this->akunEo->id,
            'nama_dokumen' => 'Denah Venue.pdf',
            'lokasi_file' => 'dokumen-event/denah-media.pdf',
            'tanggal_upload' => now(),
        ]);

        $this->actingAs($this->media())
            ->get(route('media.events.show', $event->event_id))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where(
                    'event.dokumen.0.unduh_url',
                    route('events.dokumen', [
                        $event->event_id,
                        $dokumen->dokumen_event_id,
                    ], false),
                ));

        $this->actingAs($this->media())
            ->get(route('events.dokumen', [
                $event->event_id,
                $dokumen->dokumen_event_id,
            ]))
            ->assertOk();
    }

    public function test_kontak_darurat_tampil_pada_detail_event(): void
    {
        $event = $this->buatEvent(Event::STATUS_DIPUBLIKASIKAN);

        KontakDaruratEvent::create([
            'event_id' => $event->event_id,
            'nama' => 'Pos Medis',
            'nomor_telepon' => '081200000001',
        ]);

        $this->actingAs($this->media())
            ->get(route('media.events.show', $event->event_id))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('event.kontak_darurat', 1)
                ->where('event.kontak_darurat.0.nama', 'Pos Medis'));
    }

    public function test_detail_event_non_publik_mengembalikan_404(): void
    {
        foreach ([
            Event::STATUS_DRAFT,
            Event::STATUS_MENUNGGU_VERIFIKASI,
            Event::STATUS_DITUTUP,
            Event::STATUS_DIARSIPKAN,
        ] as $status) {
            $event = $this->buatEvent($status);

            $this->actingAs($this->media())
                ->get(route('media.events.show', $event->event_id))
                ->assertNotFound();

            $event->delete();
        }
    }

    public function test_detail_event_yang_sudah_berakhir_mengembalikan_404(): void
    {
        $event = $this->buatEvent(Event::STATUS_DIPUBLIKASIKAN, -1);

        $this->actingAs($this->media())
            ->get(route('media.events.show', $event->event_id))
            ->assertNotFound();
    }

    public function test_peran_selain_media_tidak_boleh_mengakses_halaman(): void
    {
        $event = $this->buatEvent(Event::STATUS_DIPUBLIKASIKAN);

        $peran = [
            User::factory()->eo()->create(),
            User::factory()->admin()->create(),
            User::factory()->pemda()->create(),
            User::factory()->vendor()->create(),
            User::factory()->komunitas()->create(),
            User::factory()->akademisi()->create(),
            User::factory()->pengunjung()->create(),
        ];

        foreach ($peran as $user) {
            $this->actingAs($user)
                ->get(route('media.events.index'))
                ->assertForbidden();

            $this->actingAs($user)
                ->get(route('media.events.show', $event->event_id))
                ->assertForbidden();
        }
    }

    public function test_tamu_diarahkan_ke_halaman_login(): void
    {
        $event = $this->buatEvent(Event::STATUS_DIPUBLIKASIKAN);

        $this->get(route('media.events.index'))
            ->assertRedirect(route('login'));

        $this->get(route('media.events.show', $event->event_id))
            ->assertRedirect(route('login'));
    }

    public function test_detail_peran_lain_tidak_menerima_data_penyelenggara(): void
    {
        $event = $this->buatEvent(Event::STATUS_DIPUBLIKASIKAN);

        // Pengaman saat komponen bersama menambah blok penyelenggara opsional.
        $this->actingAs(User::factory()->akademisi()->create())
            ->get(route('akademisi.events.show', $event->event_id))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('akademisi/events/Show')
                ->missing('event.penyelenggara'));
    }

    public function test_menu_media_memuat_tautan_informasi_event(): void
    {
        $this->actingAs($this->media())
            ->get(route('media.dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('menu.items.1.label', 'Event')
                ->where('menu.items.1.href', '/media/events'));
    }
}
