<?php

namespace Tests\Feature\Api\V1\Admin;

use App\Models\Admin;
use App\Models\Eo;
use App\Models\Event;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EventVerificationApiTest extends TestCase
{
    use RefreshDatabase;

    private function adminAktif(): array
    {
        $user = User::factory()->admin()->create();
        $admin = Admin::create([
            'akun_id' => $user->id,
            'nama_admin' => 'Admin Utama',
        ]);

        return [$user, $admin];
    }

    private function buatEvent(string $status, string $nama = 'Festival Banyumas'): Event
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
            'kategori_event' => 'Festival Budaya',
            'tanggal_mulai' => now()->addDays(5)->toDateString(),
            'tanggal_selesai' => now()->addDays(7)->toDateString(),
            'lokasi' => 'Alun-alun Purwokerto',
            'status_event' => $status,
        ]);
    }

    public function test_admin_dapat_melihat_daftar_event_menunggu_verifikasi(): void
    {
        [$admin] = $this->adminAktif();
        $this->buatEvent(Event::STATUS_MENUNGGU_VERIFIKASI, 'Event Menunggu');
        $this->buatEvent(Event::STATUS_DIPUBLIKASIKAN, 'Event Terverifikasi');

        $response = $this->actingAs($admin)
            ->getJson('/api/v1/admin/events?status=menunggu_verifikasi');

        $response->assertOk()
            ->assertJsonPath('data.0.nama_event', 'Event Menunggu')
            ->assertJsonPath('data.0.status', Event::STATUS_MENUNGGU_VERIFIKASI);
    }

    public function test_admin_berhasil_memverifikasi_event_hingga_muncul_di_publik(): void
    {
        [$userAdmin, $admin] = $this->adminAktif();
        $event = $this->buatEvent(Event::STATUS_MENUNGGU_VERIFIKASI);

        $this->assertSame(0, Event::tersediaUmum()->count());

        $response = $this->actingAs($userAdmin)
            ->postJson("/api/v1/admin/events/{$event->event_id}/verifikasi");

        $response->assertOk()
            ->assertJsonPath('message', 'Event berhasil diverifikasi.')
            ->assertJsonPath('data.id', $event->event_id)
            ->assertJsonPath('data.status', Event::STATUS_DIPUBLIKASIKAN)
            ->assertJsonPath('data.status_label', 'Terverifikasi');

        $event->refresh();
        $this->assertSame(Event::STATUS_DIPUBLIKASIKAN, $event->status_event);
        $this->assertSame($admin->admin_id, $event->diverifikasi_oleh);
        $this->assertNotNull($event->tanggal_diverifikasi);

        $this->assertSame(1, Event::tersediaUmum()->count());
    }

    public function test_verifikasi_gagal_jika_event_tidak_sedang_menunggu_verifikasi(): void
    {
        [$admin] = $this->adminAktif();
        $event = $this->buatEvent(Event::STATUS_DIPUBLIKASIKAN);

        $response = $this->actingAs($admin)
            ->postJson("/api/v1/admin/events/{$event->event_id}/verifikasi");

        $response->assertStatus(422)
            ->assertJsonPath('message', 'Event ini tidak sedang menunggu verifikasi.');
    }

    public function test_non_admin_ditolak_403_saat_mengakses_api_verifikasi(): void
    {
        $eoUser = User::factory()->eo()->create();
        $event = $this->buatEvent(Event::STATUS_MENUNGGU_VERIFIKASI);

        $this->actingAs($eoUser)
            ->getJson('/api/v1/admin/events')
            ->assertForbidden();

        $this->actingAs($eoUser)
            ->postJson("/api/v1/admin/events/{$event->event_id}/verifikasi")
            ->assertForbidden();
    }

    public function test_tamu_ditolak_401_saat_mengakses_api_verifikasi(): void
    {
        $event = $this->buatEvent(Event::STATUS_MENUNGGU_VERIFIKASI);

        $this->getJson('/api/v1/admin/events')
            ->assertUnauthorized();

        $this->postJson("/api/v1/admin/events/{$event->event_id}/verifikasi")
            ->assertUnauthorized();
    }
}
