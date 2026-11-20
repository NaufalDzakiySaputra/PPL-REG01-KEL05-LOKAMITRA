<?php

namespace Tests\Feature\Eo;

use App\Models\Eo;
use App\Models\Event;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

// PBI-036: halaman detail EO menjadi tempat memperbarui informasi publik event
// (rundown, layout/dokumen, regulasi, dan kontak darurat).
class EventShowTest extends TestCase
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
            'nama_event' => 'Festival Budaya',
            'kategori_event' => 'Festival Budaya',
            'tanggal_mulai' => now()->subDay()->toDateString(),
            'tanggal_selesai' => now()->addDays(3)->toDateString(),
            'lokasi' => 'Purwokerto',
            'deskripsi' => 'Deskripsi event.',
            'status_event' => Event::STATUS_DIPUBLIKASIKAN,
        ], $ubah));
    }

    public function test_pemilik_melihat_halaman_detail_event_yang_bisa_diubah(): void
    {
        [$user, $eo] = $this->eoAktif();
        $event = $this->buatEvent($eo);

        $this->actingAs($user)
            ->get(route('eo.events.show', $event))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('eo/events/Show')
                ->where('event.id', $event->event_id)
                ->where('event.nama', 'Festival Budaya')
                ->where('event.dapat_diubah', true));
    }

    public function test_event_ditutup_tetap_bisa_dilihat_tetapi_tidak_dapat_diubah(): void
    {
        [$user, $eo] = $this->eoAktif();
        $event = $this->buatEvent($eo, ['status_event' => Event::STATUS_DITUTUP]);

        $this->actingAs($user)
            ->get(route('eo.events.show', $event))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('eo/events/Show')
                ->where('event.dapat_diubah', false));
    }

    public function test_eo_lain_tidak_bisa_membuka_detail_event(): void
    {
        [, $eo] = $this->eoAktif();
        [$userLain] = $this->eoAktif();
        $event = $this->buatEvent($eo);

        $this->actingAs($userLain)
            ->get(route('eo.events.show', $event))
            ->assertForbidden();
    }

    public function test_tamu_diarahkan_ke_login(): void
    {
        [, $eo] = $this->eoAktif();
        $event = $this->buatEvent($eo);

        $this->get(route('eo.events.show', $event))
            ->assertRedirect(route('login'));
    }
}
