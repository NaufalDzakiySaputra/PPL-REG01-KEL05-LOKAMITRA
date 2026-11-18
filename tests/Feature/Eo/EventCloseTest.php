<?php

namespace Tests\Feature\Eo;

use App\Models\Eo;
use App\Models\Event;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class EventCloseTest extends TestCase
{
    use RefreshDatabase;

    private function eoAktif(): array
    {
        $user = User::factory()->eo()->create();
        $eo = Eo::create([
            'akun_id' => $user->id,
            'nama_eo_organisasi' => 'EO Banyumas',
            'status_verifikasi' => Eo::STATUS_DISETUJUI,
        ]);

        return [$user, $eo];
    }

    private function buatEvent(Eo $eo, string $status, int $mulai = -2, int $selesai = 0): Event
    {
        return Event::create([
            'eo_id' => $eo->eo_id,
            'nama_event' => 'Event Uji',
            'tanggal_mulai' => now()->addDays($mulai)->toDateString(),
            'tanggal_selesai' => now()->addDays($selesai)->toDateString(),
            'lokasi' => 'Purwokerto',
            'status_event' => $status,
        ]);
    }

    public function test_eo_pemilik_dapat_menutup_event_yang_sudah_selesai(): void
    {
        [$user, $eo] = $this->eoAktif();
        $event = $this->buatEvent($eo, Event::STATUS_DIPUBLIKASIKAN);

        $this->actingAs($user)->post(route('eo.events.close', $event))->assertRedirect();

        $event->refresh();
        $this->assertSame(Event::STATUS_DITUTUP, $event->status_event);
        $this->assertNotNull($event->tanggal_ditutup);
    }

    public function test_event_ditutup_tidak_tampil_sebagai_berlangsung_dan_siap_diarsipkan(): void
    {
        [$user, $eo] = $this->eoAktif();
        $event = $this->buatEvent($eo, Event::STATUS_DIPUBLIKASIKAN);

        $this->assertSame(1, Event::berjalan()->count());
        $this->assertSame(1, Event::tersediaUmum()->count());
        $this->assertSame(0, Event::siapDiarsipkan()->count());

        $this->actingAs($user)->post(route('eo.events.close', $event));

        $this->assertSame(0, Event::berjalan()->count());
        $this->assertSame(0, Event::tersediaUmum()->count());
        $this->assertSame(1, Event::siapDiarsipkan()->count());
    }

    public function test_eo_lain_tidak_bisa_menutup_event(): void
    {
        [, $eo] = $this->eoAktif();
        [$userLain] = $this->eoAktif();
        $event = $this->buatEvent($eo, Event::STATUS_DIPUBLIKASIKAN);

        $this->actingAs($userLain)->post(route('eo.events.close', $event))->assertForbidden();

        $this->assertSame(Event::STATUS_DIPUBLIKASIKAN, $event->refresh()->status_event);
        $this->assertNull($event->tanggal_ditutup);
    }

     public function test_event_yang_masih_berlangsung_boleh_ditutup(): void
    {
        [$user, $eo] = $this->eoAktif();
        $event = $this->buatEvent($eo, Event::STATUS_DIPUBLIKASIKAN, -1, 3);

        $this->actingAs($user)->post(route('eo.events.close', $event))->assertRedirect();

        $this->assertSame(Event::STATUS_DITUTUP, $event->refresh()->status_event);
    }

    public function test_event_yang_belum_terverifikasi_tidak_bisa_ditutup(): void
    {
        [$user, $eo] = $this->eoAktif();

        foreach ([Event::STATUS_DRAFT, Event::STATUS_MENUNGGU_VERIFIKASI, Event::STATUS_DIARSIPKAN] as $status) {
            $event = $this->buatEvent($eo, $status);

            $this->actingAs($user)->post(route('eo.events.close', $event))->assertRedirect();

            $this->assertSame($status, $event->refresh()->status_event);
        }
    }

    public function test_menutup_dua_kali_tidak_mengubah_tanggal_ditutup(): void
    {
        [$user, $eo] = $this->eoAktif();
        $event = $this->buatEvent($eo, Event::STATUS_DIPUBLIKASIKAN);

        $this->actingAs($user)->post(route('eo.events.close', $event));
        $pertama = $event->refresh()->tanggal_ditutup;

        $this->travel(1)->hour();
        $this->actingAs($user)->post(route('eo.events.close', $event));

        $this->assertTrue($pertama->equalTo($event->refresh()->tanggal_ditutup));
    }

    public function test_detail_menampilkan_tombol_tutup_hanya_untuk_event_terverifikasi(): void
    {
        [$user, $eo] = $this->eoAktif();
        $terverifikasi = $this->buatEvent($eo, Event::STATUS_DIPUBLIKASIKAN, -1, 3);
        $draft = $this->buatEvent($eo, Event::STATUS_DRAFT);

        $this->actingAs($user)->get(route('eo.events.show', $terverifikasi))
            ->assertInertia(fn (Assert $p) => $p->where('event.dapat_ditutup', true));

        $this->actingAs($user)->get(route('eo.events.show', $draft))
            ->assertInertia(fn (Assert $p) => $p->where('event.dapat_ditutup', false));
    }

    public function test_event_ditutup_tidak_bisa_diubah_lagi(): void
    {
        [$user, $eo] = $this->eoAktif();
        $event = $this->buatEvent($eo, Event::STATUS_DIPUBLIKASIKAN);

        $this->actingAs($user)->post(route('eo.events.close', $event));

        $this->actingAs($user)->get(route('eo.events.edit', $event))->assertForbidden();
    }

    public function test_role_lain_ditolak(): void
    {
        [, $eo] = $this->eoAktif();
        $event = $this->buatEvent($eo, Event::STATUS_DIPUBLIKASIKAN);
        $vendor = User::factory()->vendor()->create();

        $this->actingAs($vendor)->post(route('eo.events.close', $event))->assertForbidden();
    }

    public function test_tamu_diarahkan_ke_login(): void
    {
        [, $eo] = $this->eoAktif();
        $event = $this->buatEvent($eo, Event::STATUS_DIPUBLIKASIKAN);

        $this->post(route('eo.events.close', $event))->assertRedirect(route('login'));
    }
}