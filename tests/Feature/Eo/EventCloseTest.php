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
        $event = $this->buatEvent($eo, Event::STATUS_DITUTUP);

        $this->actingAs($user)->get(route('eo.events.edit', $event))->assertForbidden();
    }

    public function test_halaman_detail_event_dapat_diakses_pemilik(): void
    {
        [$user, $eo] = $this->eoAktif();
        $event = $this->buatEvent($eo, Event::STATUS_DIPUBLIKASIKAN);

        $this->actingAs($user)->get(route('eo.events.show', $event))
            ->assertOk()
            ->assertInertia(fn (Assert $p) => $p
                ->component('eo/events/Show')
                ->where('event.id', $event->event_id)
            );
    }
}
