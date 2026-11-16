// Field profil mengikuti nama kolom pada migration dan RegisterRequest.
export type ProfilField = {
    name: string;
    label: string;
    placeholder: string;
    required?: boolean;
    full?: boolean;
    type?: 'text' | 'number' | 'url' | 'select';
    options?: string[];
};

export type DocumentRequirement = {
    key: string;
    jenis_dokumen: string;
    deskripsi: string;
    sifat_dokumen: 'wajib' | 'opsional';
    wajib: boolean;
};

export const profilFields: Record<string, ProfilField[]> = {
    eo: [
        {
            name: 'nama_eo_organisasi',
            label: 'Nama EO / organisasi',
            placeholder: 'Nama Event Organizer atau organisasi',
            required: true,
            full: true,
        },
        {
            name: 'website_media_sosial',
            label: 'Website / media sosial',
            placeholder: 'instagram.com/akunanda',
        },
        {
            name: 'jenis_event_yang_ditangani',
            label: 'Jenis event yang ditangani',
            placeholder: 'Festival, seminar, pameran',
        },
        {
            name: 'alamat_kantor',
            label: 'Alamat kantor',
            placeholder: 'Alamat kantor',
            full: true,
        },
    ],
    vendor: [
        {
            name: 'nama_usaha',
            label: 'Nama usaha',
            placeholder: 'Nama usaha atau merek',
            required: true,
        },
        {
            name: 'alamat_usaha',
            label: 'Alamat usaha',
            placeholder: 'Alamat lengkap tempat usaha',
        },
        {
            name: 'nama_pemilik',
            label: 'Nama pemilik',
            placeholder: 'Sesuai identitas pemilik',
            required: true,
        },
        {
            name: 'bidang_usaha',
            label: 'Bidang usaha',
            placeholder: 'Pilih bidang usaha',
            type: 'select',
            options: [
                'Konsumsi',
                'Dekorasi',
                'Tata suara',
                'Pencahayaan',
                'Dokumentasi',
                'Perlengkapan event',
                'Jasa lainnya',
            ],
        },
    ],
    pemda: [
        {
            name: 'nama_instansi',
            label: 'Nama instansi / dinas',
            placeholder: 'Nama instansi tempat bertugas',
            required: true,
            full: true,
        },
        {
            name: 'jabatan_pendaftar',
            label: 'Jabatan pendaftar',
            placeholder: 'Jabatan di instansi',
            required: true,
        },
        {
            name: 'wilayah_kerja',
            label: 'Wilayah kerja',
            placeholder: 'Kabupaten atau Kota',
            required: true,
        },
        {
            name: 'bidang_yang_ditangani',
            label: 'Bidang yang ditangani',
            placeholder: 'Pilih bidang',
            type: 'select',
            options: [
                'Pariwisata',
                'Kebudayaan',
                'Ekonomi kreatif',
                'Perizinan',
                'Kepemudaan',
                'Lainnya',
            ],
        },
    ],
    komunitas: [
        {
            name: 'nama_komunitas',
            label: 'Nama komunitas',
            placeholder: 'Nama kelompok atau sanggar',
            required: true,
        },
        {
            name: 'perkiraan_jumlah_anggota',
            label: 'Perkiraan jumlah anggota',
            placeholder: 'Contoh: 25',
            type: 'number',
        },
        {
            name: 'wilayah_kegiatan',
            label: 'Wilayah kegiatan',
            placeholder: 'Tempat komunitas berkegiatan',
        },
        {
            name: 'bidang_kegiatan',
            label: 'Bidang kegiatan',
            placeholder: 'Pilih bidang kegiatan',
            type: 'select',
            required: true,
            options: [
                'Seni dan Budaya',
                'Olahraga',
                'Kepemudaan',
                'Sosial',
                'Lingkungan',
                'Lainnya',
            ],
        },
    ],
    akademisi: [
        {
            name: 'nama_institusi',
            label: 'Nama institusi',
            placeholder: 'Nama perguruan tinggi',
            required: true,
        },
        {
            name: 'fakultas_unit_jurusan',
            label: 'Fakultas / unit / jurusan',
            placeholder: 'Fakultas atau unit Anda',
        },
        {
            name: 'bidang_keahlian',
            label: 'Bidang keahlian',
            placeholder: 'Contoh: Pariwisata, Budaya, dll.',
            required: true,
        },
        {
            name: 'tautan_profil_riset',
            label: 'Tautan profil riset (opsional)',
            placeholder: 'Google Scholar, SINTA, atau lainnya',
            type: 'url',
            full: true,
        },
    ],
    pengunjung: [],
    media: [
        {
            name: 'nama_media',
            label: 'Nama media',
            placeholder: 'Nama media atau lembaga pers',
            required: true,
        },
        {
            name: 'nama_pemimpin_redaksi',
            label: 'Nama pemimpin redaksi',
            placeholder: 'Nama pemimpin redaksi',
        },
        {
            name: 'wilayah_liputan',
            label: 'Wilayah liputan',
            placeholder: 'Contoh: Banyumas dan sekitarnya',
        },
        {
            name: 'jenis_media',
            label: 'Jenis media',
            placeholder: 'Pilih jenis media',
            type: 'select',
            required: true,
            options: ['Online', 'Cetak', 'Televisi', 'Radio'],
        },
    ],
};
