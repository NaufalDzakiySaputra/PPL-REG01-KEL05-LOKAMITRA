<?php

namespace App\Services\Dashboard;

use App\Models\Event;
use App\Models\KeanggotaanEvent;
use App\Models\RiwayatAktivitas;
use App\Models\Tugas;
use App\Models\User;
use App\Support\FormatTanggal;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Route;

/**
 * Data Dashboard EO sesuai Figma, dihitung untuk SATU event aktif milik EO:
 * kartu ringkasan, kelengkapan persiapan, tenggat terdekat, progres tugas
 * per stakeholder, dan aktivitas terbaru.
 */
class EoDashboardService
{
    // Peran stakeholder beserta judul baris "Kelengkapan persiapan".
    private const KEBUTUHAN = [
        User::ROLE_PEMDA => 'Perizinan acara',
        User::ROLE_VENDOR => 'Vendor dan UMKM',
        User::ROLE_KOMUNITAS => 'Peran komunitas lokal',
        User::ROLE_AKADEMISI => 'Kajian akademisi',
        User::ROLE_MEDIA => 'Publikasi media',
    ];

    public function data(User $user): array
    {
        $eo = $user->eo;
        $event = $eo ? $this->eventAktif($eo->eo_id) : null;

        $dasar = [
            'notifikasiBelumDibaca' => $user->notifikasi()->where('status_baca', 'belum_dibaca')->count(),
            'profil' => [
                'nama' => $eo?->nama_eo_organisasi,
                'status_verifikasi' => $eo?->status_verifikasi,
            ],
        ];

        if (! $event) {
            return $dasar + ['event' => null];
        }

        $anggota = KeanggotaanEvent::query()
            ->with('akun:id,name,role')
            ->where('event_id', $event->event_id)
            ->where('status_keanggotaan', KeanggotaanEvent::STATUS_DITERIMA)
            ->get();

        $tugas = Tugas::query()
            ->with('penerima:id,name,role')
            ->where('event_id', $event->event_id)
            ->get();

        $kebutuhan = $this->kebutuhan($event, $anggota, $tugas);
        $siap = collect($kebutuhan)->where('siap', true)->count();

        $tugasSelesai = $tugas->where('status_tugas', Tugas::STATUS_SELESAI)->count();

        return $dasar + [
            'event' => [
                'id' => $event->event_id,
                'nama' => $event->nama_event,
                'kategori' => $event->kategori_event,
                'lokasi' => $event->lokasi,
                'status' => $event->status_event,
                'tanggalMulai' => FormatTanggal::panjang($event->tanggal_mulai),
                'hariMenuju' => $this->hariMenuju($event),
                'href' => Route::has('eo.events.show') ? route('eo.events.show', $event->event_id, false) : null,
            ],
            'ringkasan' => [
                'persiapanPersen' => (int) round($siap / max(1, count($kebutuhan)) * 100),
                'kebutuhanSiap' => $siap,
                'kebutuhanTotal' => count($kebutuhan),
                'tugasSelesai' => $tugasSelesai,
                'tugasTotal' => $tugas->count(),
                'tugasBerjalan' => $tugas->count() - $tugasSelesai,
                'stakeholder' => $anggota->count(),
                'jenisStakeholder' => $anggota->pluck('akun.role')->unique()->count(),
            ],
            'kebutuhan' => $kebutuhan,
            'tenggat' => $this->tenggat($tugas),
            'progresStakeholder' => $this->progresStakeholder($tugas),
            'aktivitas' => $this->aktivitas($user, $anggota),
        ];
    }

    // Event aktif: yang sedang/akan berlangsung paling dekat, cadangan: event terdekat apa pun.
    private function eventAktif(int $eoId): ?Event
    {
        $kandidat = Event::query()
            ->where('eo_id', $eoId)
            ->whereNotIn('status_event', [Event::STATUS_DITUTUP, Event::STATUS_DIARSIPKAN])
            ->orderBy('tanggal_mulai')
            ->get();

        return $kandidat->first(
            fn (Event $e) => $e->status_event === Event::STATUS_DIPUBLIKASIKAN
                && $e->tanggal_selesai->greaterThanOrEqualTo(today())
        ) ?? $kandidat->first();
    }

    // Selisih hari dari hari ini ke tanggal mulai (0 jika sudah mulai).
    private function hariMenuju(Event $event): int
    {
        $selisih = $event->tanggal_mulai->copy()->startOfDay()->getTimestamp() - today()->getTimestamp();

        return max(0, (int) round($selisih / 86400));
    }

    // Satu baris per kebutuhan: 5 stakeholder + rundown dari timeline.
    private function kebutuhan(Event $event, Collection $anggota, Collection $tugas): array
    {
        $baris = [];

        foreach (self::KEBUTUHAN as $role => $judul) {
            $jumlahAnggota = $anggota->where('akun.role', $role)->count();
            $tugasRole = $tugas->where('penerima.role', $role);
            $selesai = $tugasRole->where('status_tugas', Tugas::STATUS_SELESAI)->count();

            if ($tugasRole->count() > 0) {
                $status = $selesai === $tugasRole->count() ? 'selesai' : 'berjalan';
                $keterangan = "{$selesai} dari {$tugasRole->count()} tugas selesai";
            } elseif ($jumlahAnggota > 0) {
                $status = 'terkonfirmasi';
                $keterangan = "{$jumlahAnggota} stakeholder sudah bergabung";
            } else {
                $status = 'menunggu';
                $keterangan = 'Belum ada stakeholder yang bergabung';
            }

            $baris[] = [
                'judul' => $judul,
                'keterangan' => $keterangan,
                'status' => $status,
                'siap' => in_array($status, ['selesai', 'terkonfirmasi'], true),
            ];
        }

        $agenda = $event->timeline()->count();
        $baris[] = [
            'judul' => 'Rundown dan jadwal',
            'keterangan' => $agenda > 0 ? "{$agenda} agenda tersusun" : 'Belum ada agenda',
            'status' => $agenda > 0 ? 'tersusun' : 'draft',
            'siap' => $agenda > 0,
        ];

        return $baris;
    }

    // Empat tugas belum selesai dengan batas waktu terdekat.
    private function tenggat(Collection $tugas): array
    {
        return $tugas
            ->where('status_tugas', '!=', Tugas::STATUS_SELESAI)
            ->filter(fn (Tugas $t) => $t->tanggal_deadline->greaterThanOrEqualTo(now()->startOfDay()))
            ->sortBy('tanggal_deadline')
            ->take(4)
            ->map(fn (Tugas $t) => [
                'judul' => $t->judul,
                'untuk' => config('lokamitra.peran_registrasi.'.$t->penerima?->role, '-'),
            ] + FormatTanggal::lencana($t->tanggal_deadline))
            ->values()
            ->all();
    }

    private function progresStakeholder(Collection $tugas): array
    {
        $hasil = [];

        foreach (self::KEBUTUHAN as $role => $judul) {
            $tugasRole = $tugas->where('penerima.role', $role);
            $selesai = $tugasRole->where('status_tugas', Tugas::STATUS_SELESAI)->count();
            $total = $tugasRole->count();

            $hasil[] = [
                'peran' => config("lokamitra.peran_registrasi.{$role}"),
                'selesai' => $selesai,
                'total' => $total,
                'persen' => $total > 0 ? (int) round($selesai / $total * 100) : 0,
            ];
        }

        return $hasil;
    }

    // Aktivitas milik EO dan seluruh stakeholder yang tergabung di event ini.
    private function aktivitas(User $eoUser, Collection $anggota): array
    {
        $idAkun = $anggota->pluck('akun_id')->push($eoUser->id)->unique()->all();

        return RiwayatAktivitas::with('akun:id,name')
            ->whereIn('akun_id', $idAkun)
            ->latest('waktu_aktivitas')
            ->limit(4)
            ->get()
            ->map(fn (RiwayatAktivitas $r) => [
                'oleh' => $r->akun?->name ?? 'Sistem',
                'teks' => $r->deskripsi,
                'waktu' => FormatTanggal::relatif($r->waktu_aktivitas),
            ])
            ->all();
    }
}
