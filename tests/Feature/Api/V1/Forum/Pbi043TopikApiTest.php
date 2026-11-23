<?php

namespace Tests\Feature\Api\V1\Forum;

use App\Models\Event;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

// F-07 / PBI-043: Event Organizer membuat topik diskusi pada event miliknya.
class Pbi043TopikApiTest extends TestCase
{
    use ForumTestHelpers;
    use RefreshDatabase;

    public function test_eo_dapat_membuat_topik_dan_topik_tampil_di_daftar(): void
    {
        [$eo, , $event] = $this->buatEventForum();

        $respons = $this->actingAs($eo)
            ->postJson("/api/v1/events/{$event->event_id}/forum", [
                'judul' => 'Koordinasi dekorasi venue',
                'deskripsi' => 'Menentukan kebutuhan dekorasi utama.',
            ]);

        $respons->assertCreated()
            ->assertJsonPath('message', 'Topik diskusi berhasil dibuat.')
            ->assertJsonPath('data.judul', 'Koordinasi dekorasi venue')
            ->assertJsonPath('data.event_id', $event->event_id)
            ->assertJsonPath('data.pembuat.id', $eo->id);

        $this->assertDatabaseHas('forum', [
            'event_id' => $event->event_id,
            'pembuat_akun_id' => $eo->id,
            'judul' => 'Koordinasi dekorasi venue',
        ]);

        $daftar = $this->actingAs($eo)
            ->getJson("/api/v1/events/{$event->event_id}/forum");

        $daftar->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.judul', 'Koordinasi dekorasi venue')
            ->assertJsonPath('data.0.pembuat.name', $eo->name);
    }

    public function test_eo_tidak_dapat_membuat_topik_di_event_eo_lain(): void
    {
        [, $event] = $this->buatEventDenganStatus(Event::STATUS_DIPUBLIKASIKAN, 'Event EO Lain');

        $eoLain = User::factory()->eo()->create();

        $this->actingAs($eoLain)
            ->postJson("/api/v1/events/{$event->event_id}/forum", [
                'judul' => 'Topik di event milik EO lain',
            ])
            ->assertNotFound();
    }

    public function test_judul_topik_wajib_diisi(): void
    {
        [$eo, , $event] = $this->buatEventForum();

        $this->actingAs($eo)
            ->postJson("/api/v1/events/{$event->event_id}/forum", [
                'judul' => '',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('judul');
    }
}
