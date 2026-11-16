<?php

namespace App\Services\Dashboard;

use App\Models\DokumenPersyaratan;
use App\Models\Event;
use App\Models\RiwayatAktivitas;
use App\Models\User;
use App\Support\FormatTanggal;
use Illuminate\Support\Facades\Route;

/**
 * Data Dashboard Admin sesuai Figma: kartu ringkasan, pertumbuhan pengguna,
 * pengguna per peran, "Perlu tindakan", aktivitas terbaru, dan status event.
 * Dipisah dari controller agar bisa dipakai ulang oleh API pada Tahap 8.
 */
class AdminDashboardService
{
    // Singkatan peran untuk label "Akun ...".
    private const PERAN_SINGKAT = [
        User::ROLE_EO => 'EO',
        User::ROLE_VENDOR => 'Vendor',
        User::ROLE_PEMDA => 'Pemda',
        User::ROLE_KOMUNITAS => 'Komunitas',
        User::ROLE_AKADEMISI => 'Akademisi',
        User::ROLE_MEDIA => 'Media',
    ];

    public function data(): array
    {
        $peran = config('lokamitra.peran_registrasi');
        $rolePendaftar = array_values(array_diff(array_keys($peran), [User::ROLE_PENGUNJUNG]));

        $ringkasan = $this->ringkasan($peran, $rolePendaftar);
        $perluTindakan = $this->perluTindakan($rolePendaftar, $ringkasan['menunggu']['total'], $ringkasan['siapArsip']);

        return [
            'tanggal' => FormatTanggal::ringkas(today()),
            'ringkasan' => $ringkasan,
            'pertumbuhan' => $this->pertumbuhan(),
            'penggunaPerPeran' => $this->penggunaPerPeran($peran),
            'perluTindakan' => $perluTindakan,
            'aktivitas' => $this->aktivitas(),
            'statusEvent' => $this->statusEvent(),
        ];
    }

    private function ringkasan(array $peran, array $rolePendaftar): array
    {
        $awalBulan = now()->startOfMonth();
        $akunPlatform = User::query()->whereIn('role', array_keys($peran));

        $berjalan = Event::berjalan()->count();
        $terpublikasi = Event::query()
            ->where('status_event', Event::STATUS_DIPUBLIKASIKAN)
            ->whereDate('tanggal_mulai', '>', today())
            ->count();

        $akunMenunggu = User::query()
            ->whereIn('role', $rolePendaftar)
            ->where('status_akun', User::STATUS_MENUNGGU)
            ->count();

        $eventMenunggu = Event::where('status_event', Event::STATUS_MENUNGGU_VERIFIKASI)->count();

        // Dokumen yang belum diperiksa, hanya milik akun yang masih menunggu verifikasi.
        $dokumenMenunggu = DokumenPersyaratan::query()
            ->where('status_pemeriksaan', DokumenPersyaratan::STATUS_MENUNGGU)
            ->whereIn('akun_id', User::where('status_akun', User::STATUS_MENUNGGU)->select('id'))
            ->count();

        return [
            'totalPengguna' => (clone $akunPlatform)->count(),
            'penggunaBulanIni' => (clone $akunPlatform)->where('created_at', '>=', $awalBulan)->count(),
            'eventAktif' => $berjalan + $terpublikasi,
            'eventBerjalan' => $berjalan,
            'eventTerpublikasi' => $terpublikasi,
            'menunggu' => [
                'total' => $akunMenunggu + $eventMenunggu + $dokumenMenunggu,
                'akun' => $akunMenunggu,
                'event' => $eventMenunggu,
                'dokumen' => $dokumenMenunggu,
            ],
            'eventDiarsipkan' => Event::where('status_event', Event::STATUS_DIARSIPKAN)->count(),
            'eventDiarsipkanBulanIni' => Event::where('status_event', Event::STATUS_DIARSIPKAN)
                ->where('tanggal_diarsipkan', '>=', $awalBulan)
                ->count(),
            'siapArsip' => Event::where('status_event', Event::STATUS_DITUTUP)->count(),
        ];
    }

    // Akun baru per bulan selama 6 bulan terakhir.
    private function pertumbuhan(): array
    {
        $hasil = [];

        for ($i = 5; $i >= 0; $i--) {
            $awal = now()->startOfMonth()->subMonthsNoOverflow($i);
            $akhir = $awal->copy()->endOfMonth();

            $hasil[] = [
                'bulan' => mb_convert_case($awal->copy()->locale('id')->translatedFormat('M'), MB_CASE_TITLE),
                'jumlah' => User::query()
                    ->whereIn('role', array_keys(config('lokamitra.peran_registrasi')))
                    ->whereBetween('created_at', [$awal, $akhir])
                    ->count(),
            ];
        }

        return $hasil;
    }

    private function penggunaPerPeran(array $peran): array
    {
        $baris = collect($peran)->map(fn (string $label, string $role) => [
            'peran' => $label,
            'jumlah' => User::where('role', $role)->count(),
        ])->values();

        $maks = max(1, (int) $baris->max('jumlah'));

        return $baris
            ->map(fn (array $item) => $item + ['persen' => (int) round($item['jumlah'] / $maks * 100)])
            ->all();
    }

    private function perluTindakan(array $rolePendaftar, int $totalMenunggu, int $siapArsip): array
    {
        $item = [];

        $akun = User::query()
            ->with(['eo', 'vendor', 'pemerintahDaerah', 'komunitasLokal', 'akademisi', 'media'])
            ->withCount([
                'dokumenPersyaratan as dokumen_menunggu_count' => fn ($q) => $q->where('status_pemeriksaan', DokumenPersyaratan::STATUS_MENUNGGU),
            ])
            ->whereIn('role', $rolePendaftar)
            ->where('status_akun', User::STATUS_MENUNGGU)
            ->latest()
            ->limit(3)
            ->get();

        foreach ($akun as $user) {
            $item[] = [
                'tag' => 'Akun '.(self::PERAN_SINGKAT[$user->role] ?? ucfirst($user->role)),
                'jenis' => 'akun',
                'judul' => $this->namaProfil($user),
                'keterangan' => 'Mendaftar '.FormatTanggal::ringkas($user->created_at)
                    .($user->dokumen_menunggu_count > 0 ? " · {$user->dokumen_menunggu_count} dokumen belum diperiksa" : ' · dokumen lengkap'),
                'href' => route('admin.pengajuan.show', $user->id, false),
            ];
        }

        $event = Event::with('eo')
            ->where('status_event', Event::STATUS_MENUNGGU_VERIFIKASI)
            ->latest()
            ->limit(2)
            ->get();

        foreach ($event as $e) {
            $item[] = [
                'tag' => 'Event',
                'jenis' => 'event',
                'judul' => $e->nama_event,
                'keterangan' => 'Oleh '.($e->eo?->nama_eo_organisasi ?? '-').' · '.FormatTanggal::ringkas($e->tanggal_mulai),
                // Halaman detail event admin dibuat di Tahap 7; sebelum itu tombol dinonaktifkan.
                'href' => Route::has('admin.events.show') ? route('admin.events.show', $e->event_id, false) : null,
            ];
        }

        $arsip = Event::query()
            ->where('status_event', Event::STATUS_DITUTUP)
            ->latest('tanggal_ditutup')
            ->limit(2)
            ->get();

        foreach ($arsip as $e) {
            $item[] = [
                'tag' => 'Arsip',
                'jenis' => 'arsip',
                'judul' => $e->nama_event,
                'keterangan' => 'Selesai '.FormatTanggal::ringkas($e->tanggal_selesai).' · siap diarsipkan',
                'href' => Route::has('admin.events.show') ? route('admin.events.show', $e->event_id, false) : null,
            ];
        }

        return [
            'total' => $totalMenunggu + $siapArsip,
            'item' => array_slice($item, 0, 5),
        ];
    }

    private function aktivitas(): array
    {
        return RiwayatAktivitas::with('akun:id,name')
            ->latest('waktu_aktivitas')
            ->limit(6)
            ->get()
            ->map(fn (RiwayatAktivitas $r) => [
                'oleh' => $r->akun?->name ?? 'Sistem',
                'teks' => $r->deskripsi,
                'waktu' => FormatTanggal::denganJam($r->waktu_aktivitas),
                // Indeks warna titik (0-5), dipilih di Vue.
                'warna' => crc32($r->jenis_aktivitas) % 6,
            ])
            ->all();
    }

    private function statusEvent(): array
    {
        $selesai = Event::whereIn('status_event', [Event::STATUS_DITUTUP, Event::STATUS_DIARSIPKAN])->count()
            + Event::where('status_event', Event::STATUS_DIPUBLIKASIKAN)->whereDate('tanggal_selesai', '<', today())->count();

        $baris = [
            ['label' => 'Berjalan', 'kunci' => 'berjalan', 'jumlah' => Event::berjalan()->count()],
            ['label' => 'Dipublikasikan', 'kunci' => 'dipublikasikan', 'jumlah' => Event::where('status_event', Event::STATUS_DIPUBLIKASIKAN)->whereDate('tanggal_mulai', '>', today())->count()],
            ['label' => 'Menunggu verifikasi', 'kunci' => 'menunggu', 'jumlah' => Event::where('status_event', Event::STATUS_MENUNGGU_VERIFIKASI)->count()],
            ['label' => 'Selesai/Arsip', 'kunci' => 'selesai', 'jumlah' => $selesai],
        ];

        $total = max(1, array_sum(array_column($baris, 'jumlah')));

        return array_map(
            fn (array $b) => $b + ['persen' => round($b['jumlah'] / $total * 100, 1)],
            $baris
        );
    }

    // Nama profil sesuai role (nama usaha, instansi, dst.), cadangan: nama akun.
    private function namaProfil(User $user): string
    {
        return match ($user->role) {
            User::ROLE_EO => $user->eo?->nama_eo_organisasi,
            User::ROLE_VENDOR => $user->vendor?->nama_usaha,
            User::ROLE_PEMDA => $user->pemerintahDaerah?->nama_instansi,
            User::ROLE_KOMUNITAS => $user->komunitasLokal?->nama_komunitas,
            User::ROLE_AKADEMISI => $user->akademisi?->nama_institusi,
            User::ROLE_MEDIA => $user->media?->nama_media,
            default => null,
        } ?? $user->name;
    }
}
