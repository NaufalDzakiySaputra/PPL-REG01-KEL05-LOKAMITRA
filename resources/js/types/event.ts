// Tipe bersama untuk halaman informasi event publik (F-06) yang dipakai
// peran mitra seperti Pemerintah Daerah dan Vendor/UMKM.

export type KartuEvent = {
    id: number;
    nama: string;
    kategori: string | null;
    lokasi: string | null;
    stakeholder: number;
    warna: number;
    tanggal: { hari: string; bulan: string };
    href: string | null;
};

export type PaginatorKartu = {
    data: KartuEvent[];
    total: number;
    prev_page_url: string | null;
    next_page_url: string | null;
    current_page: number;
    last_page: number;
};

export type FilterEvent = {
    q: string;
    kategori: string;
    kota: string;
    urut: string;
};

export type PilihanEvent = {
    kategori: string[];
    kota: string[];
};

export type ButirRundown = {
    judul: string;
    deskripsi: string | null;
    waktu_mulai: string | null;
    waktu_selesai: string | null;
};

export type ButirDokumen = {
    id: number;
    nama: string;
    jenis: 'layout' | 'dokumen';
    unduh_url: string;
};

export type ButirKontak = {
    nama: string;
    nomor: string;
};

export type DetailEventData = {
    id: number;
    nama: string;
    deskripsi: string | null;
    kategori: string | null;
    lokasi: string | null;
    stakeholder: number;
    warna: number;
    tanggal_mulai: string;
    tanggal_selesai: string;
    daftar_sampai: string;
    sedang_berjalan: boolean;
    informasi_regulasi: string | null;
    rundown: ButirRundown[];
    dokumen: ButirDokumen[];
    kontak_darurat: ButirKontak[];
    // Hanya diisi pada area media (PBI-042), opsional untuk peran lain.
    penyelenggara?: {
        nama: string;
        website: string | null;
        alamat: string | null;
    };
};
