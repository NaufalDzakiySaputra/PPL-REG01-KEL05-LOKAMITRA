// Bentuk data dashboard yang dikirim service (app/Services/Dashboard).

export type PasanganProfil = { label: string; value: string };

export type MitraDashboardProps = {
    peran: string;
    statusVerifikasi: string | null;
    profil: PasanganProfil[];
    statistik: {
        tugasTotal: number;
        tugasSelesai: number;
        tugasTerlambat: number;
        eventDiikuti: number;
        pengajuanMenunggu: number;
        eventTersedia: number;
    };
    tugasTerdekat: {
        judul: string;
        event: string | null;
        status: string;
        hari: string;
        bulan: string;
    }[];
    eventDiikuti: EventRingkas[];
    eventTersedia: EventRingkas[];
    aktivitas: { teks: string; waktu: string }[];
};

export type EventRingkas = {
    id: number;
    nama: string;
    lokasi: string | null;
    tanggal: string;
};
