<?php

namespace Tests\Feature\Admin;

use App\Models\Admin;
use App\Models\Eo;
use App\Models\Event;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class EventArchiveTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $akun = User::factory()->admin()->create();
        Admin::create(['akun_id' => $akun->id]);

        return $akun;
    }

    private function buatEvent(string $status, string $nama = 'Event Uji'): Event
    {
        $user = User::factory()->eo()->create();
        $eo = Eo::create([
            'akun_id' => $user->id,
            'nama_eo_organisasi' => 'EO Banyumas',
            'status_verifikasi' => Eo::STATUS_DISETUJUI,
        ]);

        return Event::create([
            'eo_id' => $eo->eo_id,
            'nama_event' => $nama,
            'tanggal_mulai' => now()->subDays(5)->toDateString(),
            'tanggal_selesai' => now()->subDays(2)->toDateString(),
            'lokasi' => 'Purwokerto',
            'status_event' => $status,
        ]);
    }

    public function test_admin_dapat_mengarsipkan_event_yang_ditutup(): void
    {
        $event = $this->buatEvent(Event::STATUS_DITUTUP);

        $this->actingAs($this->admin())
            ->post(route('admin.events.archive', $event))
            ->assertRedirect();

        $event->refresh();
        $this->assertSame(Event::STATUS_DIARSIPKAN, $event->status_event);
        $this->assertNotNull($event->tanggal_diarsipkan);
    }

    public function test_event_yang_belum_ditutup_tidak_bisa_diarsipkan(): void
    {
        $admin = $this->admin();

        foreach ([Event::STATUS_DRAFT, Event::STATUS_MENUNGGU_VERIFIKASI, Event::STATUS_DIPUBLIKASIKAN] as $status) {
            $event = $this->buatEvent($status);

            $this->actingAs($admin)->post(route('admin.events.archive', $event))->assertRedirect();

            $this->assertSame($status, $event->refresh()->status_event);
            $this->assertNull($event->tanggal_diarsipkan);
        }
    }

    public function test_mengarsipkan_dua_kali_tidak_mengubah_tanggal_diarsipkan(): void
    {
        $admin = $this->admin();
        $event = $this->buatEvent(Event::STATUS_DITUTUP);

        $this->actingAs($admin)->post(route('admin.events.archive', $event));
        $pertama = $event->refresh()->tanggal_diarsipkan;

        $this->travel(1)->hour();
        $this->actingAs($admin)->post(route('admin.events.archive', $event));

        $this->assertTrue($pertama->equalTo($event->refresh()->tanggal_diarsipkan));
    }

    public function test_tab_siap_diarsipkan_hanya_memuat_event_ditutup(): void
    {
        $this->buatEvent(Event::STATUS_DITUTUP, 'Ditutup');
        $this->buatEvent(Event::STATUS_DIARSIPKAN, 'Arsip');
        $this->buatEvent(Event::STATUS_DIPUBLIKASIKAN, 'Aktif');

        $this->actingAs($this->admin())
            ->get(route('admin.events.index', ['status' => 'ditutup']))
            ->assertInertia(fn (Assert $p) => $p
                ->has('events.data', 1)
                ->where('events.data.0.nama', 'Ditutup')
                ->where('ringkasan.ditutup', 1)
                ->where('ringkasan.diarsip', 1));
    }

    public function test_event_arsip_ditemukan_di_daftar_arsip_dan_tidak_tampil_aktif(): void
    {
        $admin = $this->admin();
        $event = $this->buatEvent(Event::STATUS_DITUTUP, 'Pasar Rakyat');

        $this->actingAs($admin)->post(route('admin.events.archive', $event));

        // Ditemukan di tab Arsip.
        $this->actingAs($admin)
            ->get(route('admin.events.index', ['status' => 'diarsipkan']))
            ->assertInertia(fn (Assert $p) => $p
                ->has('events.data', 1)
                ->where('events.data.0.nama', 'Pasar Rakyat')
                ->where('events.data.0.status', 'diarsipkan'));

        // Tidak lagi di tab siap diarsipkan, tidak tampil sebagai event aktif/publik.
        $this->actingAs($admin)
            ->get(route('admin.events.index', ['status' => 'ditutup']))
            ->assertInertia(fn (Assert $p) => $p->has('events.data', 0));

        $this->assertSame(0, Event::tersediaUmum()->count());
        $this->assertSame(0, Event::berjalan()->count());
        $this->assertSame(0, Event::siapDiarsipkan()->count());
    }

    public function test_selain_admin_tidak_bisa_mengarsipkan(): void
    {
        $event = $this->buatEvent(Event::STATUS_DITUTUP);

        foreach ([User::factory()->eo()->create(), User::factory()->vendor()->create()] as $akun) {
            $this->actingAs($akun)->post(route('admin.events.archive', $event))->assertForbidden();
        }

        $this->assertSame(Event::STATUS_DITUTUP, $event->refresh()->status_event);
    }

    public function test_tamu_diarahkan_ke_login(): void
    {
        $event = $this->buatEvent(Event::STATUS_DITUTUP);

        $this->post(route('admin.events.archive', $event))->assertRedirect(route('login'));
    }
}
