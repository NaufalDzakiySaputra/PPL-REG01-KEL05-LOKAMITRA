<?php

namespace Tests\Feature\Admin;

use App\Models\Admin;
use App\Models\Eo;
use App\Models\Event;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class EventVerificationTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): array
    {
        $akun = User::factory()->admin()->create();
        $admin = Admin::create(['akun_id' => $akun->id]);

        return [$akun, $admin];
    }

    private function eo(): Eo
    {
        $user = User::factory()->eo()->create();

        return Eo::create([
            'akun_id' => $user->id,
            'nama_eo_organisasi' => 'EO Banyumas',
            'status_verifikasi' => Eo::STATUS_DISETUJUI,
        ]);
    }

    private function buatEvent(string $status, string $nama = 'Event Uji'): Event
    {
        return Event::create([
            'eo_id' => $this->eo()->eo_id,
            'nama_event' => $nama,
            'tanggal_mulai' => now()->addDays(10)->toDateString(),
            'tanggal_selesai' => now()->addDays(11)->toDateString(),
            'lokasi' => 'Purwokerto',
            'status_event' => $status,
        ]);
    }

    public function test_admin_melihat_event_yang_menunggu_verifikasi(): void
    {
        [$akun] = $this->admin();
        $this->buatEvent(Event::STATUS_MENUNGGU_VERIFIKASI, 'Menunggu');
        $this->buatEvent(Event::STATUS_DRAFT, 'Masih Draft');
        $this->buatEvent(Event::STATUS_DIPUBLIKASIKAN, 'Sudah Terverifikasi');

        $this->actingAs($akun)->get(route('admin.events.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $p) => $p
                ->component('admin/events/Index')
                ->has('events.data', 1)
                ->where('events.data.0.nama', 'Menunggu')
                ->where('ringkasan.menunggu', 1)
                ->where('ringkasan.terverifikasi', 1));
    }

    public function test_admin_dapat_melihat_tab_terverifikasi(): void
    {
        [$akun] = $this->admin();
        $this->buatEvent(Event::STATUS_MENUNGGU_VERIFIKASI);
        $this->buatEvent(Event::STATUS_DIPUBLIKASIKAN, 'Sudah Terverifikasi');

        $this->actingAs($akun)->get(route('admin.events.index', ['status' => 'dipublikasikan']))
            ->assertInertia(fn (Assert $p) => $p
                ->has('events.data', 1)
                ->where('events.data.0.nama', 'Sudah Terverifikasi'));
    }

    public function test_verifikasi_mengubah_status_dan_mencatat_admin(): void
    {
        [$akun, $admin] = $this->admin();
        $event = $this->buatEvent(Event::STATUS_MENUNGGU_VERIFIKASI);

        $this->actingAs($akun)->post(route('admin.events.verify', $event))->assertRedirect();

        $event->refresh();
        $this->assertSame(Event::STATUS_DIPUBLIKASIKAN, $event->status_event);
        $this->assertSame($admin->admin_id, $event->diverifikasi_oleh);
        $this->assertNotNull($event->tanggal_diverifikasi);
    }

    public function test_hanya_event_terverifikasi_tampil_di_daftar_publik(): void
    {
        [$akun] = $this->admin();
        $menunggu = $this->buatEvent(Event::STATUS_MENUNGGU_VERIFIKASI, 'Menunggu');
        $this->buatEvent(Event::STATUS_DRAFT, 'Draft');
        $this->buatEvent(Event::STATUS_DITUTUP, 'Ditutup');
        $this->buatEvent(Event::STATUS_DIARSIPKAN, 'Arsip');

        // Sebelum diverifikasi: tidak ada yang tampil di publik.
        $this->assertSame(0, Event::tersediaUmum()->count());

        $this->actingAs($akun)->post(route('admin.events.verify', $menunggu));

        // Setelah diverifikasi: hanya event tersebut yang tampil.
        $this->assertSame(['Menunggu'], Event::tersediaUmum()->pluck('nama_event')->all());
    }

    public function test_event_bukan_menunggu_tidak_berubah(): void
    {
        [$akun] = $this->admin();
        $draft = $this->buatEvent(Event::STATUS_DRAFT);

        $this->actingAs($akun)->post(route('admin.events.verify', $draft))->assertRedirect();

        $this->assertSame(Event::STATUS_DRAFT, $draft->refresh()->status_event);
        $this->assertNull($draft->diverifikasi_oleh);
    }

    public function test_selain_admin_tidak_bisa_mengakses(): void
    {
        $event = $this->buatEvent(Event::STATUS_MENUNGGU_VERIFIKASI);

        foreach ([User::factory()->eo()->create(), User::factory()->vendor()->create()] as $akun) {
            $this->actingAs($akun)->get(route('admin.events.index'))->assertForbidden();
            $this->actingAs($akun)->post(route('admin.events.verify', $event))->assertForbidden();
        }

        $this->assertSame(Event::STATUS_MENUNGGU_VERIFIKASI, $event->refresh()->status_event);
    }

    public function test_tamu_diarahkan_ke_login(): void
    {
        $event = $this->buatEvent(Event::STATUS_MENUNGGU_VERIFIKASI);

        $this->get(route('admin.events.index'))->assertRedirect(route('login'));
        $this->post(route('admin.events.verify', $event))->assertRedirect(route('login'));
    }

    public function test_menu_daftar_event_admin_aktif(): void
    {
        [$akun] = $this->admin();

        $this->actingAs($akun)->get(route('admin.events.index'))
            ->assertInertia(fn (Assert $p) => $p
                ->where('menu.items', fn ($items) => collect($items)
                    ->contains(fn ($i) => $i['label'] === 'Daftar Event' && $i['href'] === '/admin/events')));
    }
}
