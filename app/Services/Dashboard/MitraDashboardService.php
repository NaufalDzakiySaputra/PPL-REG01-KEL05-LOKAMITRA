<?php

namespace App\Services\Dashboard;

use App\Models\Event;
use App\Models\KeanggotaanEvent;
use App\Models\RiwayatAktivitas;
use App\Models\Tugas;
use App\Models\User;
use App\Services\RoleProfileService;
use App\Support\FormatTanggal;

/**
 * Data dashboard untuk Vendor, Pemda, Komunitas, Akademisi, dan Media.
 * Belum ada desain Figma untuk kelima role ini, jadi tampilannya mengikuti
 * gaya dashboard EO dengan isi yang relevan untuk mitra (tugas dan event).
 */
class MitraDashboardService
{
    private const RELASI = [
        User::ROLE_VENDOR => 'vendor',
        User::ROLE_PEMDA => 'pemerintahDaerah',
        User::ROLE_KOMUNITAS => 'komunitasLokal',
        User::ROLE_AKADEMISI => 'akademisi',
        User::ROLE_MEDIA => 'media',
    ];

    public function __construct(private readonly RoleProfileService $profilService) {}

    public function data(User $user): array
    {
        $tugas = Tugas::query()->where('penerima_akun_id', $user->id);
        $keanggotaan = KeanggotaanEvent::query()->where('akun_id', $user->id);

        $tugasTotal = (clone $tugas)->count();
        $tugasSelesai = (clone $tugas)->where('status_tugas', Tugas::STATUS_SELESAI)->count();

        $idEventTerkait = (clone $keanggotaan)->pluck('event_id');

        return [
            'peran' => config("lokamitra.peran_registrasi.{$user->role}", ucfirst($user->role)),
            'statusVerifikasi' => $this->profilModel($user)?->status_verifikasi,
            'profil' => $this->profil($user),
            'statistik' => [
                'tugasTotal' => $tugasTotal,
                'tugasSelesai' => $tugasSelesai,
                'tugasTerlambat' => (clone $tugas)
                    ->where('status_tugas', '!=', Tugas::STATUS_SELESAI)
                    ->where('tanggal_deadline', '<', now())
                    ->count(),
                'eventDiikuti' => (clone $keanggotaan)->where('status_keanggotaan', KeanggotaanEvent::STATUS_DITERIMA)->count(),
                'pengajuanMenunggu' => (clone $keanggotaan)->where('status_keanggotaan', KeanggotaanEvent::STATUS_MENUNGGU)->count(),
                'eventTersedia' => Event::tersediaUmum()->whereNotIn('event_id', $idEventTerkait)->count(),
            ],
            'tugasTerdekat' => (clone $tugas)
                ->with('event:event_id,nama_event')
                ->where('status_tugas', '!=', Tugas::STATUS_SELESAI)
                ->orderBy('tanggal_deadline')
                ->limit(4)
                ->get()
                ->map(fn (Tugas $t) => [
                    'judul' => $t->judul,
                    'event' => $t->event?->nama_event,
                    'status' => $t->status_tugas,
                ] + FormatTanggal::lencana($t->tanggal_deadline))
                ->all(),
            'eventDiikuti' => (clone $keanggotaan)
                ->with('event')
                ->where('status_keanggotaan', KeanggotaanEvent::STATUS_DITERIMA)
                ->limit(5)
                ->get()
                ->filter(fn (KeanggotaanEvent $k) => $k->event !== null)
                ->map(fn (KeanggotaanEvent $k) => [
                    'id' => $k->event->event_id,
                    'nama' => $k->event->nama_event,
                    'lokasi' => $k->event->lokasi,
                    'tanggal' => FormatTanggal::ringkas($k->event->tanggal_mulai),
                ])
                ->values()
                ->all(),
            'eventTersedia' => Event::tersediaUmum()
                ->whereNotIn('event_id', $idEventTerkait)
                ->orderBy('tanggal_mulai')
                ->limit(3)
                ->get()
                ->map(fn (Event $e) => [
                    'id' => $e->event_id,
                    'nama' => $e->nama_event,
                    'lokasi' => $e->lokasi,
                    'tanggal' => FormatTanggal::ringkas($e->tanggal_mulai),
                ])
                ->all(),
            'aktivitas' => RiwayatAktivitas::query()
                ->where('akun_id', $user->id)
                ->latest('waktu_aktivitas')
                ->limit(4)
                ->get()
                ->map(fn (RiwayatAktivitas $r) => [
                    'teks' => $r->deskripsi,
                    'waktu' => FormatTanggal::relatif($r->waktu_aktivitas),
                ])
                ->all(),
        ];
    }

    private function profilModel(User $user)
    {
        $relasi = self::RELASI[$user->role] ?? null;

        return $relasi ? $user->{$relasi} : null;
    }

    // Pasangan label-nilai profil; aman bila profil belum ada.
    private function profil(User $user): array
    {
        $model = $this->profilModel($user);

        return collect($this->profilService->definitions($user))
            ->map(fn (array $field, string $kunci) => [
                'label' => $field['label'],
                'value' => $model?->{$kunci},
            ])
            ->filter(fn (array $item) => $item['value'] !== null && $item['value'] !== '')
            ->map(fn (array $item) => ['label' => $item['label'], 'value' => (string) $item['value']])
            ->values()
            ->all();
    }
}
