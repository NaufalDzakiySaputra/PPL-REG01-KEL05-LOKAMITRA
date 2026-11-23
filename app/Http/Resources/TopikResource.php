<?php

namespace App\Http\Resources;

use App\Models\Forum;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Carbon;

/** @mixin Forum */
class TopikResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->forum_id,
            'event_id' => $this->event_id,
            'judul' => $this->judul,
            'deskripsi' => $this->deskripsi,
            'tanggal_dibuat' => Carbon::parse($this->tanggal_dibuat)->toIso8601String(),
            'pembuat' => [
                'id' => $this->pembuat?->id,
                'name' => $this->pembuat?->name,
                'role' => $this->pembuat?->role,
            ],
            'jumlah_pesan' => $this->whenCounted('pesan'),
        ];
    }
}
