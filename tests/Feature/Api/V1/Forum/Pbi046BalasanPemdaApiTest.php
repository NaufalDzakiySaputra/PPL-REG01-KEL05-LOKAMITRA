<?php

namespace Tests\Feature\Api\V1\Forum;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

// F-07 / PBI-046: Pemerintah Daerah memberi tanggapan pada topik forum.
class Pbi046BalasanPemdaApiTest extends TestCase
{
    use ForumTestHelpers;
    use RefreshDatabase;

    public function test_pemda_dapat_memberi_tanggapan_yang_terlihat_di_utas(): void
    {
        [$eo, , $event] = $this->buatEventForum();
        $forum = $this->buatForum($event, $eo);

        $pemda = User::factory()->pemda()->create();
        $this->jadikanPeserta($pemda, $event);

        $this->actingAs($pemda)
            ->postJson("/api/v1/forum/{$forum->forum_id}/pesan", [
                'isi_pesan' => 'Pemda akan menyiapkan surat dukungan.',
            ])
            ->assertCreated()
            ->assertJsonPath('data.pengirim.role', User::ROLE_PEMDA);

        $this->assertDatabaseHas('pesan_forum', [
            'forum_id' => $forum->forum_id,
            'pengirim_akun_id' => $pemda->id,
            'isi_pesan' => 'Pemda akan menyiapkan surat dukungan.',
        ]);

        $this->actingAs($eo)
            ->getJson("/api/v1/forum/{$forum->forum_id}")
            ->assertOk()
            ->assertJsonPath('data.pesan.0.isi_pesan', 'Pemda akan menyiapkan surat dukungan.')
            ->assertJsonPath('data.pesan.0.pengirim.name', $pemda->name);
    }
}
