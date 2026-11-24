<?php

namespace Tests\Feature\Api\V1\Forum;

use App\Models\Event;
use App\Models\Forum;
use App\Models\LampiranForum;
use App\Models\PesanForum;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class Pbi045LampiranApiTest extends TestCase
{
    use ForumTestHelpers;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
    }

    private function buatPesan(Forum $forum, User $pengirim): PesanForum
    {
        return PesanForum::create([
            'forum_id' => $forum->forum_id,
            'pengirim_akun_id' => $pengirim->id,
            'isi_pesan' => 'Berikut kajian awal dari kami.',
            'waktu_kirim' => now(),
        ]);
    }

    /** @param array<string, mixed> $override */
    private function unggah(
        User $akun,
        Forum $forum,
        PesanForum $pesan,
        array $override = []
    ) {
        return $this->actingAs($akun)->post(
            "/api/v1/forum/{$forum->forum_id}/lampiran",
            array_merge([
                'pesan_id' => $pesan->pesan_forum_id,
                'file_lampiran' => UploadedFile::fake()->create('kajian.pdf', 20, 'application/pdf'),
            ], $override),
            ['Accept' => 'application/json']
        );
    }

    public function test_akademisi_peserta_dapat_mengunggah_lampiran_valid(): void
    {
        [$eo, , $event] = $this->buatEventForum();
        $forum = $this->buatForum($event, $eo);
        $akademisi = User::factory()->akademisi()->create();
        $this->jadikanPeserta($akademisi, $event);
        $pesan = $this->buatPesan($forum, $akademisi);

        $respons = $this->unggah($akademisi, $forum, $pesan)
            ->assertCreated()
            ->assertJsonPath('message', 'Lampiran berhasil diunggah.')
            ->assertJsonPath('data.nama_file', 'kajian.pdf')
            ->assertJsonPath('data.format_file', 'pdf');

        $lampiran = LampiranForum::findOrFail($respons->json('data.id'));

        $this->assertSame($pesan->pesan_forum_id, $lampiran->pesan_forum_id);
        $this->assertSame($akademisi->id, $lampiran->pengunggah_akun_id);
        $this->assertSame(20 * 1024, $lampiran->ukuran_file);
        $this->assertSame(
            "/api/v1/forum/{$forum->forum_id}/lampiran/{$lampiran->lampiran_id}/download",
            $respons->json('data.url')
        );
        Storage::disk('local')->assertExists($lampiran->lokasi_file);
    }

    public function test_tipe_dan_ukuran_lampiran_tidak_valid_ditolak(): void
    {
        [$eo, , $event] = $this->buatEventForum();
        $forum = $this->buatForum($event, $eo);
        $akademisi = User::factory()->akademisi()->create();
        $this->jadikanPeserta($akademisi, $event);
        $pesan = $this->buatPesan($forum, $akademisi);

        $this->unggah($akademisi, $forum, $pesan, [
            'file_lampiran' => UploadedFile::fake()->create('kajian.txt', 20, 'text/plain'),
        ])->assertUnprocessable()->assertJsonValidationErrors('file_lampiran');

        $this->unggah($akademisi, $forum, $pesan, [
            'file_lampiran' => UploadedFile::fake()->create('kajian.pdf', 6000, 'application/pdf'),
        ])->assertUnprocessable()->assertJsonValidationErrors('file_lampiran');

        $this->assertDatabaseCount('lampiran_forum', 0);
    }

    public function test_lampiran_wajib_menempel_pada_pesan(): void
    {
        [$eo, , $event] = $this->buatEventForum();
        $forum = $this->buatForum($event, $eo);
        $akademisi = User::factory()->akademisi()->create();
        $this->jadikanPeserta($akademisi, $event);
        $pesan = $this->buatPesan($forum, $akademisi);

        $this->unggah($akademisi, $forum, $pesan, ['pesan_id' => null])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('pesan_id');
    }

    public function test_peran_selain_akademisi_tidak_dapat_mengunggah(): void
    {
        [$eo, , $event] = $this->buatEventForum();
        $forum = $this->buatForum($event, $eo);
        $pesan = $this->buatPesan($forum, $eo);

        foreach ([$eo, User::factory()->komunitas()->create(), User::factory()->admin()->create()] as $akun) {
            $this->actingAs($akun)
                ->post("/api/v1/forum/{$forum->forum_id}/lampiran", [
                    'pesan_id' => $pesan->pesan_forum_id,
                    'file_lampiran' => UploadedFile::fake()->create('kajian.pdf', 20, 'application/pdf'),
                ], ['Accept' => 'application/json'])
                ->assertForbidden();
        }

        $this->assertDatabaseCount('lampiran_forum', 0);
    }

    public function test_akademisi_non_peserta_tidak_dapat_mengunggah(): void
    {
        [$eo, , $event] = $this->buatEventForum();
        $forum = $this->buatForum($event, $eo);
        $pesan = $this->buatPesan($forum, $eo);
        $akademisi = User::factory()->akademisi()->create();
        $this->jadikanPeserta($akademisi, $event, 'menunggu');

        $this->unggah($akademisi, $forum, $pesan)->assertNotFound();
    }

    public function test_pesan_dari_forum_lain_tidak_dapat_dilampiri(): void
    {
        [$eo, , $event] = $this->buatEventForum();
        $forum = $this->buatForum($event, $eo);
        $forumLain = $this->buatForum($event, $eo, ['judul' => 'Topik lain']);
        $akademisi = User::factory()->akademisi()->create();
        $this->jadikanPeserta($akademisi, $event);
        $pesanLain = $this->buatPesan($forumLain, $akademisi);

        $this->unggah($akademisi, $forum, $pesanLain)->assertNotFound();
    }

    public function test_peserta_dapat_mengunduh_lampiran(): void
    {
        [$eo, , $event] = $this->buatEventForum();
        $forum = $this->buatForum($event, $eo);
        $akademisi = User::factory()->akademisi()->create();
        $this->jadikanPeserta($akademisi, $event);
        $pesan = $this->buatPesan($forum, $akademisi);
        $url = $this->unggah($akademisi, $forum, $pesan)->json('data.url');

        $komunitas = User::factory()->komunitas()->create();
        $this->jadikanPeserta($komunitas, $event);

        $this->actingAs($komunitas)->get($url)->assertOk();

        $this->actingAs($eo)->get($url)->assertOk();
    }

    public function test_non_peserta_tidak_dapat_mengunduh_lampiran(): void
    {
        [$eo, , $event] = $this->buatEventForum();
        $forum = $this->buatForum($event, $eo);
        $akademisi = User::factory()->akademisi()->create();
        $this->jadikanPeserta($akademisi, $event);
        $pesan = $this->buatPesan($forum, $akademisi);
        $url = $this->unggah($akademisi, $forum, $pesan)->json('data.url');

        $asing = User::factory()->akademisi()->create();

        $this->actingAs($asing)->get($url)->assertNotFound();
    }

    public function test_lampiran_yang_berkasnya_hilang_tidak_dapat_diunduh(): void
    {
        [$eo, , $event] = $this->buatEventForum();
        $forum = $this->buatForum($event, $eo);
        $akademisi = User::factory()->akademisi()->create();
        $this->jadikanPeserta($akademisi, $event);
        $pesan = $this->buatPesan($forum, $akademisi);
        $respons = $this->unggah($akademisi, $forum, $pesan);

        $lampiran = LampiranForum::firstOrFail();
        Storage::disk('local')->delete($lampiran->lokasi_file);

        $this->actingAs($akademisi)
            ->get($respons->json('data.url'))
            ->assertNotFound();
    }

    public function test_event_ditutup_dan_diarsipkan_tidak_menerima_lampiran(): void
    {
        foreach ([Event::STATUS_DITUTUP, Event::STATUS_DIARSIPKAN] as $status) {
            [$eo, $event] = $this->buatEventDenganStatus($status);
            $forum = $this->buatForum($event, $eo);
            $pesan = $this->buatPesan($forum, $eo);
            $akademisi = User::factory()->akademisi()->create();
            $this->jadikanPeserta($akademisi, $event);

            $this->unggah($akademisi, $forum, $pesan)->assertForbidden();
        }
    }

    public function test_lampiran_tampil_pada_utas_diskusi(): void
    {
        [$eo, , $event] = $this->buatEventForum();
        $forum = $this->buatForum($event, $eo);
        $akademisi = User::factory()->akademisi()->create();
        $this->jadikanPeserta($akademisi, $event);
        $pesan = $this->buatPesan($forum, $akademisi);
        $this->unggah($akademisi, $forum, $pesan)->assertCreated();

        $this->actingAs($eo)
            ->get("/api/v1/forum/{$forum->forum_id}")
            ->assertOk()
            ->assertJsonPath('data.pesan.0.lampiran.0.nama_file', 'kajian.pdf')
            ->assertJsonPath('data.pesan.0.lampiran.0.pengunggah.role', 'akademisi');
    }

    public function test_tamu_tidak_dapat_mengunggah_maupun_mengunduh(): void
    {
        [$eo, , $event] = $this->buatEventForum();
        $forum = $this->buatForum($event, $eo);
        $pesan = $this->buatPesan($forum, $eo);

        $this->post("/api/v1/forum/{$forum->forum_id}/lampiran", [
            'pesan_id' => $pesan->pesan_forum_id,
            'file_lampiran' => UploadedFile::fake()->create('kajian.pdf', 20, 'application/pdf'),
        ], ['Accept' => 'application/json'])->assertUnauthorized();

        $this->get("/api/v1/forum/{$forum->forum_id}/lampiran/1/download")
            ->assertUnauthorized();
    }
}
