<?php

namespace Tests\Feature\Api\V1\Forum;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

// F-07 / PBI-048: Media memberi tanggapan sehingga masuk riwayat diskusi.
class Pbi048BalasanMediaApiTest extends TestCase
{
    use ForumTestHelpers;
    use RefreshDatabase;

    public function test_tanggapan_media_masuk_riwayat_utas(): void
    {
        [$eo, , $event] = $this->buatEventForum();
        $forum = $this->buatForum($event, $eo);

        $media = User::factory()->media()->create();
        $this->jadikanPeserta($media, $event);

        $this->actingAs($media)
            ->postJson("/api/v1/forum/{$forum->forum_id}/pesan", [
                'isi_pesan' => 'Media akan mengangkat liputan kolaborasi ini.',
            ])
            ->assertCreated()
            ->assertJsonPath('data.pengirim.role', User::ROLE_MEDIA);

        $riwayat = $this->actingAs($eo)
            ->getJson("/api/v1/forum/{$forum->forum_id}");

        $riwayat->assertOk()
            ->assertJsonCount(1, 'data.pesan')
            ->assertJsonPath('data.pesan.0.isi_pesan', 'Media akan mengangkat liputan kolaborasi ini.')
            ->assertJsonPath('data.pesan.0.pengirim.name', $media->name);
    }
}
