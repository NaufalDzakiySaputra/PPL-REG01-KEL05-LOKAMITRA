<?php

namespace Tests\Feature\Eo;

use App\Models\Eo;
use App\Models\Event;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class EventUpdateTest extends TestCase
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

    private function buatEvent(Eo $eo, array $ubah = []): Event
    {
        return Event::create(array_merge([
            'eo_id' => $eo->eo_id,
            'nama_event' => 'Festival Lama',
            'kategori_event' => 'Festival Budaya',
            'tanggal_mulai' => now()->addDays(10)->toDateString(),
            'tanggal_selesai' => now()->addDays(11)->toDateString(),
            'lokasi' => 'Purwokerto',
            'deskripsi' => 'Deskripsi lama.',
        ], $ubah));
    }

    public function test_pemilik_dapat_membuka_formulir_edit(): void
    {
        [$user, $eo] = $this->eoAktif();
        $event = $this->buatEvent($eo);

        $this->actingAs($user)->get(route('eo.events.edit', $event))
            ->assertOk()
            ->assertInertia(fn (Assert $p) => $p
                ->component('eo/events/Edit')
                ->where('event.nama', 'Festival Lama')
                ->has('kategori'));
    }

    public function test_eo_lain_tidak_bisa_mengubah_atau_melihat_halaman_edit(): void
    {
        [, $eo] = $this->eoAktif();
        [$userLain] = $this->eoAktif();
        $event = $this->buatEvent($eo);

        $this->actingAs($userLain)->get(route('eo.events.edit', $event))->assertForbidden();
        $this->actingAs($userLain)->get(route('eo.events.show', $event))->assertForbidden();
    }

    public function test_event_ditutup_tidak_bisa_diedit_di_halaman_web(): void
    {
        [$user, $eo] = $this->eoAktif();
        $event = $this->buatEvent($eo, ['status_event' => Event::STATUS_DITUTUP]);

        $this->actingAs($user)->get(route('eo.events.edit', $event))->assertForbidden();
    }

    public function test_tamu_diarahkan_ke_login(): void
    {
        [, $eo] = $this->eoAktif();
        $event = $this->buatEvent($eo);

        $this->get(route('eo.events.edit', $event))
            ->assertRedirect(route('login'));
        $this->get(route('eo.events.show', $event))
            ->assertRedirect(route('login'));
    }
}
