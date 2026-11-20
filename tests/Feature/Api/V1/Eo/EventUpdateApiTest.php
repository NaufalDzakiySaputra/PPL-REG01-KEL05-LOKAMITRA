<?php

namespace Tests\Feature\Api\V1\Eo;

use App\Models\Eo;
use App\Models\Event;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EventUpdateApiTest extends TestCase
{
    use RefreshDatabase;

    private function eoAktif(): array
    {
        $user = User::factory()->eo()->create();
        $eo = Eo::create([
            'akun_id' => $user->id,
            'nama_eo_organisasi' => 'EO Banyumas Creative',
            'status_verifikasi' => Eo::STATUS_DISETUJUI,
        ]);

        return [$user, $eo];
    }

    private function buatEvent(Eo $eo, array $override = []): Event
    {
        return Event::create(array_merge([
            'eo_id' => $eo->eo_id,
            'nama_event' => 'Festival Budaya Asli',
            'kategori_event' => 'Festival Budaya',
            'tanggal_mulai' => now()->addDays(10)->toDateString(),
            'tanggal_selesai' => now()->addDays(12)->toDateString(),
            'lokasi' => 'Alun-alun Purwokerto',
            'deskripsi' => 'Deskripsi awal event.',
            'status_event' => Event::STATUS_MENUNGGU_VERIFIKASI,
        ], $override));
    }

    public function test_eo_pemilik_berhasil_memperbarui_event(): void
    {
        [$user, $eo] = $this->eoAktif();
        $event = $this->buatEvent($eo);

        $response = $this->actingAs($user)->putJson("/api/v1/eo/events/{$event->event_id}", [
            'nama_event' => 'Festival Budaya Banyumas Diperbarui',
            'kategori_event' => 'Festival Budaya',
            'tanggal_mulai' => now()->addDays(11)->toDateString(),
            'tanggal_selesai' => now()->addDays(13)->toDateString(),
            'lokasi' => 'GOR Satria Purwokerto',
            'deskripsi' => 'Deskripsi event yang telah diperbarui.',
        ]);

        $response->assertOk()
            ->assertJsonPath('message', 'Informasi event berhasil diperbarui.')
            ->assertJsonPath('data.id', $event->event_id)
            ->assertJsonPath('data.nama_event', 'Festival Budaya Banyumas Diperbarui')
            ->assertJsonPath('data.lokasi', 'GOR Satria Purwokerto');

        $this->assertDatabaseHas('event', [
            'event_id' => $event->event_id,
            'nama_event' => 'Festival Budaya Banyumas Diperbarui',
            'lokasi' => 'GOR Satria Purwokerto',
        ]);
    }

    public function test_eo_lain_ditolak_403_saat_mencoba_mengubah_event(): void
    {
        [$userPemilik, $eoPemilik] = $this->eoAktif();
        [$userLain] = $this->eoAktif();
        $event = $this->buatEvent($eoPemilik);

        $this->actingAs($userLain)->putJson("/api/v1/eo/events/{$event->event_id}", [
            'nama_event' => 'Pembajakan Event',
            'tanggal_mulai' => now()->addDays(10)->toDateString(),
            'tanggal_selesai' => now()->addDays(12)->toDateString(),
            'lokasi' => 'Lokasi Bajakan',
        ])->assertForbidden();

        $this->assertDatabaseMissing('event', [
            'nama_event' => 'Pembajakan Event',
        ]);
    }

    public function test_pembaruan_event_gagal_validasi_422(): void
    {
        [$user, $eo] = $this->eoAktif();
        $event = $this->buatEvent($eo);

        $response = $this->actingAs($user)->putJson("/api/v1/eo/events/{$event->event_id}", [
            'nama_event' => '',
            'lokasi' => '',
            'tanggal_mulai' => now()->addDays(10)->toDateString(),
            'tanggal_selesai' => now()->addDays(5)->toDateString(),
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['nama_event', 'lokasi', 'tanggal_selesai']);
    }

    public function test_non_eo_ditolak_403_saat_mengubah_event(): void
    {
        [, $eo] = $this->eoAktif();
        $event = $this->buatEvent($eo);

        $vendor = User::factory()->vendor()->create();
        $this->actingAs($vendor)->putJson("/api/v1/eo/events/{$event->event_id}", [
            'nama_event' => 'Ubah oleh Vendor',
            'tanggal_mulai' => now()->addDays(10)->toDateString(),
            'tanggal_selesai' => now()->addDays(12)->toDateString(),
            'lokasi' => 'Lokasi Baru',
        ])->assertForbidden();

        $admin = User::factory()->admin()->create();
        $this->actingAs($admin)->putJson("/api/v1/eo/events/{$event->event_id}", [
            'nama_event' => 'Ubah oleh Admin',
            'tanggal_mulai' => now()->addDays(10)->toDateString(),
            'tanggal_selesai' => now()->addDays(12)->toDateString(),
            'lokasi' => 'Lokasi Baru',
        ])->assertForbidden();
    }

    public function test_tamu_ditolak_401_saat_mengubah_event(): void
    {
        [, $eo] = $this->eoAktif();
        $event = $this->buatEvent($eo);

        $this->putJson("/api/v1/eo/events/{$event->event_id}", [
            'nama_event' => 'Ubah oleh Tamu',
            'tanggal_mulai' => now()->addDays(10)->toDateString(),
            'tanggal_selesai' => now()->addDays(12)->toDateString(),
            'lokasi' => 'Lokasi Baru',
        ])->assertUnauthorized();
    }

    public function test_event_yang_sudah_ditutup_tidak_dapat_diubah(): void
    {
        [$user, $eo] = $this->eoAktif();
        $event = $this->buatEvent($eo, [
            'status_event' => Event::STATUS_DITUTUP,
        ]);

        $this->actingAs($user)->putJson("/api/v1/eo/events/{$event->event_id}", [
            'nama_event' => 'Ubah Event Ditutup',
            'tanggal_mulai' => now()->addDays(10)->toDateString(),
            'tanggal_selesai' => now()->addDays(12)->toDateString(),
            'lokasi' => 'Lokasi Baru',
        ])->assertForbidden();
    }

    public function test_eo_dapat_melihat_detail_event_miliknya(): void
    {
        [$user, $eo] = $this->eoAktif();
        $event = $this->buatEvent($eo);

        $response = $this->actingAs($user)->getJson("/api/v1/eo/events/{$event->event_id}");

        $response->assertOk()
            ->assertJsonPath('data.id', $event->event_id)
            ->assertJsonPath('data.nama_event', $event->nama_event);
    }
}
