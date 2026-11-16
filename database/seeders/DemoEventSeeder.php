<?php

namespace Database\Seeders;

use App\Models\Eo;
use App\Models\Event;
use App\Models\KeanggotaanEvent;
use App\Models\RiwayatAktivitas;
use App\Models\Timeline;
use App\Models\Tugas;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Data demo agar dashboard terisi seperti rancangan Figma.
 * Jalankan setelah AdminSeeder, EoSeeder, dan AkunContohSeeder.
 * Aman dijalankan berulang (memakai updateOrCreate).
 */
class DemoEventSeeder extends Seeder
{
    public function run(): void
    {
        $eo = Eo::whereHas('akun', fn ($q) => $q->where('email', 'eo@lokamitra.test'))->first();

        if (! $eo) {
            return; // jalankan EoSeeder lebih dulu
        }

        $this->penggunaTambahan();
        $event = $this->event($eo);
        $this->keanggotaanDanTugas($event);
        $this->riwayat($eo);
    }

    // Pengunjung tambahan dengan tanggal daftar tersebar 6 bulan (untuk grafik pertumbuhan).
    private function penggunaTambahan(): void
    {
        $perBulan = [2, 3, 4, 5, 6, 8];

        foreach ($perBulan as $indeks => $jumlah) {
            $bulan = now()->startOfMonth()->subMonthsNoOverflow(5 - $indeks);

            for ($i = 1; $i <= $jumlah; $i++) {
                $akun = User::updateOrCreate(
                    ['email' => "demo.pengunjung.{$indeks}.{$i}@lokamitra.test"],
                    [
                        'name' => "Pengunjung Demo {$indeks}-{$i}",
                        'password' => 'password',
                        'nomor_telepon' => '081200000000',
                        'role' => User::ROLE_PENGUNJUNG,
                        'status_akun' => User::STATUS_AKTIF,
                    ]
                );

                $akun->forceFill(['created_at' => $bulan->copy()->addDays($i)])->saveQuietly();
            }
        }
    }

    // Beberapa event dengan status berbeda; mengembalikan event utama milik EO contoh.
    // Event utama sengaja dibuat sedang berlangsung agar Dashboard EO (yang memilih
    // event aktif) menampilkan data demo lengkap: stakeholder, tugas, dan agenda.
    private function event(Eo $eo): Event
    {
        $daftar = [
            ['Festival Budaya Banyumas', 'Festival Budaya', 'Alun-alun, Banyumas', -1, 2, Event::STATUS_DIPUBLIKASIKAN],
            ['Konser Musik Akhir Tahun', 'Konser Musik', 'GOR Satria, Purwokerto', 60, 60, Event::STATUS_DIPUBLIKASIKAN],
            ['Forum MICE Jawa Tengah', 'MICE', 'Hotel Aston, Purwokerto', 45, 46, Event::STATUS_DIPUBLIKASIKAN],
            ['Pekan Kuliner Nusantara', 'Festival Budaya', 'Taman Andhang Pangrenan, Banyumas', 36, 37, Event::STATUS_DIPUBLIKASIKAN],
            ['Pameran UMKM Banyumas', 'MICE', 'GOR Satria, Purwokerto', 90, 91, Event::STATUS_MENUNGGU_VERIFIKASI],
            ['Workshop Kriya Lokal', 'Festival Budaya', 'Balai Desa Kedungbanteng, Banyumas', 100, 100, Event::STATUS_DRAFT],
            ['Pasar Rakyat Lokal', 'Festival Budaya', 'Alun-alun, Purwokerto', -50, -49, Event::STATUS_DITUTUP],
            ['Karnaval Desa Wisata', 'Festival Budaya', 'Desa Wisata, Banyumas', -120, -119, Event::STATUS_DIARSIPKAN],
        ];

        $utama = null;

        foreach ($daftar as [$nama, $kategori, $lokasi, $mulai, $selesai, $status]) {
            $event = Event::updateOrCreate(
                ['eo_id' => $eo->eo_id, 'nama_event' => $nama],
                [
                    'deskripsi' => "Deskripsi {$nama}.",
                    'lokasi' => $lokasi,
                    'kategori_event' => $kategori,
                    'tanggal_mulai' => today()->addDays($mulai),
                    'tanggal_selesai' => today()->addDays($selesai),
                    'status_event' => $status,
                    'tanggal_ditutup' => $status === Event::STATUS_DITUTUP ? now()->subDays(40) : null,
                    'tanggal_diarsipkan' => $status === Event::STATUS_DIARSIPKAN ? now()->subDays(5) : null,
                ]
            );

            $utama ??= $event;
        }

        return $utama;
    }

    // Stakeholder contoh bergabung di event utama, lengkap dengan tugas dan agenda.
    private function keanggotaanDanTugas(Event $event): void
    {
        $tugasPerAkun = [
            'pemda@lokamitra.test' => [['Surat izin keramaian', 'selesai', 10], ['Koordinasi keamanan', 'selesai', 14], ['Izin penggunaan alun-alun', 'selesai', 9]],
            'vendor@lokamitra.test' => [['Kesepakatan vendor konsumsi', 'berjalan', 5], ['Tata suara dan panggung', 'selesai', 12], ['Dekorasi area utama', 'belum_mulai', 20]],
            'komunitas@lokamitra.test' => [['Briefing komunitas lokal', 'berjalan', 20], ['Susunan penampil', 'selesai', 15]],
            'akademisi@lokamitra.test' => [['Dokumen kajian dampak', 'berjalan', 9], ['Instrumen survei', 'belum_mulai', 25]],
            'media@lokamitra.test' => [['Materi promosi final', 'berjalan', 12], ['Rilis pers', 'selesai', 7]],
        ];

        foreach ($tugasPerAkun as $email => $daftarTugas) {
            $akun = User::where('email', $email)->first();

            if (! $akun) {
                continue;
            }

            KeanggotaanEvent::updateOrCreate(
                ['event_id' => $event->event_id, 'akun_id' => $akun->id],
                [
                    'tanggal_bergabung' => now()->subDays(20),
                    'status_keanggotaan' => KeanggotaanEvent::STATUS_DITERIMA,
                    'pesan_pengajuan' => 'Siap berkolaborasi.',
                    'tanggal_respons' => now()->subDays(19),
                ]
            );

            foreach ($daftarTugas as [$judul, $status, $hari]) {
                Tugas::updateOrCreate(
                    ['event_id' => $event->event_id, 'penerima_akun_id' => $akun->id, 'judul' => $judul],
                    [
                        'deskripsi' => $judul.' untuk '.$event->nama_event.'.',
                        'tanggal_deadline' => now()->addDays($hari),
                        'status_tugas' => $status,
                    ]
                );
            }

            RiwayatAktivitas::firstOrCreate(
                ['akun_id' => $akun->id, 'jenis_aktivitas' => 'bergabung_event'],
                ['deskripsi' => "Bergabung ke event {$event->nama_event}.", 'waktu_aktivitas' => now()->subDays(rand(1, 3))]
            );
        }

        foreach ([['Gladi bersih', 20], ['Pembukaan festival', 36], ['Penutupan dan evaluasi', 37]] as [$judul, $hari]) {
            Timeline::updateOrCreate(
                ['event_id' => $event->event_id, 'judul_aktivitas' => $judul],
                ['deskripsi' => $judul, 'waktu_mulai' => now()->addDays($hari), 'waktu_selesai' => now()->addDays($hari)->addHours(3)]
            );
        }
    }

    private function riwayat(Eo $eo): void
    {
        RiwayatAktivitas::firstOrCreate(
            ['akun_id' => $eo->akun_id, 'jenis_aktivitas' => 'membuat_event'],
            ['deskripsi' => 'Membuat event Festival Budaya Banyumas.', 'waktu_aktivitas' => now()->subDays(25)]
        );
    }
}
