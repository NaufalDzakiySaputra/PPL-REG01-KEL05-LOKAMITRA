<?php

namespace Tests\Feature\Api\V1\Forum;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

// F-07 / PBI-047: Vendor/UMKM memberi tanggapan yang dapat dibaca peserta lain.
class Pbi047BalasanVendorApiTest extends TestCase
{
    use ForumTestHelpers;
    use RefreshDatabase;

    public function test_balasan_vendor_tersimpan_dan_dapat_dibaca_peserta_lain(): void
    {
        [$eo, , $event] = $this->buatEventForum();
        $forum = $this->buatForum($event, $eo);

        $vendor = User::factory()->vendor()->create();
        $komunitas = User::factory()->komunitas()->create();
        $this->jadikanPeserta($vendor, $event);
        $this->jadikanPeserta($komunitas, $event);

        $this->actingAs($vendor)
            ->postJson("/api/v1/forum/{$forum->forum_id}/pesan", [
                'isi_pesan' => 'Vendor menyediakan konsumsi untuk 200 peserta.',
            ])
            ->assertCreated()
            ->assertJsonPath('data.pengirim.role', User::ROLE_VENDOR);

        $this->actingAs($komunitas)
            ->getJson("/api/v1/forum/{$forum->forum_id}")
            ->assertOk()
            ->assertJsonPath('data.pesan.0.isi_pesan', 'Vendor menyediakan konsumsi untuk 200 peserta.')
            ->assertJsonPath('data.pesan.0.pengirim.name', $vendor->name);
    }
}
