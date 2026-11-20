<?php

namespace App\Http\Resources;

use App\Models\Event;
use App\Models\KeanggotaanEvent;
use App\Models\KontakDaruratEvent;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Detail event untuk konsumen publik (F-06): hanya informasi yang memang
 * boleh dilihat pengunjung, termasuk rundown, layout/dokumen, dan kontak darurat.
 *
 * @mixin Event
 */
class EventPublikResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->event_id,
            'nama' => $this->nama_event,
            'deskripsi' => $this->deskripsi,
            'kategori' => $this->kategori_event,
            'lokasi' => $this->lokasi,
            'stakeholder' => $this->keanggotaan_count
                ?? $this->keanggotaan()
                    ->where('status_keanggotaan', KeanggotaanEvent::STATUS_DITERIMA)
                    ->count(),
            'tanggal_mulai' => $this->tanggal_mulai?->toDateString(),
            'tanggal_selesai' => $this->tanggal_selesai?->toDateString(),
            'sedang_berjalan' => $this->tanggal_mulai?->lte(today()) ?? false,
            'informasi_regulasi' => $this->informasi_regulasi,
            'rundown' => RundownResource::collection($this->whenLoaded('timeline')),
            'dokumen' => DokumenEventResource::collection($this->whenLoaded('dokumen')),
            // Publik hanya melihat maksimal dua kontak darurat per event.
            'kontak_darurat' => KontakDaruratResource::collection(
                $this->whenLoaded('kontakDarurat', fn () => $this->kontakDarurat
                    ->take(KontakDaruratEvent::MAKSIMAL_PER_EVENT)
                    ->values()),
            ),
        ];
    }
}
