<?php

namespace App\Http\Requests\Eo;

use App\Models\Event;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Dasar validasi informasi publik event (rundown, layout/dokumen, kontak darurat).
 * Hanya EO pemilik event yang masih boleh diubah yang dapat menulis.
 */
abstract class InformasiPublikRequest extends FormRequest
{
    public function authorize(): bool
    {
        $event = $this->event();
        $eo = $this->user()?->eo;

        return $eo !== null
            && $event !== null
            && $event->eo_id === $eo->eo_id
            && $event->dapatDiubah();
    }

    protected function event(): ?Event
    {
        $event = $this->route('event');

        return $event instanceof Event ? $event : null;
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'judul_aktivitas' => 'Judul aktivitas',
            'deskripsi' => 'Deskripsi',
            'waktu_mulai' => 'Waktu mulai',
            'waktu_selesai' => 'Waktu selesai',
            'nama_dokumen' => 'Nama dokumen',
            'jenis' => 'Jenis',
            'file' => 'Berkas',
            'nama' => 'Nama kontak',
            'nomor_telepon' => 'Nomor telepon',
        ];
    }
}
