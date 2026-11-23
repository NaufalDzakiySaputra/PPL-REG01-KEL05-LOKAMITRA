<?php

namespace Tests\Feature\Api\V1\Forum;

use App\Models\Event;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

// F-07: aturan status event. Forum hanya ada untuk event yang sudah tayang;
// event ditutup/diarsipkan bersifat baca-saja.
class ForumStatusEventApiTest extends TestCase
{
    use ForumTestHelpers;
    use RefreshDatabase;

    public function test_event_draft_mengembalikan_404(): void
    {
        [$eo, $event] = $this->buatEventDenganStatus(Event::STATUS_DRAFT, 'Event Draft');

        $this->actingAs($eo)
            ->getJson("/api/v1/events/{$event->event_id}/forum")
            ->assertNotFound();
    }

    public function test_event_menunggu_verifikasi_mengembalikan_404(): void
    {
        [$eo, $event] = $this->buatEventDenganStatus(
            Event::STATUS_MENUNGGU_VERIFIKASI,
            'Event Menunggu Verifikasi',
        );

        $this->actingAs($eo)
            ->getJson("/api/v1/events/{$event->event_id}/forum")
            ->assertNotFound();
    }

    public function test_event_ditutup_bisa_dibaca_tapi_tidak_bisa_ditulis(): void
    {
        [$eo, $event] = $this->buatEventDenganStatus(Event::STATUS_DITUTUP, 'Event Ditutup');
        $forum = $this->buatForum($event, $eo);
        $komunitas = User::factory()->komunitas()->create();
        $this->jadikanPeserta($komunitas, $event);

        $this->actingAs($eo)
            ->getJson("/api/v1/events/{$event->event_id}/forum")
            ->assertOk();

        $this->actingAs($eo)
            ->postJson("/api/v1/events/{$event->event_id}/forum", [
                'judul' => 'Topik baru ditolak',
            ])
            ->assertForbidden();

        $this->actingAs($komunitas)
            ->postJson("/api/v1/forum/{$forum->forum_id}/pesan", [
                'isi_pesan' => 'Tanggapan ditolak.',
            ])
            ->assertForbidden();
    }

    public function test_event_diarsipkan_bisa_dibaca_tapi_tidak_bisa_ditulis(): void
    {
        [$eo, $event] = $this->buatEventDenganStatus(Event::STATUS_DIARSIPKAN, 'Event Diarsipkan');
        $forum = $this->buatForum($event, $eo);
        $vendor = User::factory()->vendor()->create();
        $this->jadikanPeserta($vendor, $event);

        $this->actingAs($vendor)
            ->getJson("/api/v1/forum/{$forum->forum_id}")
            ->assertOk();

        $this->actingAs($vendor)
            ->postJson("/api/v1/forum/{$forum->forum_id}/pesan", [
                'isi_pesan' => 'Tanggapan arsip ditolak.',
            ])
            ->assertForbidden();
    }
}
