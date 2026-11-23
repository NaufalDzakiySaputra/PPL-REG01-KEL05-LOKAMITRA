<?php

namespace Tests\Feature\Api\V1\Forum;

use App\Models\Eo;
use App\Models\Event;
use App\Models\Forum;
use App\Models\KeanggotaanEvent;
use App\Models\User;

trait ForumTestHelpers
{
    /** @return array{0: User, 1: Eo, 2: Event} */
    private function buatEventForum(): array
    {
        $akun = User::factory()->eo()->create();

        $eo = Eo::create([
            'akun_id' => $akun->id,
            'nama_eo_organisasi' => 'EO Forum Uji',
            'status_verifikasi' => Eo::STATUS_DISETUJUI,
        ]);

        $event = Event::create([
            'eo_id' => $eo->eo_id,
            'nama_event' => 'Event Forum Uji',
            'deskripsi' => 'Event untuk menguji forum diskusi.',
            'lokasi' => 'Purwokerto',
            'kategori_event' => 'Festival Budaya',
            'tanggal_mulai' => now()->addDays(5)->toDateString(),
            'tanggal_selesai' => now()->addDays(6)->toDateString(),
            'status_event' => Event::STATUS_DIPUBLIKASIKAN,
        ]);

        return [$akun, $eo, $event];
    }

    /** @return array{0: User, 1: Event} */
    private function buatEventDenganStatus(string $status, string $nama = 'Event Status Uji'): array
    {
        $akun = User::factory()->eo()->create();

        $eo = Eo::create([
            'akun_id' => $akun->id,
            'nama_eo_organisasi' => 'EO Status Uji',
            'status_verifikasi' => Eo::STATUS_DISETUJUI,
        ]);

        $event = Event::create([
            'eo_id' => $eo->eo_id,
            'nama_event' => $nama,
            'deskripsi' => 'Event untuk menguji status forum.',
            'lokasi' => 'Purwokerto',
            'kategori_event' => 'Festival Budaya',
            'tanggal_mulai' => now()->addDays(5)->toDateString(),
            'tanggal_selesai' => now()->addDays(6)->toDateString(),
            'status_event' => $status,
        ]);

        return [$akun, $event];
    }

    private function buatForum(Event $event, User $pembuat, array $override = []): Forum
    {
        return Forum::create(array_merge([
            'event_id' => $event->event_id,
            'pembuat_akun_id' => $pembuat->id,
            'judul' => 'Koordinasi teknis panggung',
            'deskripsi' => 'Rembugan kebutuhan teknis event.',
            'tanggal_dibuat' => now(),
        ], $override));
    }

    private function jadikanPeserta(User $user, Event $event, string $status = KeanggotaanEvent::STATUS_DITERIMA): void
    {
        KeanggotaanEvent::create([
            'event_id' => $event->event_id,
            'akun_id' => $user->id,
            'status_keanggotaan' => $status,
            'tanggal_bergabung' => now(),
            'tanggal_respons' => now(),
        ]);
    }
}
