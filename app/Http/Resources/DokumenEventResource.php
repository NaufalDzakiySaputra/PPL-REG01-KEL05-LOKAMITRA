<?php

namespace App\Http\Resources;

use App\Models\DokumenEvent;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin DokumenEvent */
class DokumenEventResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->dokumen_event_id,
            'nama' => $this->nama_dokumen,
            'jenis' => $this->jenis ?? DokumenEvent::JENIS_DOKUMEN,
            'diunggah_pada' => $this->tanggal_upload?->toIso8601String(),
            // Unduhan selalu lewat route terkontrol agar status event tetap dicek.
            'unduh_url' => route('events.dokumen', [
                $this->event_id,
                $this->dokumen_event_id,
            ], false),
        ];
    }
}
