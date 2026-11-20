<?php

namespace Tests\Feature\Admin;

use App\Models\Admin;
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

// PBI-037: Pengelola Platform memeriksa informasi publik event sebelum/ sesudah
// dipublikasikan, termasuk mengunduh dan mempratinjau layout/dokumen.
class EventReviewTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $akun = User::factory()->admin()->create();
        Admin::create(['akun_id' => $akun->id]);

        return $akun;
    }

    private function buatEo(): array
    {
        $user = User::factory()->eo()->create(['name' => 'Sahal Dafa']);
        $eo = Eo::create([
            'akun_id' => $user->id,
            'nama_eo_organisasi' => 'EO Banyumas Creative',
            'status_verifikasi' => Eo::STATUS_DISETUJUI,
        ]);

        return [$user, $eo];
    }

    private function buatEvent(Eo $eo, string $status = Event::STATUS_MENUNGGU_VERIFIKASI, array $ubah = []): Event
    {
        return Event::create(array_merge([
            'eo_id' => $eo->eo_id,
            'nama_event' => 'Festival Budaya Banyumas',
            'kategori_event' => 'Festival Budaya',
            'tanggal_mulai' => now()->addDays(2)->toDateString(),
            'tanggal_selesai' => now()->addDays(4)->toDateString(),
            'lokasi' => 'Alun-alun, Banyumas',
            'deskripsi' => 'Festival tahunan masyarakat Banyumas.',
            'status_event' => $status,
        ], $ubah));
    }

    private function buatDokumen(Event $event, User $pengunggah, array $ubah = []): DokumenEvent
    {
        $nama = $ubah['nama_dokumen'] ?? 'Denah Lokasi.pdf';
        $lokasi = "dokumen-event/{$event->event_id}/".str()->slug($nama).'.pdf';

        Storage::disk('local')->put(
            $lokasi,
            "%PDF-1.4\n1 0 obj\n<< /Type /Catalog >>\nendobj\n%%EOF\n",
        );

        return DokumenEvent::create(array_merge([
            'event_id' => $event->event_id,
            'pengunggah_akun_id' => $pengunggah->id,
            'nama_dokumen' => $nama,
            'jenis' => DokumenEvent::JENIS_LAYOUT,
            'lokasi_file' => $lokasi,
            'tanggal_upload' => now(),
        ], $ubah));
    }

    public function test_admin_dapat_memeriksa_seluruh_informasi_publik_event(): void
    {
        [$userEo, $eo] = $this->buatEo();
        $event = $this->buatEvent($eo, Event::STATUS_MENUNGGU_VERIFIKASI, [
            'informasi_regulasi' => 'Dilarang membawa benda tajam.',
        ]);

        // Sengaja dibuat tidak urut agar pengurutan rundown ikut teruji.
        Timeline::create([
            'event_id' => $event->event_id,
            'judul_aktivitas' => 'Penutupan',
            'waktu_mulai' => today()->setTime(13, 0),
            'waktu_selesai' => today()->setTime(15, 0),
        ]);
        Timeline::create([
            'event_id' => $event->event_id,
            'judul_aktivitas' => 'Pembukaan',
            'deskripsi' => 'Sambutan panitia.',
            'waktu_mulai' => today()->setTime(8, 0),
            'waktu_selesai' => today()->setTime(9, 0),
        ]);

        $this->buatDokumen($event, $userEo, ['nama_dokumen' => 'Poster Acara.pdf']);
        $this->buatDokumen($event, $userEo, ['nama_dokumen' => 'Denah Lokasi.pdf']);
        KontakDaruratEvent::create([
            'event_id' => $event->event_id,
            'nama' => 'Pos Medis',
            'nomor_telepon' => '081200000001',
        ]);

        $this->actingAs($this->admin())
            ->get(route('admin.events.show', $event))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/events/Show')
                ->where('event.id', $event->event_id)
                ->where('event.nama', 'Festival Budaya Banyumas')
                ->where('event.status', Event::STATUS_MENUNGGU_VERIFIKASI)
                ->where('event.status_label', 'Menunggu Verifikasi')
                ->where('event.dapat_diverifikasi', true)
                ->where('event.dapat_diarsipkan', false)
                ->where('event.informasi_regulasi', 'Dilarang membawa benda tajam.')
                ->where('eo.nama_organisasi', 'EO Banyumas Creative')
                ->where('eo.nama_penanggung_jawab', 'Sahal Dafa')
                ->where('eo.status_verifikasi_label', 'Disetujui')
                ->has('rundown', 2)
                ->where('rundown.0.judul', 'Pembukaan')
                ->where('rundown.1.judul', 'Penutupan')
                ->has('dokumen', 2)
                ->where('dokumen.0.nama', 'Denah Lokasi.pdf')
                ->where('dokumen.1.nama', 'Poster Acara.pdf')
                ->has('kontak_darurat', 1)
                ->where('kontak_darurat.0.nama', 'Pos Medis'));
    }

    public function test_admin_dapat_membuka_pemeriksaan_untuk_semua_status_event(): void
    {
        [$userEo, $eo] = $this->buatEo();
        $admin = $this->admin();

        $harapan = [
            Event::STATUS_DRAFT => [false, false],
            Event::STATUS_MENUNGGU_VERIFIKASI => [true, false],
            Event::STATUS_DIPUBLIKASIKAN => [false, false],
            Event::STATUS_DITUTUP => [false, true],
            Event::STATUS_DIARSIPKAN => [false, false],
        ];

        foreach ($harapan as $status => [$dapatDiverifikasi, $dapatDiarsipkan]) {
            $event = $this->buatEvent($eo, $status);

            $this->actingAs($admin)
                ->get(route('admin.events.show', $event))
                ->assertOk()
                ->assertInertia(fn (Assert $page) => $page
                    ->where('event.status', $status)
                    ->where('event.dapat_diverifikasi', $dapatDiverifikasi)
                    ->where('event.dapat_diarsipkan', $dapatDiarsipkan));
        }
    }

    public function test_verifikasi_dari_halaman_pemeriksaan_mencatat_admin_verifikator(): void
    {
        [, $eo] = $this->buatEo();
        $admin = $this->admin();
        $event = $this->buatEvent($eo);

        $this->actingAs($admin)
            ->postJson("/api/v1/admin/events/{$event->event_id}/verifikasi")
            ->assertOk();

        $segar = $event->fresh();

        $this->assertSame(Event::STATUS_DIPUBLIKASIKAN, $segar->status_event);
        $this->assertSame($admin->admin->admin_id, $segar->diverifikasi_oleh);
        $this->assertNotNull($segar->tanggal_diverifikasi);

        // Halaman pemeriksaan langsung mencerminkan status terbaru.
        $this->actingAs($admin)
            ->get(route('admin.events.show', $event))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('event.status_label', 'Terverifikasi')
                ->where('event.dapat_diverifikasi', false)
                ->where('event.diverifikasi_oleh', $admin->name));
    }

    public function test_informasi_yang_disetujui_tampil_di_papan_informasi_publik(): void
    {
        Storage::fake('local');

        [$userEo, $eo] = $this->buatEo();
        $event = $this->buatEvent($eo, Event::STATUS_MENUNGGU_VERIFIKASI, [
            'informasi_regulasi' => 'Dilarang membawa benda tajam.',
        ]);

        Timeline::create([
            'event_id' => $event->event_id,
            'judul_aktivitas' => 'Pembukaan',
            'waktu_mulai' => today()->setTime(8, 0),
            'waktu_selesai' => today()->setTime(9, 0),
        ]);
        $this->buatDokumen($event, $userEo, ['nama_dokumen' => 'Denah Lokasi.pdf']);
        KontakDaruratEvent::create([
            'event_id' => $event->event_id,
            'nama' => 'Pos Medis',
            'nomor_telepon' => '081200000001',
        ]);

        // Sebelum disetujui, informasi belum tampil di papan informasi publik.
        $this->getJson("/api/v1/events/{$event->event_id}")->assertNotFound();

        $this->actingAs($this->admin())
            ->postJson("/api/v1/admin/events/{$event->event_id}/verifikasi")
            ->assertOk();

        // AC: informasi yang disetujui tampil pada papan informasi publik.
        $this->getJson("/api/v1/events/{$event->event_id}")
            ->assertOk()
            ->assertJsonPath('data.nama', 'Festival Budaya Banyumas')
            ->assertJsonPath('data.informasi_regulasi', 'Dilarang membawa benda tajam.')
            ->assertJsonPath('data.rundown.0.judul', 'Pembukaan')
            ->assertJsonPath('data.dokumen.0.nama', 'Denah Lokasi.pdf')
            ->assertJsonPath('data.kontak_darurat.0.nama', 'Pos Medis');
    }

    public function test_admin_dapat_mengunduh_dokumen_event_yang_belum_dipublikasikan(): void
    {
        Storage::fake('local');

        [$userEo, $eo] = $this->buatEo();
        $event = $this->buatEvent($eo);
        $dokumen = $this->buatDokumen($event, $userEo);

        $this->actingAs($this->admin())
            ->get(route('admin.events.dokumen.unduh', [$event, $dokumen]))
            ->assertOk()
            ->assertDownload('Denah Lokasi.pdf');
    }

    public function test_admin_dapat_mempratinjau_pdf_secara_inline(): void
    {
        Storage::fake('local');

        [$userEo, $eo] = $this->buatEo();
        $event = $this->buatEvent($eo);
        $dokumen = $this->buatDokumen($event, $userEo);

        $this->actingAs($this->admin())
            ->get(route('admin.events.dokumen.pratinjau', [$event, $dokumen]))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf')
            ->assertHeader('Content-Disposition', 'inline')
            ->assertHeader('X-Content-Type-Options', 'nosniff');
    }

    public function test_pratinjau_menolak_berkas_selain_pdf_dan_gambar(): void
    {
        Storage::fake('local');

        [$userEo, $eo] = $this->buatEo();
        $event = $this->buatEvent($eo);
        $lokasi = "dokumen-event/{$event->event_id}/catatan.txt";
        Storage::disk('local')->put($lokasi, 'catatan biasa');

        $dokumen = DokumenEvent::create([
            'event_id' => $event->event_id,
            'pengunggah_akun_id' => $userEo->id,
            'nama_dokumen' => 'Catatan.txt',
            'jenis' => DokumenEvent::JENIS_DOKUMEN,
            'lokasi_file' => $lokasi,
            'tanggal_upload' => now(),
        ]);

        $this->actingAs($this->admin())
            ->get(route('admin.events.dokumen.pratinjau', [$event, $dokumen]))
            ->assertStatus(415);
    }

    public function test_dokumen_milik_event_lain_tidak_dapat_diakses(): void
    {
        Storage::fake('local');

        [$userEo, $eo] = $this->buatEo();
        $event = $this->buatEvent($eo);
        $eventLain = $this->buatEvent($eo, Event::STATUS_DRAFT, [
            'nama_event' => 'Event Lain',
        ]);
        $dokumenLain = $this->buatDokumen($eventLain, $userEo);

        $admin = $this->admin();

        $this->actingAs($admin)
            ->get(route('admin.events.dokumen.unduh', [$event, $dokumenLain]))
            ->assertNotFound();

        $this->actingAs($admin)
            ->get(route('admin.events.dokumen.pratinjau', [$event, $dokumenLain]))
            ->assertNotFound();
    }

    public function test_selain_admin_tidak_bisa_memeriksa_informasi_event(): void
    {
        [$userEo, $eo] = $this->buatEo();
        $event = $this->buatEvent($eo);

        $this->actingAs($userEo)
            ->get(route('admin.events.show', $event))
            ->assertForbidden();

        $this->actingAs(User::factory()->vendor()->create())
            ->get(route('admin.events.show', $event))
            ->assertForbidden();
    }

    public function test_tamu_diarahkan_ke_login_saat_memeriksa_informasi_event(): void
    {
        [, $eo] = $this->buatEo();
        $event = $this->buatEvent($eo);

        $this->get(route('admin.events.show', $event))
            ->assertRedirect(route('login'));
    }

    public function test_selain_admin_tidak_bisa_mengunduh_dokumen_pemeriksaan(): void
    {
        Storage::fake('local');

        [$userEo, $eo] = $this->buatEo();
        $event = $this->buatEvent($eo);
        $dokumen = $this->buatDokumen($event, $userEo);

        $this->actingAs($userEo)
            ->get(route('admin.events.dokumen.unduh', [$event, $dokumen]))
            ->assertForbidden();
    }

    public function test_tamu_diarahkan_ke_login_saat_mengunduh_dokumen_pemeriksaan(): void
    {
        Storage::fake('local');

        [$userEo, $eo] = $this->buatEo();
        $event = $this->buatEvent($eo);
        $dokumen = $this->buatDokumen($event, $userEo);

        $this->get(route('admin.events.dokumen.unduh', [$event, $dokumen]))
            ->assertRedirect(route('login'));
    }
}
