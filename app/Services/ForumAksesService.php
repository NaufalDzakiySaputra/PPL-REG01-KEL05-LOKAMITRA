<?php

namespace App\Services;

use App\Models\Eo;
use App\Models\Event;
use App\Models\KeanggotaanEvent;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

/**
 * Satu-satunya tempat aturan kewenangan forum diskusi (F-07).
 *
 * Peserta forum = anggota event dengan status "diterima" pada
 * keanggotaan_event, ditambah EO pemilik event. Admin hanya membaca.
 * Tabel permintaan_kerja_sama sengaja tidak dipakai sebagai sumber
 * kewenangan agar tetap sinkron dengan dashboard dan papan informasi publik.
 */
class ForumAksesService
{
    /** Status event yang forumnya masih boleh dibuka. */
    private const STATUS_BISA_DIBACA = [
        Event::STATUS_DIPUBLIKASIKAN,
        Event::STATUS_DITUTUP,
        Event::STATUS_DIARSIPKAN,
    ];

    /** Status event yang forumnya masih menerima topik dan tanggapan baru. */
    private const STATUS_BISA_DITULIS = [
        Event::STATUS_DIPUBLIKASIKAN,
    ];

    public function bolehMengakses(User $user, Event $event): bool
    {
        if (! in_array($event->status_event, self::STATUS_BISA_DIBACA, true)) {
            return false;
        }

        if ($user->role === User::ROLE_ADMIN) {
            return true;
        }

        if ($this->adalahPemilikEvent($user, $event)) {
            return true;
        }

        return KeanggotaanEvent::query()
            ->where('event_id', $event->event_id)
            ->where('akun_id', $user->id)
            ->where('status_keanggotaan', KeanggotaanEvent::STATUS_DITERIMA)
            ->exists();
    }

    /**
     * Event tidak tersedia, bukan peserta, atau status tidak dikenal: 404
     * supaya keberadaan forum tidak bocor ke pihak yang tidak berwenang.
     */
    public function pastikanBolehMengakses(User $user, Event $event): void
    {
        abort_unless(
            $this->bolehMengakses($user, $event),
            404,
            'Forum pada event ini tidak ditemukan.'
        );
    }

    /** Topik dan tanggapan hanya bisa ditulis pada event yang masih aktif. */
    public function pastikanBolehMenulis(User $user, Event $event): void
    {
        $this->pastikanBolehMengakses($user, $event);

        abort_if(
            $user->role === User::ROLE_ADMIN,
            403,
            'Admin hanya dapat membaca forum diskusi.'
        );

        abort_unless(
            in_array($event->status_event, self::STATUS_BISA_DITULIS, true),
            403,
            'Forum pada event ini sudah ditutup untuk tanggapan.'
        );
    }

    public function adalahPemilikEvent(User $user, Event $event): bool
    {
        return Eo::query()
            ->where('akun_id', $user->id)
            ->whereKey($event->eo_id)
            ->exists();
    }

    /**
     * Event yang forumnya boleh dibuka oleh akun tersebut.
     *
     * @return Collection<int, Event>
     */
    public function eventsDiikuti(User $user): Collection
    {
        $eventIds = KeanggotaanEvent::query()
            ->where('akun_id', $user->id)
            ->where('status_keanggotaan', KeanggotaanEvent::STATUS_DITERIMA)
            ->pluck('event_id')
            ->all();

        $eoIds = Eo::query()
            ->where('akun_id', $user->id)
            ->pluck('eo_id')
            ->all();

        if ($eoIds !== []) {
            $eventIds = array_merge(
                $eventIds,
                Event::query()->whereIn('eo_id', $eoIds)->pluck('event_id')->all()
            );
        }

        return Event::query()
            ->whereIn('event_id', array_values(array_unique($eventIds)))
            ->whereIn('status_event', self::STATUS_BISA_DIBACA)
            ->latest('tanggal_mulai')
            ->get();
    }
}
