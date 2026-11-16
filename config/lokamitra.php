<?php

return [
    // Peran yang dapat dipilih pada registrasi publik.
    'peran_registrasi' => [
        'eo' => 'Event Organizer (EO)',
        'vendor' => 'Vendor/UMKM',
        'pemda' => 'Pemerintah Daerah',
        'komunitas' => 'Komunitas Lokal',
        'akademisi' => 'Akademisi',
        'media' => 'Media',
        'pengunjung' => 'Pengunjung',
    ],

    'kategori_event' => [
        'Festival Budaya',
        'Konser Musik',
        'MICE',
        'Pameran',
        'Workshop',
        'Olahraga',
        'Lainnya',
    ],

    // Informasi panel kiri pada halaman registrasi, disesuaikan dengan Figma.
    'registrasi_info' => [
        'eo' => [
            'title' => 'Daftar sebagai Event Organizer',
            'description' => 'Untuk penyelenggara event yang ingin mengelola perencanaan, koordinasi, dan pelaksanaan acara bersama mitra.',
            'checklist' => [
                'Profil organisasi atau penyelenggara event',
                'Informasi jenis event yang ditangani',
                'Alamat dan kanal komunikasi yang aktif',
            ],
        ],
        'vendor' => [
            'title' => 'Daftar sebagai Vendor/UMKM',
            'description' => 'Untuk pelaku usaha penyedia kebutuhan event seperti konsumsi, tata suara, dekorasi, dan dokumentasi yang ingin bekerja sama dengan penyelenggara.',
            'checklist' => [
                'NIB atau surat keterangan usaha',
                'Identitas pemilik',
                'Profil atau katalog layanan',
                'Foto produk atau portofolio',
            ],
        ],
        'pemda' => [
            'title' => 'Daftar sebagai Pemerintah Daerah',
            'description' => 'Untuk perwakilan instansi atau dinas yang memberikan perizinan, pendampingan, dan dukungan penyelenggaraan event di daerah.',
            'checklist' => [
                'Surat tugas atau surat penunjukan',
                'Identitas pendaftar (KTP atau ID Pegawai)',
            ],
        ],
        'komunitas' => [
            'title' => 'Daftar sebagai Komunitas Lokal',
            'description' => 'Untuk kelompok masyarakat, seniman, atau pegiat budaya setempat yang ingin berpartisipasi dalam penyelenggaraan event di daerahnya.',
            'checklist' => [
                'Surat keterangan komunitas atau bukti kegiatan',
                'Identitas ketua atau penanggung jawab',
                'Foto kegiatan komunitas',
            ],
        ],
        'akademisi' => [
            'title' => 'Daftar sebagai Akademisi',
            'description' => 'Untuk dosen, peneliti, atau mahasiswa pendamping dari perguruan tinggi dan lembaga riset yang memberikan kajian dan pendampingan event.',
            'checklist' => [
                'Kartu identitas institusi atau surat tugas',
                'Identitas pendaftar (KTP)',
            ],
        ],
        'media' => [
            'title' => 'Daftar sebagai Media',
            'description' => 'Untuk jurnalis dan lembaga media yang membantu mempublikasikan dan meliput event melalui platform ini.',
            'checklist' => [
                'Kartu pers atau surat tugas peliputan',
                'Identitas pemimpin redaksi atau penanggung jawab',
                'Contoh publikasi',
            ],
        ],
        'pengunjung' => [
            'title' => 'Daftar sebagai Pengunjung',
            'description' => 'Buat akun untuk melihat informasi event dan mengikuti kegiatan yang tersedia di Lokamitra.',
            'checklist' => [
                'Isi nama lengkap dan nomor HP',
                'Gunakan alamat email yang aktif',
                'Buat password untuk masuk ke Lokamitra',
            ],
        ],
    ],

    // File disimpan di disk local (private), bukan folder public.
    'dokumen_registrasi' => [
        'eo' => [
            ['key' => 'ktp_penanggung_jawab', 'jenis_dokumen' => 'KTP Penanggung Jawab', 'deskripsi' => 'Unggah KTP penanggung jawab EO yang jelas dan terbaca.', 'sifat_dokumen' => 'wajib', 'wajib' => true],
            ['key' => 'profil_legalitas_eo', 'jenis_dokumen' => 'Profil atau Legalitas EO', 'deskripsi' => 'Profil organisasi, akta, atau dokumen legalitas EO.', 'sifat_dokumen' => 'wajib', 'wajib' => true],
            ['key' => 'portofolio_event', 'jenis_dokumen' => 'Portofolio Event Sebelumnya', 'deskripsi' => 'Contoh event yang pernah diselenggarakan oleh EO.', 'sifat_dokumen' => 'wajib', 'wajib' => true],
            ['key' => 'nib_npwp', 'jenis_dokumen' => 'NIB atau NPWP (unggah salah satu)', 'deskripsi' => 'Opsional. Unggah NIB atau NPWP saja, tidak perlu keduanya.', 'sifat_dokumen' => 'opsional', 'wajib' => false],
        ],
        'vendor' => [
            ['key' => 'nib', 'jenis_dokumen' => 'NIB atau surat keterangan usaha', 'deskripsi' => 'Bukti bahwa usaha terdaftar atau memiliki keterangan usaha.', 'sifat_dokumen' => 'wajib', 'wajib' => true],
            ['key' => 'identitas_pemilik', 'jenis_dokumen' => 'Identitas pemilik (KTP)', 'deskripsi' => 'Foto KTP yang jelas dan terbaca.', 'sifat_dokumen' => 'wajib', 'wajib' => true],
            ['key' => 'profil_katalog', 'jenis_dokumen' => 'Profil atau katalog layanan', 'deskripsi' => 'Daftar produk atau jasa yang ditawarkan.', 'sifat_dokumen' => 'wajib', 'wajib' => true],
            ['key' => 'foto_portofolio', 'jenis_dokumen' => 'Foto produk atau portofolio', 'deskripsi' => 'Contoh hasil kerja atau produk usaha.', 'sifat_dokumen' => 'opsional', 'wajib' => false],
        ],
        'pemda' => [
            ['key' => 'surat_tugas', 'jenis_dokumen' => 'Surat tugas atau surat penunjukan', 'deskripsi' => 'Surat dari instansi yang menunjukkan pendaftar sebagai perwakilan di Lokamitra.', 'sifat_dokumen' => 'wajib', 'wajib' => true],
            ['key' => 'identitas_pendaftar', 'jenis_dokumen' => 'Identitas pendaftar (KTP atau ID Pegawai)', 'deskripsi' => 'Foto identitas yang jelas dan terbaca.', 'sifat_dokumen' => 'wajib', 'wajib' => true],
        ],
        'komunitas' => [
            ['key' => 'surat_keterangan', 'jenis_dokumen' => 'Surat keterangan komunitas atau bukti kegiatan', 'deskripsi' => 'Surat keterangan dari desa/kelurahan atau dokumentasi kegiatan yang pernah dilakukan.', 'sifat_dokumen' => 'wajib', 'wajib' => true],
            ['key' => 'identitas_ketua', 'jenis_dokumen' => 'Identitas ketua atau penanggung jawab', 'deskripsi' => 'Foto KTP ketua atau penanggung jawab yang jelas dan terbaca.', 'sifat_dokumen' => 'wajib', 'wajib' => true],
            ['key' => 'foto_kegiatan', 'jenis_dokumen' => 'Foto kegiatan komunitas', 'deskripsi' => 'Foto kegiatan untuk memperkuat profil komunitas.', 'sifat_dokumen' => 'opsional', 'wajib' => false],
        ],
        'akademisi' => [
            ['key' => 'identitas_institusi', 'jenis_dokumen' => 'Kartu identitas institusi atau surat tugas', 'deskripsi' => 'Kartu dosen/mahasiswa atau surat tugas dari institusi.', 'sifat_dokumen' => 'wajib', 'wajib' => true],
            ['key' => 'identitas_pendaftar', 'jenis_dokumen' => 'Identitas pendaftar (KTP)', 'deskripsi' => 'Foto identitas yang jelas dan terbaca.', 'sifat_dokumen' => 'wajib', 'wajib' => true],
        ],
        'pengunjung' => [],
        'media' => [
            ['key' => 'kartu_pers', 'jenis_dokumen' => 'Kartu pers atau surat tugas peliputan', 'deskripsi' => 'Bukti bahwa pendaftar bertugas sebagai wartawan atau perwakilan media.', 'sifat_dokumen' => 'wajib', 'wajib' => true],
            ['key' => 'identitas_pemimpin', 'jenis_dokumen' => 'Identitas pemimpin redaksi atau penanggung jawab', 'deskripsi' => 'Foto identitas yang jelas dan terbaca.', 'sifat_dokumen' => 'wajib', 'wajib' => true],
            ['key' => 'contoh_publikasi', 'jenis_dokumen' => 'Contoh publikasi', 'deskripsi' => 'Tautan atau bukti karya berita yang pernah diterbitkan.', 'sifat_dokumen' => 'opsional', 'wajib' => false],
        ],
    ],
];
