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

    public function test_role_lain_tidak_bisa_mengakses_halaman_buat_event(): void
    {
        $vendor = User::factory()->vendor()->create();

        $this->actingAs($vendor)->get(route('eo.events.create'))->assertForbidden();
    }

    public function test_tamu_diarahkan_ke_login(): void
    {
        $this->get(route('eo.events.create'))->assertRedirect(route('login'));
        $this->get(route('eo.events.index'))->assertRedirect(route('login'));
    }
}
