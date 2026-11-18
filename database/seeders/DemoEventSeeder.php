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
    private function event(Eo $eo): Event
    {
        $daftar = [
            ['Festival Budaya Banyumas', 'Festival Budaya', 'Alun-alun, Banyumas', 36, 37, Event::STATUS_DIPUBLIKASIKAN, <<<'TEKS'
Festival Budaya Banyumas adalah perayaan tahunan yang menampilkan kekayaan
budaya daerah Banyumas dalam satu panggung besar. Pengunjung dapat menikmati
pertunjukan slawat kreasi, ebeg, angguk, akulturasi budaya, serta pameran
kerajinan tangan para perajin lokal.

Selain rangkaian pagelaran, festival ini juga menghadirkan Kuliner Bumi
Ayem, panggung musik akustik, dan ruang edukasi budaya untuk pelajar.
Acara ini terbuka untuk umum dan diperkirakan dihadiri ribuan pengunjung
dari berbagai daerah di Jawa Tengah.
TEKS],
            ['Konser Musik Akhir Tahun', 'Konser Musik', 'GOR Satria, Purwokerto', 60, 60, Event::STATUS_DIPUBLIKASIKAN, <<<'TEKS'
Konser Musik Akhir Tahun menghadirkan deretan musisi lokal dan nasional
untuk merayakan pergantian tahun bersama. Panggung selama tiga jam akan
menampilkan balada, rock, hingga musik tradisional Banyumas.

Disediakan juga area kuliner, booth merchandise resmi, dan zona ramah
disabilitas. Pendaftaran peserta gratis dengan kuota terbatas.
TEKS],
            ['Forum MICE Jawa Tengah', 'MICE', 'Hotel Aston, Purwokerto', 45, 46, Event::STATUS_DIPUBLIKASIKAN, <<<'TEKS'
Forum MICE Jawa Tengah menghimpun pelaku pariwisata, penyelenggara acara,
pengelola hotel, dan pemangku kepentingan daerah untuk membahas pengembangan
ekosistem Meetings, Incentives, Conferences, and Exhibitions di Jawa Tengah.

Rangkaian kegiatan mencakup seminar, focus group discussion, business
matching, dan pameran jasa penyelenggaraan acara daerah.
TEKS],
            ['Pekan Kuliner Nusantara', 'Festival Budaya', 'Taman Andhang Pangrenan, Banyumas', -1, 2, Event::STATUS_DIPUBLIKASIKAN, <<<'TEKS'
Pekan Kuliner Nusantara mempersembahkan lebih dari 60 stand kuliner khas
nusantara, dari makanan tradisional Banyumas hingga menu khas daerah lain.
Ada juga lomba memasak, kelas memasak bersama chef, dan demo pembuatan
makanan lokal yang bisa diikuti seluruh pengunjung.
TEKS],
            ['Pameran UMKM Banyumas', 'MICE', 'GOR Satria, Purwokerto', 90, 91, Event::STATUS_MENUNGGU_VERIFIKASI, <<<'TEKS'
Pameran UMKM Banyumas menampilkan produk unggulan usaha mikro, kecil, dan
menengah dari seluruh kabupaten. Pengunjung dapat berbelanja langsung,
menjalin kemitraan usaha, dan mengikuti sesi pemberdayaan pelaku UMKM.
TEKS],
            ['Workshop Kriya Lokal', 'Festival Budaya', 'Balai Desa Kedungbanteng, Banyumas', 100, 100, Event::STATUS_DRAFT, <<<'TEKS'
Workshop Kriya Lokal mengajak masyarakat belajar langsung dari perajin
Banyumas: pembuatan batik biron, anyaman bambu, dan keramik. Kuota terbatas
agar setiap peserta mendapat pendampingan intensif.
TEKS],
            ['Pasar Rakyat Lokal', 'Festival Budaya', 'Alun-alun, Purwokerto', -50, -49, Event::STATUS_DITUTUP, <<<'TEKS'
Pasar Rakyat Lokal merupakan bazar ekonomi kreatif yang menampung petani,
nelayan, dan pelaku usaha kecil untuk memasarkan produk langsung ke
konsumen dengan harga wajar.
TEKS],
            ['Karnaval Desa Wisata', 'Festival Budaya', 'Desa Wisata, Banyumas', -120, -119, Event::STATUS_DIARSIPKAN, <<<'TEKS'
Karnaval Desa Wisata menampilkan arak-arakan busana, organisasi seni desa,
dan potensi wisata tiap desa di Banyumas sebagai bentuk apresiasi
kearifan lokal.
TEKS],
        ];

        $utama = null;

        foreach ($daftar as [$nama, $kategori, $lokasi, $mulai, $selesai, $status, $deskripsi]) {
            $event = Event::updateOrCreate(
                ['eo_id' => $eo->eo_id, 'nama_event' => $nama],
                [
                    'deskripsi' => $deskripsi,
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
