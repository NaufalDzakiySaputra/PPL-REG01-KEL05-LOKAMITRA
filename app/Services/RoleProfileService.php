<?php

namespace App\Services;

use App\Models\RiwayatAktivitas;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RoleProfileService
{
    // Definisi field profil dibuat terpusat agar mudah dipakai kembali oleh API.
    private const FIELD_DEFINITIONS = [
        User::ROLE_EO => [
            'nama_eo_organisasi' => ['label' => 'Nama EO/organisasi', 'type' => 'text', 'required' => true, 'rules' => ['required', 'string', 'max:255']],
            'website_media_sosial' => ['label' => 'Website/media sosial', 'type' => 'url', 'required' => false, 'rules' => ['nullable', 'url', 'max:255']],
            'alamat_kantor' => ['label' => 'Alamat kantor', 'type' => 'textarea', 'required' => false, 'rules' => ['nullable', 'string', 'max:1000']],
            'jenis_event_yang_ditangani' => ['label' => 'Jenis event yang ditangani', 'type' => 'text', 'required' => false, 'rules' => ['nullable', 'string', 'max:255']],
        ],
        User::ROLE_VENDOR => [
            'nama_usaha' => ['label' => 'Nama usaha', 'type' => 'text', 'required' => true, 'rules' => ['required', 'string', 'max:255']],
            'nama_pemilik' => ['label' => 'Nama pemilik', 'type' => 'text', 'required' => true, 'rules' => ['required', 'string', 'max:255']],
            'alamat_usaha' => ['label' => 'Alamat usaha', 'type' => 'textarea', 'required' => false, 'rules' => ['nullable', 'string', 'max:1000']],
            'bidang_usaha' => ['label' => 'Bidang usaha', 'type' => 'text', 'required' => false, 'rules' => ['nullable', 'string', 'max:255']],
        ],
        User::ROLE_PEMDA => [
            'nama_instansi' => ['label' => 'Nama instansi', 'type' => 'text', 'required' => true, 'rules' => ['required', 'string', 'max:255']],
            'jabatan_pendaftar' => ['label' => 'Jabatan pendaftar', 'type' => 'text', 'required' => true, 'rules' => ['required', 'string', 'max:255']],
            'wilayah_kerja' => ['label' => 'Wilayah kerja', 'type' => 'text', 'required' => true, 'rules' => ['required', 'string', 'max:255']],
            'bidang_yang_ditangani' => ['label' => 'Bidang yang ditangani', 'type' => 'textarea', 'required' => false, 'rules' => ['nullable', 'string', 'max:1000']],
        ],
        User::ROLE_KOMUNITAS => [
            'nama_komunitas' => ['label' => 'Nama komunitas', 'type' => 'text', 'required' => true, 'rules' => ['required', 'string', 'max:255']],
            'perkiraan_jumlah_anggota' => ['label' => 'Perkiraan jumlah anggota', 'type' => 'number', 'required' => false, 'rules' => ['nullable', 'integer', 'min:1']],
            'wilayah_kegiatan' => ['label' => 'Wilayah kegiatan', 'type' => 'text', 'required' => false, 'rules' => ['nullable', 'string', 'max:255']],
            'bidang_kegiatan' => ['label' => 'Bidang kegiatan', 'type' => 'text', 'required' => true, 'rules' => ['required', 'string', 'max:255']],
        ],
        User::ROLE_AKADEMISI => [
            'nama_institusi' => ['label' => 'Nama institusi', 'type' => 'text', 'required' => true, 'rules' => ['required', 'string', 'max:255']],
            'fakultas_unit_jurusan' => ['label' => 'Fakultas/unit/jurusan', 'type' => 'text', 'required' => false, 'rules' => ['nullable', 'string', 'max:255']],
            'bidang_keahlian' => ['label' => 'Bidang keahlian', 'type' => 'text', 'required' => true, 'rules' => ['required', 'string', 'max:255']],
            'tautan_profil_riset' => ['label' => 'Tautan profil riset', 'type' => 'url', 'required' => false, 'rules' => ['nullable', 'url', 'max:255']],
        ],
        User::ROLE_MEDIA => [
            'nama_media' => ['label' => 'Nama media', 'type' => 'text', 'required' => true, 'rules' => ['required', 'string', 'max:255']],
            'nama_pemimpin_redaksi' => ['label' => 'Nama pemimpin redaksi', 'type' => 'text', 'required' => false, 'rules' => ['nullable', 'string', 'max:255']],
            'wilayah_liputan' => ['label' => 'Wilayah liputan', 'type' => 'text', 'required' => false, 'rules' => ['nullable', 'string', 'max:255']],
            'jenis_media' => ['label' => 'Jenis media', 'type' => 'text', 'required' => true, 'rules' => ['required', 'string', 'max:255']],
        ],
    ];

    private const RELATIONS = [
        User::ROLE_EO => 'eo',
        User::ROLE_VENDOR => 'vendor',
        User::ROLE_PEMDA => 'pemerintahDaerah',
        User::ROLE_KOMUNITAS => 'komunitasLokal',
        User::ROLE_AKADEMISI => 'akademisi',
        User::ROLE_MEDIA => 'media',
    ];

    public function definitions(User $user): array
    {
        if (! isset(self::FIELD_DEFINITIONS[$user->role])) {
            throw ValidationException::withMessages([
                'role' => 'Role ini tidak memiliki profil tambahan yang dapat diubah.',
            ]);
        }

        return self::FIELD_DEFINITIONS[$user->role];
    }

    public function rules(User $user): array
    {
        return collect($this->definitions($user))
            ->mapWithKeys(fn (array $field, string $key) => [$key => $field['rules']])
            ->all();
    }

    public function profile(User $user): Model
    {
        $relation = self::RELATIONS[$user->role] ?? null;

        if ($relation === null) {
            throw ValidationException::withMessages([
                'role' => 'Role ini tidak memiliki profil tambahan.',
            ]);
        }

        return $user->{$relation}()->firstOrFail();
    }

    public function fieldsForForm(User $user): array
    {
        $profile = $this->profile($user);

        return collect($this->definitions($user))
            ->map(fn (array $field, string $key) => [
                'key' => $key,
                'label' => $field['label'],
                'type' => $field['type'],
                'required' => $field['required'],
                'value' => $profile->{$key},
            ])
            ->values()
            ->all();
    }

    public function update(User $user, array $data): void
    {
        $profile = $this->profile($user);
        $allowedFields = array_keys($this->definitions($user));

        DB::transaction(function () use ($user, $profile, $data, $allowedFields): void {
            $profile->fill(Arr::only($data, $allowedFields));
            $profile->save();

            RiwayatAktivitas::create([
                'akun_id' => $user->id,
                'jenis_aktivitas' => 'profil_peran_diperbarui',
                'deskripsi' => 'Memperbarui informasi profil ' . $user->role . '.',
                'waktu_aktivitas' => now(),
            ]);
        });
    }

    public function roleLabel(User $user): string
    {
        return config("lokamitra.peran_registrasi.{$user->role}", ucfirst($user->role));
    }
}
