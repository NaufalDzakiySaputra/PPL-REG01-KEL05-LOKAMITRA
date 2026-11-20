<?php

namespace Tests\Feature\Api\V1\Eo;

use App\Models\Eo;
use App\Models\Event;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EventCloseApiTest extends TestCase
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

    public function test_eo_pemilik_berhasil_menutup_event_dipublikasikan(): void
    {
        [$user, $eo] = $this->eoAktif();
        $event = $this->buatEvent($eo, Event::STATUS_DIPUBLIKASIKAN);

        $this->actingAs($user)
            ->postJson("/api/v1/eo/events/{$event->event_id}/tutup")
            ->assertOk()
            ->assertJsonStructure(['message', 'data']);

        $event->refresh();
        $this->assertSame(Event::STATUS_DITUTUP, $event->status_event);
        $this->assertNotNull($event->tanggal_ditutup);
    }

    public function test_event_ditutup_tidak_tampil_di_berjalan_dan_tersedia_umum(): void
    {
        [$user, $eo] = $this->eoAktif();
        $event = $this->buatEvent($eo, Event::STATUS_DIPUBLIKASIKAN);

        $this->assertSame(1, Event::berjalan()->count());
        $this->assertSame(1, Event::tersediaUmum()->count());
        $this->assertSame(0, Event::siapDiarsipkan()->count());

        $this->actingAs($user)
            ->postJson("/api/v1/eo/events/{$event->event_id}/tutup")
            ->assertOk();

        $this->assertSame(0, Event::berjalan()->count());
        $this->assertSame(0, Event::tersediaUmum()->count());
        $this->assertSame(1, Event::siapDiarsipkan()->count());
    }

    public function test_eo_lain_ditolak_403(): void
    {
        [, $eo] = $this->eoAktif();
        [$userLain] = $this->eoAktif();
        $event = $this->buatEvent($eo, Event::STATUS_DIPUBLIKASIKAN);

        $this->actingAs($userLain)
            ->postJson("/api/v1/eo/events/{$event->event_id}/tutup")
            ->assertForbidden();

        $this->assertSame(Event::STATUS_DIPUBLIKASIKAN, $event->refresh()->status_event);
    }

    public function test_event_bukan_dipublikasikan_ditolak_422(): void
    {
        [$user, $eo] = $this->eoAktif();

        foreach ([Event::STATUS_DRAFT, Event::STATUS_MENUNGGU_VERIFIKASI, Event::STATUS_DIARSIPKAN] as $status) {
            $event = $this->buatEvent($eo, $status);

            $this->actingAs($user)
                ->postJson("/api/v1/eo/events/{$event->event_id}/tutup")
                ->assertUnprocessable();

            $this->assertSame($status, $event->refresh()->status_event);
        }
    }

    public function test_tamu_ditolak_401(): void
    {
        [, $eo] = $this->eoAktif();
        $event = $this->buatEvent($eo, Event::STATUS_DIPUBLIKASIKAN);

        $this->postJson("/api/v1/eo/events/{$event->event_id}/tutup")
            ->assertUnauthorized();
    }

    public function test_role_bukan_eo_ditolak_403(): void
    {
        [, $eo] = $this->eoAktif();
        $event = $this->buatEvent($eo, Event::STATUS_DIPUBLIKASIKAN);
        $vendor = User::factory()->vendor()->create();

        $this->actingAs($vendor)
            ->postJson("/api/v1/eo/events/{$event->event_id}/tutup")
            ->assertForbidden();
    }
}
