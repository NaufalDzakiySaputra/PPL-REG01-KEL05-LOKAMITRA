// Tipe bersama untuk Forum Diskusi (F-07 / PBI-043 s.d. PBI-048).

export type PenggunaForum = {
    id: number;
    name: string;
    role: string;
};

export type LampiranForum = {
    id: number;
    nama_file: string;
    format_file: string;
    ukuran_file: number;
    waktu_upload: string;
    pengunggah: PenggunaForum | null;
    url: string;
};

export type TopikForum = {
    id: number;
    event_id: number;
    judul: string;
    deskripsi: string | null;
    tanggal_dibuat: string;
    pembuat: PenggunaForum | null;
    jumlah_pesan: number | null;
};

export type PesanForum = {
    id: number;
    isi_pesan: string;
    waktu_kirim: string;
    pengirim: PenggunaForum | null;
    lampiran: LampiranForum[];
};

export type UtasForum = {
    topik: TopikForum;
    pesan: PesanForum[];
};

export type DaftarTopikRespons = {
    data: TopikForum[];
};

export type DetailUtasRespons = {
    data: UtasForum;
};

export type EventForum = {
    id: number;
    nama: string;
    status: string;
};
