<?php

namespace App\Http\Controllers\Api\V1\Eo;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StakeholderController extends Controller
{
    private function kategori(): array
    {
        return [
            'pemda' => [
                'role' => User::ROLE_PEMDA,
                'relation' => 'pemerintahDaerah',
                'label' => 'Pemerintah Daerah',
                'nama' => 'nama_instansi',
                'fields' => [
                    'Jabatan pendaftar' => 'jabatan_pendaftar',
                    'Wilayah kerja' => 'wilayah_kerja',
                    'Bidang yang ditangani' => 'bidang_yang_ditangani',
                ],
            ],
            'vendor' => [
                'role' => User::ROLE_VENDOR,
                'relation' => 'vendor',
                'label' => 'Vendor/UMKM',
                'nama' => 'nama_usaha',
                'fields' => [
                    'Nama pemilik' => 'nama_pemilik',
                    'Alamat usaha' => 'alamat_usaha',
                    'Bidang usaha' => 'bidang_usaha',
                ],
            ],
            'komunitas' => [
                'role' => User::ROLE_KOMUNITAS,
                'relation' => 'komunitasLokal',
                'label' => 'Komunitas Lokal',
                'nama' => 'nama_komunitas',
                'fields' => [
                    'Perkiraan jumlah anggota' => 'perkiraan_jumlah_anggota',
                    'Wilayah kegiatan' => 'wilayah_kegiatan',
                    'Bidang kegiatan' => 'bidang_kegiatan',
                ],
            ],
            'akademisi' => [
                'role' => User::ROLE_AKADEMISI,
                'relation' => 'akademisi',
                'label' => 'Akademisi',
                'nama' => 'nama_institusi',
                'fields' => [
                    'Fakultas/unit/jurusan' => 'fakultas_unit_jurusan',
                    'Bidang keahlian' => 'bidang_keahlian',
                    'Tautan profil riset' => 'tautan_profil_riset',
                ],
            ],
            'media' => [
                'role' => User::ROLE_MEDIA,
                'relation' => 'media',
                'label' => 'Media',
                'nama' => 'nama_media',
                'fields' => [
                    'Pemimpin redaksi' => 'nama_pemimpin_redaksi',
                    'Wilayah liputan' => 'wilayah_liputan',
                    'Jenis media' => 'jenis_media',
                ],
            ],
        ];
    }

    public function index(Request $request): JsonResponse
    {
        $kategori = $this->kategori();
        $filter = (string) $request->query('kategori', 'semua');
        $kataKunci = trim((string) $request->query('q', ''));

        if ($filter !== 'semua' && ! isset($kategori[$filter])) {
            $filter = 'semua';
        }

        $query = User::query()
            ->where('status_akun', User::STATUS_AKTIF)
            ->where(function ($q) use ($kategori) {
                foreach ($kategori as $item) {
                    $q->orWhere(function ($subQuery) use ($item) {
                        $subQuery
                            ->where('role', $item['role'])
                            ->whereHas($item['relation'], fn ($profile) =>
                                $profile->where('status_verifikasi', 'disetujui')
                            );
                    });
                }
            });

        if ($filter !== 'semua') {
            $item = $kategori[$filter];

            $query->where('role', $item['role'])
                ->whereHas($item['relation'], fn ($profile) =>
                    $profile->where('status_verifikasi', 'disetujui')
                );
        }

        if ($kataKunci !== '') {
            $query->where(function ($q) use ($kategori, $kataKunci) {
                $cari = '%' . $kataKunci . '%';

                $q->where('name', 'like', $cari);

                foreach ($kategori as $item) {
                    $q->orWhereHas($item['relation'], fn ($profile) =>
                        $profile->where($item['nama'], 'like', $cari)
                    );
                }
            });
        }

        $stakeholders = $query
            ->with(array_column($kategori, 'relation'))
            ->latest()
            ->paginate(12)
            ->withQueryString();

        $data = $stakeholders->getCollection()->map(
            function (User $user) use ($kategori) {
                $item = collect($kategori)->firstWhere('role', $user->role);
                $profil = $user->{$item['relation']};

                return [
                    'id' => $user->id,
                    'type' => $this->typeDariRole($user->role),
                    'kategori' => $item['label'],
                    'nama' => $profil->{$item['nama']},
                    'nama_akun' => $user->name,
                ];
            }
        );

        return response()->json([
            'data' => $data,
            'meta' => [
                'current_page' => $stakeholders->currentPage(),
                'last_page' => $stakeholders->lastPage(),
                'per_page' => $stakeholders->perPage(),
                'total' => $stakeholders->total(),
            ],
            'filter' => [
                'kategori' => $filter,
                'q' => $kataKunci,
            ],
            'kategori' => collect($kategori)->map(fn ($item, $key) => [
                'value' => $key,
                'label' => $item['label'],
            ])->values(),
        ]);
    }

    public function show(string $type, int $user): JsonResponse
    {
        $kategori = $this->kategori();

        abort_unless(isset($kategori[$type]), 404);

        $item = $kategori[$type];

        $stakeholder = User::query()
            ->whereKey($user)
            ->where('role', $item['role'])
            ->where('status_akun', User::STATUS_AKTIF)
            ->whereHas($item['relation'], fn ($profile) =>
                $profile->where('status_verifikasi', 'disetujui')
            )
            ->with($item['relation'])
            ->firstOrFail();

        $profil = $stakeholder->{$item['relation']};

        $dataProfil = collect($item['fields'])
            ->map(fn ($field, $label) => [
                'label' => $label,
                'value' => $profil->{$field},
            ])
            ->filter(fn ($field) =>
                $field['value'] !== null && $field['value'] !== ''
            )
            ->values();

        return response()->json([
            'data' => [
                'id' => $stakeholder->id,
                'type' => $type,
                'nama' => $profil->{$item['nama']},
                'nama_akun' => $stakeholder->name,
                'kategori' => $item['label'],
                'profil' => $dataProfil,
            ],
        ]);
    }

    private function typeDariRole(string $role): ?string
    {
        return collect($this->kategori())
            ->search(fn ($item) => $item['role'] === $role);
    }
}
