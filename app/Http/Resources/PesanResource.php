<?php

namespace App\Http\Resources;

use App\Models\PesanForum;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Carbon;

/** @mixin PesanForum */
class PesanResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->pesan_forum_id,
            'isi_pesan' => $this->isi_pesan,
            'waktu_kirim' => Carbon::parse($this->waktu_kirim)->toIso8601String(),
            'pengirim' => [
                'id' => $this->pengirim?->id,
                'name' => $this->pengirim?->name,
                'role' => $this->pengirim?->role,
            ],
        ];
    }
}
