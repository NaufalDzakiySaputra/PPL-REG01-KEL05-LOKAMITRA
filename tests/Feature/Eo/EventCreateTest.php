<?php

namespace Tests\Feature\Eo;

use App\Models\Eo;
use App\Models\Event;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class EventCreateTest extends TestCase
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

    private function data(array $ubah = []): array
    {
        return array_merge([
            'nama_event' => 'Festival Budaya Banyumas',
            'kategori_event' => 'Festival Budaya',
            'tanggal_mulai' => now()->addDays(10)->toDateString(),
            'tanggal_selesai' => now()->addDays(11)->toDateString(),
            'lokasi' => 'Alun-alun Purwokerto, Banyumas',
            'deskripsi' => 'Festival budaya tahunan.',
        ], $ubah);
    }

    public function test_eo_dapat_membuka_formulir_dan_daftar_event(): void
    {
        [$user] = $this->eoAktif();

        $this->actingAs($user)->get(route('eo.events.create'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('eo/events/Create')
                ->has('kategori'));

        $this->actingAs($user)->get(route('eo.events.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('eo/events/Index'));
    }

    public function test_menu_event_pada_sidebar_aktif(): void
    {
        [$user] = $this->eoAktif();

        $this->actingAs($user)->get(route('eo.events.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('menu.items', fn ($items) => collect($items)
                    ->contains(fn ($i) => $i['label'] === 'Event' && $i['href'] === '/eo/events')));
    }

    public function test_event_valid_tersimpan_sebagai_draft_milik_eo(): void
    {
        [$user, $eo] = $this->eoAktif();

        $this->actingAs($user)->post(route('eo.events.store'), $this->data())
            ->assertRedirect(route('eo.events.index'));

        $this->assertDatabaseHas('event', [
            'nama_event' => 'Festival Budaya Banyumas',
            'eo_id' => $eo->eo_id,
            'status_event' => Event::STATUS_DRAFT,
        ]);
    }

    public function test_status_dan_pemilik_tidak_bisa_diubah_lewat_request(): void
    {
        [$user, $eo] = $this->eoAktif();
        [, $eoLain] = $this->eoAktif();

        $this->actingAs($user)->post(route('eo.events.store'), $this->data([
            'status_event' => Event::STATUS_DIPUBLIKASIKAN,
            'eo_id' => $eoLain->eo_id,
        ]))->assertRedirect();

        $event = Event::firstOrFail();
        $this->assertSame(Event::STATUS_DRAFT, $event->status_event);
        $this->assertSame($eo->eo_id, $event->eo_id);
    }

    public function test_data_tidak_valid_ditolak(): void
    {
        [$user] = $this->eoAktif();

        $this->actingAs($user)->post(route('eo.events.store'), $this->data([
            'nama_event' => '',
            'lokasi' => '',
            'tanggal_mulai' => now()->addDays(5)->toDateString(),
            'tanggal_selesai' => now()->addDay()->toDateString(),
            'kategori_event' => 'Tidak ada',
        ]))->assertSessionHasErrors(['nama_event', 'lokasi', 'tanggal_selesai', 'kategori_event']);

        $this->assertDatabaseCount('event', 0);
    }

    public function test_event_baru_belum_tampil_untuk_publik(): void
    {
        [$user] = $this->eoAktif();

        $this->actingAs($user)->post(route('eo.events.store'), $this->data());

        $this->assertSame(1, Event::count());
        $this->assertSame(0, Event::tersediaUmum()->count());
        $this->assertSame(0, Event::berjalan()->count());
    }

    public function test_eo_hanya_melihat_event_miliknya(): void
    {
        [$user, $eo] = $this->eoAktif();
        [, $eoLain] = $this->eoAktif();

        Event::create($this->data(['eo_id' => $eo->eo_id, 'nama_event' => 'Milik Saya']));
        Event::create($this->data(['eo_id' => $eoLain->eo_id, 'nama_event' => 'Milik Orang Lain']));

        $this->actingAs($user)->get(route('eo.events.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->has('events.data', 1)
                ->where('events.data.0.nama', 'Milik Saya'));
    }

    public function test_role_lain_tidak_bisa_mengakses(): void
    {
        $vendor = User::factory()->vendor()->create();

        $this->actingAs($vendor)->get(route('eo.events.create'))->assertForbidden();
        $this->actingAs($vendor)->post(route('eo.events.store'), $this->data())->assertForbidden();
    }

    public function test_tamu_diarahkan_ke_login(): void
    {
        $this->post(route('eo.events.store'), $this->data())->assertRedirect(route('login'));
        $this->get(route('eo.events.index'))->assertRedirect(route('login'));
    }
}
