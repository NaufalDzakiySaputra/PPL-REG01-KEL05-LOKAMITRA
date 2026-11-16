<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\Akademisi;
use App\Models\DokumenPersyaratan;
use App\Models\Eo;
use App\Models\KomunitasLokal;
use App\Models\Media;
use App\Models\PemerintahDaerah;
use App\Models\User;
use App\Models\VendorUmkm;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

class RegisterController extends Controller
{
    // Menampilkan formulir registrasi dan pilihan peran.
    public function create(Request $request): Response
    {
        $peran = config('lokamitra.peran_registrasi', []);
        $roleDiminta = $request->string('role')->toString();

        return Inertia::render('auth/Register', [
            'roles' => collect($peran)
                ->map(fn (string $label, string $value) => [
                    'value' => $value,
                    'label' => $label,
                ])
                ->values(),
            'defaultRole' => array_key_exists($roleDiminta, $peran)
                ? $roleDiminta
                : array_key_first($peran),
            'passwordRules' => Password::defaults()->toPasswordRulesString(),
            'roleInfo' => config('lokamitra.registrasi_info', []),
            'documentRequirements' => config('lokamitra.dokumen_registrasi', []),
        ]);
    }

    // Membuat akun, profil, dan dokumen dalam satu transaksi.
    public function store(RegisterRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $documents = $data['documents'] ?? [];

        unset($data['documents'], $data['persetujuan']);

        // Path file dibersihkan jika transaksi database gagal.
        $storedFiles = [];

        try {
            DB::transaction(function () use ($data, $documents, &$storedFiles) {
                $akun = User::create([
                    'name' => $data['name'],
                    'email' => $data['email'],
                    'nomor_telepon' => $data['nomor_telepon'],
                    'password' => $data['password'],
                    'role' => $data['role'],
                    'status_akun' => $data['role'] === User::ROLE_PENGUNJUNG
                        ? User::STATUS_AKTIF
                        : User::STATUS_MENUNGGU,
                ]);

                // Membuat profil sesuai peran yang dipilih.
                match ($data['role']) {
                    User::ROLE_EO => Eo::create([
                        'akun_id' => $akun->id,
                        'nama_eo_organisasi' => $data['nama_eo_organisasi'],
                        'website_media_sosial' => $data['website_media_sosial'] ?? null,
                        'alamat_kantor' => $data['alamat_kantor'] ?? null,
                        'jenis_event_yang_ditangani' => $data['jenis_event_yang_ditangani'] ?? null,
                        'status_verifikasi' => Eo::STATUS_MENUNGGU,
                    ]),

                    User::ROLE_VENDOR => VendorUmkm::create([
                        'akun_id' => $akun->id,
                        'nama_usaha' => $data['nama_usaha'],
                        'nama_pemilik' => $data['nama_pemilik'],
                        'alamat_usaha' => $data['alamat_usaha'] ?? null,
                        'bidang_usaha' => $data['bidang_usaha'] ?? null,
                        'status_verifikasi' => VendorUmkm::STATUS_MENUNGGU,
                    ]),

                    User::ROLE_PEMDA => PemerintahDaerah::create([
                        'akun_id' => $akun->id,
                        'nama_instansi' => $data['nama_instansi'],
                        'jabatan_pendaftar' => $data['jabatan_pendaftar'],
                        'wilayah_kerja' => $data['wilayah_kerja'],
                        'bidang_yang_ditangani' => $data['bidang_yang_ditangani'] ?? null,
                        'status_verifikasi' => PemerintahDaerah::STATUS_MENUNGGU,
                    ]),

                    User::ROLE_KOMUNITAS => KomunitasLokal::create([
                        'akun_id' => $akun->id,
                        'nama_komunitas' => $data['nama_komunitas'],
                        'perkiraan_jumlah_anggota' => $data['perkiraan_jumlah_anggota'] ?? null,
                        'wilayah_kegiatan' => $data['wilayah_kegiatan'] ?? null,
                        'bidang_kegiatan' => $data['bidang_kegiatan'],
                        'status_verifikasi' => KomunitasLokal::STATUS_MENUNGGU,
                    ]),

                    User::ROLE_AKADEMISI => Akademisi::create([
                        'akun_id' => $akun->id,
                        'nama_institusi' => $data['nama_institusi'],
                        'fakultas_unit_jurusan' => $data['fakultas_unit_jurusan'] ?? null,
                        'bidang_keahlian' => $data['bidang_keahlian'],
                        'tautan_profil_riset' => $data['tautan_profil_riset'] ?? null,
                        'status_verifikasi' => Akademisi::STATUS_MENUNGGU,
                    ]),

                    User::ROLE_MEDIA => Media::create([
                        'akun_id' => $akun->id,
                        'nama_media' => $data['nama_media'],
                        'nama_pemimpin_redaksi' => $data['nama_pemimpin_redaksi'] ?? null,
                        'wilayah_liputan' => $data['wilayah_liputan'] ?? null,
                        'jenis_media' => $data['jenis_media'],
                        'status_verifikasi' => Media::STATUS_MENUNGGU,
                    ]),

                    // Pengunjung tidak membutuhkan tabel profil tambahan.
                    User::ROLE_PENGUNJUNG => null,
                };

                // Menyimpan dokumen secara privat agar tidak dapat diakses publik.
                $requirements = config("lokamitra.dokumen_registrasi.{$data['role']}", []);

                foreach ($requirements as $requirement) {
                    $file = $documents[$requirement['key']] ?? null;

                    if (! $file) {
                        continue;
                    }

                    $path = $file->store("dokumen-persyaratan/{$akun->id}", 'local');
                    $storedFiles[] = $path;

                    DokumenPersyaratan::create([
                        'akun_id' => $akun->id,
                        'jenis_dokumen' => $requirement['jenis_dokumen'],
                        'nama_file' => $file->getClientOriginalName(),
                        'format_file' => strtolower($file->getClientOriginalExtension()),
                        'lokasi_file' => $path,
                        'sifat_dokumen' => $requirement['sifat_dokumen'],
                        'tanggal_upload' => now(),
                        'status_pemeriksaan' => 'menunggu',
                    ]);
                }
            });
        } catch (Throwable $exception) {
            Storage::disk('local')->delete($storedFiles);

            throw $exception;
        }

        $pesan = $data['role'] === User::ROLE_PENGUNJUNG
            ? 'Pendaftaran berhasil. Akun Pengunjung Anda sudah aktif. Silakan login untuk melanjutkan.'
            : 'Pendaftaran berhasil. Akun Anda menunggu verifikasi Admin sebelum dapat digunakan untuk login.';

        return to_route('login')->with('status', $pesan);
    }
}
