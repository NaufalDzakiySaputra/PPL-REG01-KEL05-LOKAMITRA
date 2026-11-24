<?php

namespace App\Http\Resources;

use App\Models\LampiranForum;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin LampiranForum */
class LampiranResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->lampiran_id,
            'nama_file' => $this->nama_file,
            'format_file' => $this->format_file,
            'ukuran_file' => (int) $this->ukuran_file,
            'waktu_upload' => Carbon::parse($this->waktu_upload)->toIso8601String(),
            'pengunggah' => [
                'id' => $this->pengunggah?->id,
                'name' => $this->pengunggah?->name,
                'role' => $this->pengunggah?->role,
            ],
            // File privat: tautan unduh tetap melalui API terautentikasi.
            'url' => route('api.v1.forum.lampiran.download', [
                'forum' => $this->pesan?->forum_id,
                'lampiran' => $this->lampiran_id,
            ], false),
        ];
    }
}
