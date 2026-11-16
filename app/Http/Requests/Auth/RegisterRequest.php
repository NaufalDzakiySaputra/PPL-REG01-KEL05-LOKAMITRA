<?php

namespace App\Http\Requests\Auth;

use App\Concerns\PasswordValidationRules;
use App\Concerns\ProfileValidationRules;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class RegisterRequest extends FormRequest
{
    use PasswordValidationRules, ProfileValidationRules;

    public function authorize(): bool
    {
        return true;
    }

    // Mengubah email menjadi huruf kecil sebelum validasi.
    protected function prepareForValidation(): void
    {
        if (is_string($this->input('email'))) {
            $this->merge([
                'email' => Str::lower(trim($this->input('email'))),
            ]);
        }
    }

    public function rules(): array
    {
        $role = $this->input('role');
        $isPengunjung = $role === User::ROLE_PENGUNJUNG;

        $dasar = [
            'role' => [
                'required',
                'string',
                Rule::in(array_keys(config('lokamitra.peran_registrasi', []))),
            ],
            ...$this->profileRules(),
            'nomor_telepon' => [
                'required',
                'string',
                'regex:/^(\\+62|62|0)[0-9]{8,13}$/',
            ],
            'password' => $this->passwordRules(),
            // Pengunjung tidak perlu melalui persetujuan/dokumen verifikasi mitra.
            'persetujuan' => $isPengunjung ? ['prohibited'] : ['accepted'],
            'documents' => $isPengunjung ? ['prohibited'] : ['sometimes', 'array'],
        ];

        if (! $isPengunjung) {
            $dasar['documents.*'] = ['file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'];
        }

        $profil = match ($role) {
            User::ROLE_EO => [
                'nama_eo_organisasi' => ['required', 'string', 'max:255'],
                'website_media_sosial' => ['nullable', 'string', 'max:255'],
                'alamat_kantor' => ['nullable', 'string', 'max:255'],
                'jenis_event_yang_ditangani' => ['nullable', 'string', 'max:255'],
            ],
            User::ROLE_VENDOR => [
                'nama_usaha' => ['required', 'string', 'max:255'],
                'nama_pemilik' => ['required', 'string', 'max:255'],
                'alamat_usaha' => ['nullable', 'string', 'max:255'],
                'bidang_usaha' => ['nullable', 'string', 'max:255'],
            ],
            User::ROLE_PEMDA => [
                'nama_instansi' => ['required', 'string', 'max:255'],
                'jabatan_pendaftar' => ['required', 'string', 'max:255'],
                'wilayah_kerja' => ['required', 'string', 'max:255'],
                'bidang_yang_ditangani' => ['nullable', 'string', 'max:255'],
            ],
            User::ROLE_KOMUNITAS => [
                'nama_komunitas' => ['required', 'string', 'max:255'],
                'perkiraan_jumlah_anggota' => ['nullable', 'integer', 'min:1'],
                'wilayah_kegiatan' => ['nullable', 'string', 'max:255'],
                'bidang_kegiatan' => ['required', 'string', 'max:255'],
            ],
            User::ROLE_AKADEMISI => [
                'nama_institusi' => ['required', 'string', 'max:255'],
                'fakultas_unit_jurusan' => ['nullable', 'string', 'max:255'],
                'bidang_keahlian' => ['required', 'string', 'max:255'],
                'tautan_profil_riset' => ['nullable', 'url', 'max:255'],
            ],
            User::ROLE_MEDIA => [
                'nama_media' => ['required', 'string', 'max:255'],
                'nama_pemimpin_redaksi' => ['nullable', 'string', 'max:255'],
                'wilayah_liputan' => ['nullable', 'string', 'max:255'],
                'jenis_media' => ['required', 'string', 'max:255'],
            ],
            User::ROLE_PENGUNJUNG => [],
            default => [],
        };

        // Dokumen wajib dan opsional mengikuti konfigurasi masing-masing role.
        $dokumenRules = [];

        foreach (config('lokamitra.dokumen_registrasi.'.$role, []) as $dokumen) {
            $dokumenRules['documents.'.$dokumen['key']] = [
                $dokumen['wajib'] ? 'required' : 'nullable',
                'file',
                'mimes:pdf,jpg,jpeg,png',
                'max:10240',
            ];
        }

        return [...$dasar, ...$profil, ...$dokumenRules];
    }

    public function attributes(): array
    {
        $attributes = [
            'role' => 'peran',
            'name' => 'nama lengkap penanggung jawab',
            'email' => 'email',
            'nomor_telepon' => 'nomor telepon',
            'password' => 'password',
            'persetujuan' => 'persetujuan',
            'nama_eo_organisasi' => 'nama EO/organisasi',
            'website_media_sosial' => 'website/media sosial',
            'alamat_kantor' => 'alamat kantor',
            'jenis_event_yang_ditangani' => 'jenis event yang ditangani',
            'nama_usaha' => 'nama usaha',
            'nama_pemilik' => 'nama pemilik',
            'alamat_usaha' => 'alamat usaha',
            'bidang_usaha' => 'bidang usaha',
            'nama_instansi' => 'nama instansi/dinas',
            'jabatan_pendaftar' => 'jabatan pendaftar',
            'wilayah_kerja' => 'wilayah kerja',
            'bidang_yang_ditangani' => 'bidang yang ditangani',
            'nama_komunitas' => 'nama komunitas',
            'perkiraan_jumlah_anggota' => 'perkiraan jumlah anggota',
            'wilayah_kegiatan' => 'wilayah kegiatan',
            'bidang_kegiatan' => 'bidang kegiatan',
            'nama_institusi' => 'nama institusi',
            'fakultas_unit_jurusan' => 'fakultas/unit/jurusan',
            'bidang_keahlian' => 'bidang keahlian',
            'tautan_profil_riset' => 'tautan profil riset',
            'nama_media' => 'nama media',
            'nama_pemimpin_redaksi' => 'nama pemimpin redaksi',
            'wilayah_liputan' => 'wilayah liputan',
            'jenis_media' => 'jenis media',
            'documents.ktp_penanggung_jawab' => 'KTP penanggung jawab',
            'documents.profil_legalitas_eo' => 'profil atau legalitas EO',
            'documents.portofolio_event' => 'portofolio event sebelumnya',
            'documents.nib_npwp' => 'NIB atau NPWP',
        ];

        if ($this->input('role') === User::ROLE_PENGUNJUNG) {
            $attributes['name'] = 'nama lengkap';
        }

        return $attributes;
    }

    public function messages(): array
    {
        return [
            'required' => ':Attribute wajib diisi.',
            'string' => ':Attribute harus berupa teks.',
            'max' => ':Attribute maksimal :max karakter.',
            'integer' => ':Attribute harus berupa angka.',
            'min' => ':Attribute minimal :min.',
            'email' => 'Format email tidak valid.',
            'email.unique' => 'Email ini sudah terdaftar.',
            'confirmed' => 'Konfirmasi password tidak cocok.',
            'password.min' => 'Password minimal :min karakter.',
            'role.in' => 'Peran yang dipilih tidak didukung.',
            'nomor_telepon.regex' => 'Nomor telepon tidak valid (contoh: 081234567890).',
            'persetujuan.accepted' => 'Anda harus menyetujui pernyataan pendaftaran.',
            'documents.*.required' => 'Dokumen ini wajib diunggah.',
            'documents.*.mimes' => 'Dokumen harus berformat PDF, JPG, JPEG, atau PNG.',
            'documents.*.max' => 'Ukuran setiap dokumen maksimal 10 MB.',
        ];
    }
}
