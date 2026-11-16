<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DokumenPersyaratan;
use App\Models\User;
use App\Services\VerifikasiPendaftaranService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PengajuanController extends Controller
{
    public function __construct(
        private readonly VerifikasiPendaftaranService $verifikasi
    ) {}

    // Menampilkan daftar seluruh akun yang mendaftar melalui halaman publik.
    public function index(Request $request): Response
    {
        $status = $request->query('status', User::STATUS_MENUNGGU);
        $role = $request->query('role', 'semua');
        $kataKunci = trim((string) $request->query('q', ''));

        if (! in_array($status, ['semua', User::STATUS_MENUNGGU, User::STATUS_AKTIF, User::STATUS_DITOLAK], true)) {
            $status = User::STATUS_MENUNGGU;
        }

        if ($role !== 'semua' && ! in_array($role, $this->verifikasi->rolePendaftar(), true)) {
            $role = 'semua';
        }

        $query = User::query()
            ->whereIn('role', $this->verifikasi->rolePendaftar())
            ->with([
                'eo',
                'vendor',
                'pemerintahDaerah',
                'komunitasLokal',
                'akademisi',
                'media',
            ])
            ->withCount('dokumenPersyaratan')
            ->withCount([
                'dokumenPersyaratan as dokumen_menunggu_count' => fn ($q) => $q->where('status_pemeriksaan', 'menunggu'),
            ]);

        if ($status !== 'semua') {
            $query->where('status_akun', $status);
        }

        if ($role !== 'semua') {
            $query->where('role', $role);
        }

        if ($kataKunci !== '') {
            $query->where(function ($q) use ($kataKunci) {
                $cari = '%'.$kataKunci.'%';

                $q->where('name', 'like', $cari)
                    ->orWhere('email', 'like', $cari)
                    ->orWhereHas('eo', fn ($profil) => $profil->where('nama_eo_organisasi', 'like', $cari))
                    ->orWhereHas('vendor', fn ($profil) => $profil->where('nama_usaha', 'like', $cari))
                    ->orWhereHas('pemerintahDaerah', fn ($profil) => $profil->where('nama_instansi', 'like', $cari))
                    ->orWhereHas('komunitasLokal', fn ($profil) => $profil->where('nama_komunitas', 'like', $cari))
                    ->orWhereHas('akademisi', fn ($profil) => $profil->where('nama_institusi', 'like', $cari))
                    ->orWhereHas('media', fn ($profil) => $profil->where('nama_media', 'like', $cari));
            });
        }

        $pengajuan = $query
            ->latest()
            ->paginate(10)
            ->withQueryString()
            ->through(fn (User $user) => [
                'id' => $user->id,
                'nama' => $user->name,
                'email' => $user->email,
                'nomor_telepon' => $user->nomor_telepon,
                'role' => $user->role,
                'role_label' => config("lokamitra.peran_registrasi.{$user->role}"),
                'nama_profil' => $this->namaProfil($user),
                'status_akun' => $user->status_akun,
                'jumlah_dokumen' => $user->dokumen_persyaratan_count,
                'dokumen_menunggu' => $user->dokumen_menunggu_count,
                'tanggal_daftar' => $user->created_at?->toIso8601String(),
            ]);

        $ringkasan = [
            'menunggu' => User::whereIn('role', $this->verifikasi->rolePendaftar())
                ->where('status_akun', User::STATUS_MENUNGGU)->count(),
            'aktif' => User::whereIn('role', $this->verifikasi->rolePendaftar())
                ->where('status_akun', User::STATUS_AKTIF)->count(),
            'ditolak' => User::whereIn('role', $this->verifikasi->rolePendaftar())
                ->where('status_akun', User::STATUS_DITOLAK)->count(),
        ];

        return Inertia::render('admin/pengajuan/Index', [
            'pengajuan' => $pengajuan,
            'ringkasan' => $ringkasan,
            'filter' => [
                'status' => $status,
                'role' => $role,
                'q' => $kataKunci,
            ],
            'roles' => collect(config('lokamitra.peran_registrasi', []))
                ->map(fn (string $label, string $value) => ['value' => $value, 'label' => $label])
                ->values(),
        ]);
    }

    // Menampilkan data akun, profil, dan dokumen yang diajukan.
    public function show(User $pengajuan): Response
    {
        abort_unless(in_array($pengajuan->role, $this->verifikasi->rolePendaftar(), true), 404);

        $pengajuan->load([
            'eo',
            'vendor',
            'pemerintahDaerah',
            'komunitasLokal',
            'akademisi',
            'media',
            'dokumenPersyaratan' => fn ($q) => $q->orderBy('created_at'),
        ]);

        $profil = $this->verifikasi->profil($pengajuan);
        $dokumen = $pengajuan->dokumenPersyaratan->map(fn (DokumenPersyaratan $item) => [
            'id' => $item->dokumen_id,
            'jenis' => $item->jenis_dokumen,
            'nama_file' => $item->nama_file,
            'format' => strtoupper($item->format_file),
            'sifat' => $item->sifat_dokumen,
            'tanggal_upload' => $item->tanggal_upload?->toIso8601String(),
            'status' => $item->status_pemeriksaan,
            'catatan_admin' => $item->catatan_admin,
            'preview_url' => route('admin.pengajuan.documents.preview', [$pengajuan, $item]),
            'download_url' => route('admin.pengajuan.documents.download', [$pengajuan, $item]),
        ])->values();

        $kendala = $this->verifikasi->kendalaPersetujuan($pengajuan);

        return Inertia::render('admin/pengajuan/Show', [
            'pengajuan' => [
                'id' => $pengajuan->id,
                'nama' => $pengajuan->name,
                'email' => $pengajuan->email,
                'nomor_telepon' => $pengajuan->nomor_telepon,
                'role' => $pengajuan->role,
                'role_label' => config("lokamitra.peran_registrasi.{$pengajuan->role}"),
                'status_akun' => $pengajuan->status_akun,
                'tanggal_daftar' => $pengajuan->created_at?->toIso8601String(),
                'profil' => $this->dataProfil($pengajuan, $profil),
                'dokumen' => $dokumen,
            ],
            'kendala_persetujuan' => $kendala,
            'dapat_disetujui' => $pengajuan->status_akun === User::STATUS_MENUNGGU && $kendala === [],
        ]);
    }

    // Menyetujui pengajuan setelah syarat profil dan dokumen terpenuhi.
    public function approve(User $pengajuan): RedirectResponse
    {
        $this->verifikasi->setujuiAkun($pengajuan, request()->user());

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Akun pendaftar berhasil disetujui dan sekarang dapat login.',
        ]);

        return to_route('admin.pengajuan.show', $pengajuan);
    }

    // Menolak pengajuan dengan alasan yang akan disampaikan kepada pendaftar.
    public function reject(Request $request, User $pengajuan): RedirectResponse
    {
        $data = $request->validate([
            'alasan' => ['required', 'string', 'min:5', 'max:1000'],
        ], [
            'alasan.required' => 'Alasan penolakan wajib diisi.',
            'alasan.min' => 'Alasan penolakan minimal 5 karakter.',
            'alasan.max' => 'Alasan penolakan maksimal 1000 karakter.',
        ]);

        $this->verifikasi->tolakAkun($pengajuan, $request->user(), $data['alasan']);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Pengajuan berhasil ditolak. Pendaftar akan menerima notifikasi.',
        ]);

        return to_route('admin.pengajuan.show', $pengajuan);
    }

    // Memeriksa satu dokumen secara terpisah.
    public function reviewDocument(Request $request, User $pengajuan, DokumenPersyaratan $dokumen): RedirectResponse
    {
        $data = $request->validate([
            'status_pemeriksaan' => ['required', Rule::in(['disetujui', 'ditolak'])],
            'catatan_admin' => [
                Rule::requiredIf($request->input('status_pemeriksaan') === 'ditolak'),
                'nullable',
                'string',
                'min:5',
                'max:1000',
            ],
        ], [
            'catatan_admin.required' => 'Catatan wajib diisi jika dokumen ditolak.',
            'catatan_admin.min' => 'Catatan minimal 5 karakter.',
            'status_pemeriksaan.in' => 'Status pemeriksaan tidak valid.',
        ]);

        $this->verifikasi->periksaDokumen(
            $pengajuan,
            $dokumen,
            $request->user(),
            $data['status_pemeriksaan'],
            $data['catatan_admin'] ?? null
        );

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Hasil pemeriksaan dokumen berhasil disimpan.',
        ]);

        return to_route('admin.pengajuan.show', $pengajuan);
    }

    // Menampilkan PDF atau gambar secara inline dari storage privat.
    public function previewDocument(User $pengajuan, DokumenPersyaratan $dokumen): BinaryFileResponse
    {
        abort_unless((int) $dokumen->akun_id === (int) $pengajuan->id, 404);

        $disk = Storage::disk('local');
        abort_unless($disk->exists($dokumen->lokasi_file), 404);

        $mime = $disk->mimeType($dokumen->lokasi_file);
        abort_unless(in_array($mime, ['application/pdf', 'image/jpeg', 'image/png'], true), 415);

        return response()->file($disk->path($dokumen->lokasi_file), [
            'Content-Type' => $mime,
            'Content-Disposition' => 'inline',
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, no-store, max-age=0',
            'Pragma' => 'no-cache',
        ]);
    }

    // Mengunduh dokumen dari storage privat. Dokumen harus milik pengajuan pada URL.
    public function downloadDocument(User $pengajuan, DokumenPersyaratan $dokumen): StreamedResponse
    {
        abort_unless((int) $dokumen->akun_id === (int) $pengajuan->id, 404);
        abort_unless(Storage::disk('local')->exists($dokumen->lokasi_file), 404);

        return Storage::disk('local')->download(
            $dokumen->lokasi_file,
            basename($dokumen->nama_file)
        );
    }

    private function namaProfil(User $user): ?string
    {
        $profil = $this->verifikasi->profil($user);

        return match ($user->role) {
            User::ROLE_EO => $profil?->nama_eo_organisasi,
            User::ROLE_VENDOR => $profil?->nama_usaha,
            User::ROLE_PEMDA => $profil?->nama_instansi,
            User::ROLE_KOMUNITAS => $profil?->nama_komunitas,
            User::ROLE_AKADEMISI => $profil?->nama_institusi,
            User::ROLE_MEDIA => $profil?->nama_media,
            default => null,
        };
    }

    private function dataProfil(User $user, mixed $profil): array
    {
        $data = match ($user->role) {
            User::ROLE_EO => [
                'Nama organisasi/EO' => $profil?->nama_eo_organisasi,
                'Website/media sosial' => $profil?->website_media_sosial,
                'Alamat kantor' => $profil?->alamat_kantor,
                'Jenis event yang ditangani' => $profil?->jenis_event_yang_ditangani,
            ],
            User::ROLE_VENDOR => [
                'Nama usaha' => $profil?->nama_usaha,
                'Nama pemilik' => $profil?->nama_pemilik,
                'Alamat usaha' => $profil?->alamat_usaha,
                'Bidang usaha' => $profil?->bidang_usaha,
            ],
            User::ROLE_PEMDA => [
                'Nama instansi' => $profil?->nama_instansi,
                'Jabatan pendaftar' => $profil?->jabatan_pendaftar,
                'Wilayah kerja' => $profil?->wilayah_kerja,
                'Bidang yang ditangani' => $profil?->bidang_yang_ditangani,
            ],
            User::ROLE_KOMUNITAS => [
                'Nama komunitas' => $profil?->nama_komunitas,
                'Perkiraan jumlah anggota' => $profil?->perkiraan_jumlah_anggota,
                'Wilayah kegiatan' => $profil?->wilayah_kegiatan,
                'Bidang kegiatan' => $profil?->bidang_kegiatan,
            ],
            User::ROLE_AKADEMISI => [
                'Nama institusi' => $profil?->nama_institusi,
                'Fakultas/unit/jurusan' => $profil?->fakultas_unit_jurusan,
                'Bidang keahlian' => $profil?->bidang_keahlian,
                'Tautan profil riset' => $profil?->tautan_profil_riset,
            ],
            User::ROLE_MEDIA => [
                'Nama media' => $profil?->nama_media,
                'Pemimpin redaksi' => $profil?->nama_pemimpin_redaksi,
                'Wilayah liputan' => $profil?->wilayah_liputan,
                'Jenis media' => $profil?->jenis_media,
            ],
            default => [],
        };

        return collect($data)
            ->filter(fn ($value) => $value !== null && $value !== '')
            ->map(fn ($value, $label) => ['label' => $label, 'value' => (string) $value])
            ->values()
            ->all();
    }
}
