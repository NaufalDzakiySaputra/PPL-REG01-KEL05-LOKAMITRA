<?php

namespace Tests\Feature\Api\V1\Eo;

use App\Models\Eo;
use App\Models\Event;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EventStoreApiTest extends TestCase
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

    private function payloadValid(array $override = []): array
    {
        return array_merge([
            'nama_event' => 'Banyumas Creative Expo 2026',
            'kategori_event' => 'Festival Budaya',
            'tanggal_mulai' => now()->addDays(10)->toDateString(),
            'tanggal_selesai' => now()->addDays(12)->toDateString(),
            'lokasi' => 'GOR Satria Purwokerto',
            'deskripsi' => 'Pameran kolaborasi pelaku ekonomi kreatif dan UMKM lokal.',
        ], $override);
    }

    public function test_eo_berhasil_membuat_event_baru_dengan_respons_201_dan_struktur_sesuai(): void
    {
        [$user, $eo] = $this->eoAktif();

        $response = $this->actingAs($user)
            ->postJson('/api/v1/eo/events', $this->payloadValid());

        $response->assertCreated()
            ->assertJsonStructure([
                'message',
                'data' => [
                    'id',
                    'nama_event',
                    'kategori_event',
                    'tanggal_mulai',
                    'tanggal_selesai',
                    'periode',
                    'lokasi',
                    'deskripsi',
                    'status',
                    'status_label',
                ],
            ])
            ->assertJsonPath('message', 'Event baru berhasil dibuat dan menunggu verifikasi.')
            ->assertJsonPath('data.nama_event', 'Banyumas Creative Expo 2026')
            ->assertJsonPath('data.lokasi', 'GOR Satria Purwokerto')
            ->assertJsonPath('data.status', Event::STATUS_MENUNGGU_VERIFIKASI)
            ->assertJsonPath('data.status_label', 'Menunggu Verifikasi');

        $this->assertDatabaseHas('event', [
            'nama_event' => 'Banyumas Creative Expo 2026',
            'eo_id' => $eo->eo_id,
            'status_event' => Event::STATUS_MENUNGGU_VERIFIKASI,
            'lokasi' => 'GOR Satria Purwokerto',
        ]);
    }

    public function test_event_baru_otomatis_tidak_tampil_pada_daftar_publik(): void
    {
        [$user] = $this->eoAktif();

        $this->actingAs($user)
            ->postJson('/api/v1/eo/events', $this->payloadValid())
            ->assertCreated();

        $this->assertSame(1, Event::count());
        $this->assertSame(0, Event::tersediaUmum()->count());
        $this->assertSame(0, Event::berjalan()->count());
    }

    public function test_status_dan_eo_id_tidak_dapat_dimanipulasi_lewat_payload(): void
    {
        [$user, $eo] = $this->eoAktif();
        [, $eoLain] = $this->eoAktif();

        $this->actingAs($user)
            ->postJson('/api/v1/eo/events', $this->payloadValid([
                'status_event' => Event::STATUS_DIPUBLIKASIKAN,
                'eo_id' => $eoLain->eo_id,
            ]))
            ->assertCreated();

        $event = Event::firstOrFail();
        $this->assertSame(Event::STATUS_MENUNGGU_VERIFIKASI, $event->status_event);
        $this->assertSame($eo->eo_id, $event->eo_id);
    }

    public function test_pembuatan_event_gagal_validasi_422_jika_field_wajib_kosong(): void
    {
        [$user] = $this->eoAktif();

        $response = $this->actingAs($user)
            ->postJson('/api/v1/eo/events', [
                'nama_event' => '',
                'lokasi' => '',
                'tanggal_mulai' => '',
                'tanggal_selesai' => '',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['nama_event', 'lokasi', 'tanggal_mulai', 'tanggal_selesai']);

        $this->assertDatabaseCount('event', 0);
    }

    public function test_pembuatan_event_gagal_validasi_422_jika_tanggal_selesai_sebelum_tanggal_mulai(): void
    {
        [$user] = $this->eoAktif();

        $response = $this->actingAs($user)
            ->postJson('/api/v1/eo/events', $this->payloadValid([
                'tanggal_mulai' => now()->addDays(10)->toDateString(),
                'tanggal_selesai' => now()->addDays(5)->toDateString(),
            ]));

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['tanggal_selesai']);

        $this->assertDatabaseCount('event', 0);
    }

    public function test_pembuatan_event_gagal_validasi_422_jika_tanggal_mulai_sebelum_hari_ini(): void
    {
        [$user] = $this->eoAktif();

        $response = $this->actingAs($user)
            ->postJson('/api/v1/eo/events', $this->payloadValid([
                'tanggal_mulai' => now()->subDay()->toDateString(),
            ]));

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['tanggal_mulai']);

        $this->assertDatabaseCount('event', 0);
    }

    public function test_akses_ditolak_403_jika_diakses_oleh_user_non_eo(): void
    {
        $vendor = User::factory()->vendor()->create();

        $this->actingAs($vendor)
            ->postJson('/api/v1/eo/events', $this->payloadValid())
            ->assertForbidden();

        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->postJson('/api/v1/eo/events', $this->payloadValid())
            ->assertForbidden();

        $this->assertDatabaseCount('event', 0);
    }

    public function test_akses_ditolak_401_jika_tamu_belum_login(): void
    {
        $this->postJson('/api/v1/eo/events', $this->payloadValid())
            ->assertUnauthorized();

        $this->assertDatabaseCount('event', 0);
    }

    public function test_eo_tanpa_profil_eo_ditolak_403(): void
    {
        $userTanpaProfil = User::factory()->eo()->create();

        $this->actingAs($userTanpaProfil)
            ->postJson('/api/v1/eo/events', $this->payloadValid())
            ->assertForbidden();

        $this->assertDatabaseCount('event', 0);
    }
}
