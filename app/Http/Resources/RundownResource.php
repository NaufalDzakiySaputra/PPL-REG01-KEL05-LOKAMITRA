<?php

namespace App\Http\Resources;

use App\Models\Timeline;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Timeline */
class RundownResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->timeline_id,
            'judul' => $this->judul_aktivitas,
            'deskripsi' => $this->deskripsi,
            'waktu_mulai' => $this->waktu_mulai?->toIso8601String(),
            'waktu_selesai' => $this->waktu_selesai?->toIso8601String(),
        ];
    }
}
