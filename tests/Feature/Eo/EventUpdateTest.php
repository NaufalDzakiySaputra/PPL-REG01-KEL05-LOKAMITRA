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

    private function data(array $ubah = []): array
    {
        return array_merge([
            'nama_event' => 'Festival Baru',
            'kategori_event' => 'Pameran',
            'tanggal_mulai' => now()->addDays(20)->toDateString(),
            'tanggal_selesai' => now()->addDays(21)->toDateString(),
            'lokasi' => 'Cilacap',
            'deskripsi' => 'Deskripsi baru.',
        ], $ubah);
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

    public function test_perubahan_valid_tersimpan_dan_tampil_di_detail(): void
    {
        [$user, $eo] = $this->eoAktif();
        $event = $this->buatEvent($eo);

        $this->actingAs($user)->put(route('eo.events.update', $event), $this->data())
            ->assertRedirect(route('eo.events.show', $event));

        $this->assertDatabaseHas('event', [
            'event_id' => $event->event_id,
            'nama_event' => 'Festival Baru',
            'lokasi' => 'Cilacap',
        ]);

        $this->actingAs($user)->get(route('eo.events.show', $event))
            ->assertInertia(fn (Assert $p) => $p
                ->component('eo/events/Show')
                ->where('event.nama', 'Festival Baru')
                ->where('event.lokasi', 'Cilacap'));
    }

    public function test_eo_lain_tidak_bisa_mengubah_atau_melihat(): void
    {
        [, $eo] = $this->eoAktif();
        [$userLain] = $this->eoAktif();
        $event = $this->buatEvent($eo);

        $this->actingAs($userLain)->get(route('eo.events.edit', $event))->assertForbidden();
        $this->actingAs($userLain)->get(route('eo.events.show', $event))->assertForbidden();
        $this->actingAs($userLain)->put(route('eo.events.update', $event), $this->data())
            ->assertForbidden();

        $this->assertDatabaseHas('event', ['event_id' => $event->event_id, 'nama_event' => 'Festival Lama']);
    }

    public function test_data_tidak_valid_ditolak_dan_data_lama_tetap(): void
    {
        [$user, $eo] = $this->eoAktif();
        $event = $this->buatEvent($eo);

        $this->actingAs($user)->put(route('eo.events.update', $event), $this->data([
            'nama_event' => '',
            'lokasi' => '',
            'tanggal_mulai' => now()->addDays(5)->toDateString(),
            'tanggal_selesai' => now()->addDay()->toDateString(),
            'kategori_event' => 'Tidak ada',
        ]))->assertSessionHasErrors(['nama_event', 'lokasi', 'tanggal_selesai', 'kategori_event']);

        $this->assertDatabaseHas('event', ['event_id' => $event->event_id, 'nama_event' => 'Festival Lama']);
    }

    public function test_status_dan_pemilik_tidak_bisa_diubah_lewat_request(): void
    {
        [$user, $eo] = $this->eoAktif();
        [, $eoLain] = $this->eoAktif();
        $event = $this->buatEvent($eo);

        $this->actingAs($user)->put(route('eo.events.update', $event), $this->data([
            'status_event' => Event::STATUS_DIPUBLIKASIKAN,
            'eo_id' => $eoLain->eo_id,
        ]))->assertRedirect();

        $event->refresh();
        $this->assertSame(Event::STATUS_DRAFT, $event->status_event);
        $this->assertSame($eo->eo_id, $event->eo_id);
    }

    public function test_event_ditutup_tidak_bisa_diubah(): void
    {
        [$user, $eo] = $this->eoAktif();
        $event = $this->buatEvent($eo, ['status_event' => Event::STATUS_DITUTUP]);

        $this->actingAs($user)->get(route('eo.events.edit', $event))->assertForbidden();
        $this->actingAs($user)->put(route('eo.events.update', $event), $this->data())->assertForbidden();
    }

    public function test_tanggal_mulai_lama_yang_sudah_lewat_boleh_dipertahankan(): void
    {
        [$user, $eo] = $this->eoAktif();
        $lalu = now()->subDays(3)->toDateString();
        $event = $this->buatEvent($eo, [
            'tanggal_mulai' => $lalu,
            'tanggal_selesai' => now()->addDay()->toDateString(),
        ]);

        $this->actingAs($user)->put(route('eo.events.update', $event), $this->data([
            'tanggal_mulai' => $lalu,
            'tanggal_selesai' => now()->addDay()->toDateString(),
        ]))->assertSessionHasNoErrors();
    }

    public function test_role_lain_ditolak(): void
    {
        [, $eo] = $this->eoAktif();
        $event = $this->buatEvent($eo);
        $vendor = User::factory()->vendor()->create();

        $this->actingAs($vendor)->put(route('eo.events.update', $event), $this->data())
            ->assertForbidden();
    }

    public function test_tamu_diarahkan_ke_login(): void
    {
        [, $eo] = $this->eoAktif();
        $event = $this->buatEvent($eo);

        $this->put(route('eo.events.update', $event), $this->data())
            ->assertRedirect(route('login'));
        $this->get(route('eo.events.show', $event))
            ->assertRedirect(route('login'));
    }
}
