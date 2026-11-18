<?php

namespace Tests\Feature\Api\V1\Admin;

use App\Models\Admin;
use App\Models\Eo;
use App\Models\Event;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EventArchiveApiTest extends TestCase
{
    use RefreshDatabase;

    private function adminAktif(): array
    {
        $user = User::factory()->admin()->create();
        $admin = Admin::create(['akun_id' => $user->id, 'nama_admin' => 'Admin Utama']);

        return [$user, $admin];
    }

    private function buatEvent(string $status): Event
    {
        $userEo = User::factory()->eo()->create();
        $eo = Eo::create([
            'akun_id' => $userEo->id,
            'nama_eo_organisasi' => 'EO Test',
            'status_verifikasi' => Eo::STATUS_DISETUJUI,
        ]);

        return Event::create([
            'eo_id' => $eo->eo_id,
            'nama_event' => 'Event Uji',
            'tanggal_mulai' => now()->subDays(5)->toDateString(),
            'tanggal_selesai' => now()->subDays(1)->toDateString(),
            'lokasi' => 'Purwokerto',
            'status_event' => $status,
        ]);
    }

    public function test_admin_berhasil_mengarsipkan_event_ditutup(): void
    {
        [$user] = $this->adminAktif();
        $event = $this->buatEvent(Event::STATUS_DITUTUP);

        $this->actingAs($user)
            ->postJson("/api/v1/admin/events/{$event->event_id}/arsip")
            ->assertOk()
            ->assertJsonPath('message', 'Event berhasil diarsipkan.')
            ->assertJsonStructure(['message', 'data']);

        $this->assertSame(Event::STATUS_DIARSIPKAN, $event->refresh()->status_event);
        $this->assertNotNull($event->tanggal_diarsipkan);
    }

    public function test_event_diarsipkan_tampil_di_daftar_arsip(): void
    {
        [$user] = $this->adminAktif();
        $event = $this->buatEvent(Event::STATUS_DITUTUP);

        $this->assertSame(0, Event::where('status_event', Event::STATUS_DIARSIPKAN)->count());

        $this->actingAs($user)
            ->postJson("/api/v1/admin/events/{$event->event_id}/arsip")
            ->assertOk();

        $this->assertSame(1, Event::where('status_event', Event::STATUS_DIARSIPKAN)->count());

        $this->actingAs($user)
            ->getJson('/api/v1/admin/events/arsip')
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_event_bukan_ditutup_ditolak_422(): void
    {
        [$user] = $this->adminAktif();

        foreach ([Event::STATUS_DRAFT, Event::STATUS_MENUNGGU_VERIFIKASI, Event::STATUS_DIPUBLIKASIKAN] as $status) {
            $event = $this->buatEvent($status);

            $this->actingAs($user)
                ->postJson("/api/v1/admin/events/{$event->event_id}/arsip")
                ->assertUnprocessable();

            $this->assertSame($status, $event->refresh()->status_event);
        }
    }

    public function test_non_admin_ditolak_403(): void
    {
        $event = $this->buatEvent(Event::STATUS_DITUTUP);
        $userEo = User::factory()->eo()->create();

        $this->actingAs($userEo)
            ->postJson("/api/v1/admin/events/{$event->event_id}/arsip")
            ->assertForbidden();
    }

    public function test_tamu_ditolak_401(): void
    {
        $event = $this->buatEvent(Event::STATUS_DITUTUP);

        $this->postJson("/api/v1/admin/events/{$event->event_id}/arsip")
            ->assertUnauthorized();
    }
}
