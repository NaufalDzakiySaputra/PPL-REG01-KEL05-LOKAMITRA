<?php

namespace Database\Seeders;

use App\Models\Eo;
use App\Models\Event;
use App\Models\Forum;
use App\Models\PesanForum;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Data demo Forum Diskusi (F-07). Dijalankan setelah DemoEventSeeder karena
 * memakai event utama dan keanggotaan_event yang sudah dibuat di sana.
 * Aman dijalankan berulang (memakai updateOrCreate / firstOrCreate).
 */
class ForumSeeder extends Seeder
{
    public function run(): void
    {
        $eo = Eo::whereHas('akun', fn ($q) => $q->where('email', 'eo@lokamitra.test'))->first();

        if (! $eo) {
            return;
        }

        $event = Event::query()
            ->where('eo_id', $eo->eo_id)
            ->where('nama_event', 'Festival Budaya Banyumas')
            ->first();

        if (! $event) {
            return;
        }

        $akunEo = $eo->akun;

        if (! $akunEo) {
            return;
        }

        $topik = Forum::updateOrCreate(
            [
                'event_id' => $event->event_id,
                'judul' => 'Koordinasi teknis panggung utama',
            ],
            [
                'pembuat_akun_id' => $akunEo->id,
                'deskripsi' => 'Menyepakati kebutuhan tata suara, pencahayaan, dan jadwal technical meeting.',
                'tanggal_dibuat' => now()->subDays(3),
            ]
        );

        $balasan = [
            'pemda@lokamitra.test' => 'Pemda akan memastikan rekomendasi izin keramaian sebelum technical meeting.',
            'vendor@lokamitra.test' => 'Kami kirim penawaran tata suara dan panggung hari ini.',
            'komunitas@lokamitra.test' => 'Komunitas lokal menyiapkan 30 relawan untuk area panggung.',
            'akademisi@lokamitra.test' => 'Tim akademisi menyediakan kajian dampak sosial untuk bahan koordinasi.',
            'media@lokamitra.test' => 'Media menunggu jadwal rilis pers setelah technical meeting.',
        ];

        foreach ($balasan as $email => $isi) {
            $akun = User::where('email', $email)->first();

            if (! $akun) {
                continue;
            }

            PesanForum::firstOrCreate(
                [
                    'forum_id' => $topik->forum_id,
                    'pengirim_akun_id' => $akun->id,
                    'isi_pesan' => $isi,
                ],
                [
                    'waktu_kirim' => now()->subDays(2),
                ]
            );
        }
    }
}
