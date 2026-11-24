<?php

namespace Tests\Feature\Api\V1\Forum;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

// F-07: pembatasan akses forum berdasarkan keanggotaan_event yang diterima
// atau kepemilikan event oleh EO.
class ForumAksesApiTest extends TestCase
{
    use ForumTestHelpers;
    use RefreshDatabase;

    public function test_pengguna_tanpa_login_tidak_dapat_membuka_forum(): void
    {
        [, , $event] = $this->buatEventForum();

        $this->getJson("/api/v1/events/{$event->event_id}/forum")
            ->assertUnauthorized();
    }

    public function test_vendor_yang_bukan_peserta_tidak_dapat_membaca_topik(): void
    {
        [, , $event] = $this->buatEventForum();
        $vendor = User::factory()->vendor()->create();

        $this->actingAs($vendor)
            ->getJson("/api/v1/events/{$event->event_id}/forum")
            ->assertNotFound();
    }

    public function test_keanggotaan_berstatus_menunggu_tidak_memberi_akses(): void
    {
        [, , $event] = $this->buatEventForum();
        $komunitas = User::factory()->komunitas()->create();
        $this->jadikanPeserta($komunitas, $event, 'menunggu');

        $this->actingAs($komunitas)
            ->getJson("/api/v1/events/{$event->event_id}/forum")
            ->assertNotFound();
    }

    public function test_keanggotaan_berstatus_ditolak_tidak_memberi_akses(): void
    {
        [, , $event] = $this->buatEventForum();
        $media = User::factory()->media()->create();
        $this->jadikanPeserta($media, $event, 'ditolak');

        $this->actingAs($media)
            ->getJson("/api/v1/events/{$event->event_id}/forum")
            ->assertNotFound();
    }

    public function test_eo_tidak_dapat_membuka_forum_event_milik_eo_lain(): void
    {
        [, , $event] = $this->buatEventForum();
        $eoLain = User::factory()->eo()->create();

        $this->actingAs($eoLain)
            ->getJson("/api/v1/events/{$event->event_id}/forum")
            ->assertNotFound();
    }

    public function test_peran_non_eo_tidak_dapat_membuat_topik(): void
    {
        [, , $event] = $this->buatEventForum();
        $komunitas = User::factory()->komunitas()->create();
        $this->jadikanPeserta($komunitas, $event);

        $this->actingAs($komunitas)
            ->postJson("/api/v1/events/{$event->event_id}/forum", [
                'judul' => 'Topik dari komunitas',
            ])
            ->assertForbidden();
    }

    public function test_topik_yang_tidak_ada_mengembalikan_404(): void
    {
        [$eo] = $this->buatEventForum();

        $this->actingAs($eo)
            ->getJson('/api/v1/forum/999999')
            ->assertNotFound();
    }
}
