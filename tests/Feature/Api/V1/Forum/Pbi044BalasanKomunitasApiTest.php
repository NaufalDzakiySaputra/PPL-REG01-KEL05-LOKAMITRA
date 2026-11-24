<?php

namespace Tests\Feature\Api\V1\Forum;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

// F-07 / PBI-044: Komunitas lokal memberi tanggapan pada topik forum.
class Pbi044BalasanKomunitasApiTest extends TestCase
{
    use ForumTestHelpers;
    use RefreshDatabase;

    public function test_komunitas_dapat_memberi_tanggapan_yang_terlihat_di_utas(): void
    {
        [$eo, , $event] = $this->buatEventForum();
        $forum = $this->buatForum($event, $eo);

        $komunitas = User::factory()->komunitas()->create();
        $this->jadikanPeserta($komunitas, $event);

        $respons = $this->actingAs($komunitas)
            ->postJson("/api/v1/forum/{$forum->forum_id}/pesan", [
                'isi_pesan' => 'Komunitas siap menyiapkan relawan.',
            ]);

        $respons->assertCreated()
            ->assertJsonPath('message', 'Tanggapan berhasil dikirim.')
            ->assertJsonPath('data.isi_pesan', 'Komunitas siap menyiapkan relawan.')
            ->assertJsonPath('data.pengirim.role', User::ROLE_KOMUNITAS);

        $this->assertDatabaseHas('pesan_forum', [
            'forum_id' => $forum->forum_id,
            'pengirim_akun_id' => $komunitas->id,
            'isi_pesan' => 'Komunitas siap menyiapkan relawan.',
        ]);

        $this->actingAs($eo)
            ->getJson("/api/v1/forum/{$forum->forum_id}")
            ->assertOk()
            ->assertJsonPath('data.pesan.0.isi_pesan', 'Komunitas siap menyiapkan relawan.')
            ->assertJsonPath('data.pesan.0.pengirim.name', $komunitas->name);
    }
}
