<?php

namespace Tests\Feature\Api\V1\Forum;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

// F-07: pengujian lintas peran sesuai technical task dan acceptance criteria.
class ForumPeranApiTest extends TestCase
{
    use ForumTestHelpers;
    use RefreshDatabase;

    public function test_akademisi_dapat_memberi_tanggapan_yang_tampil_di_utas(): void
    {
        [$eo, , $event] = $this->buatEventForum();
        $forum = $this->buatForum($event, $eo);

        $akademisi = User::factory()->akademisi()->create();
        $this->jadikanPeserta($akademisi, $event);

        $this->actingAs($akademisi)
            ->postJson("/api/v1/forum/{$forum->forum_id}/pesan", [
                'isi_pesan' => 'Kajian dampak sosial sudah tersedia.',
            ])
            ->assertCreated()
            ->assertJsonPath('data.pengirim.role', User::ROLE_AKADEMISI);

        $this->actingAs($eo)
            ->getJson("/api/v1/forum/{$forum->forum_id}")
            ->assertOk()
            ->assertJsonPath('data.pesan.0.isi_pesan', 'Kajian dampak sosial sudah tersedia.');
    }

    public function test_admin_hanya_dapat_membaca_forum(): void
    {
        [$eo, , $event] = $this->buatEventForum();
        $forum = $this->buatForum($event, $eo);

        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->getJson("/api/v1/events/{$event->event_id}/forum")
            ->assertOk();

        $this->actingAs($admin)
            ->getJson("/api/v1/forum/{$forum->forum_id}")
            ->assertOk();

        $this->actingAs($admin)
            ->postJson("/api/v1/forum/{$forum->forum_id}/pesan", [
                'isi_pesan' => 'Admin tidak boleh membalas.',
            ])
            ->assertForbidden();
    }

    public function test_pengunjung_tidak_dapat_membuka_forum(): void
    {
        [, , $event] = $this->buatEventForum();
        $pengunjung = User::factory()->pengunjung()->create();

        $this->actingAs($pengunjung)
            ->getJson("/api/v1/events/{$event->event_id}/forum")
            ->assertForbidden();
    }

    public function test_setiap_peran_mitra_yang_bukan_peserta_mendapat_404(): void
    {
        [$eo, , $event] = $this->buatEventForum();
        $forum = $this->buatForum($event, $eo);

        foreach (['komunitas', 'pemda', 'vendor', 'akademisi', 'media'] as $peran) {
            $user = User::factory()->{$peran}()->create();

            $this->actingAs($user)
                ->getJson("/api/v1/events/{$event->event_id}/forum")
                ->assertNotFound();

            $this->actingAs($user)
                ->postJson("/api/v1/forum/{$forum->forum_id}/pesan", [
                    'isi_pesan' => 'Tanggapan dari non-peserta.',
                ])
                ->assertNotFound();
        }
    }
}
