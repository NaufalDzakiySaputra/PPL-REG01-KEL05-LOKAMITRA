<?php

namespace App\Http\Resources;

use App\Models\Event;
use App\Support\FormatTanggal;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Event */
class EventResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->event_id,
            'nama_event' => $this->nama_event,
            'kategori_event' => $this->kategori_event,
            'tanggal_mulai' => $this->tanggal_mulai?->toDateString(),
            'tanggal_selesai' => $this->tanggal_selesai?->toDateString(),
            'periode' => $this->periode(),
            'lokasi' => $this->lokasi,
            'deskripsi' => $this->deskripsi,
            'status' => $this->status_event,
            'status_label' => $this->statusLabel((string) $this->status_event),
        ];
    }

    private function periode(): string
    {
        if (! $this->tanggal_mulai || ! $this->tanggal_selesai) {
            return '';
        }

        $mulai = FormatTanggal::ringkas($this->tanggal_mulai);

        if ($this->tanggal_selesai->isSameDay($this->tanggal_mulai)) {
            return $mulai;
        }

        return $mulai.' - '.FormatTanggal::ringkas($this->tanggal_selesai);
    }

    private function statusLabel(string $status): string
    {
        return match ($status) {
            Event::STATUS_DRAFT => 'Draft',
            Event::STATUS_MENUNGGU_VERIFIKASI => 'Menunggu Verifikasi',
            Event::STATUS_DIPUBLIKASIKAN => 'Terverifikasi',
            Event::STATUS_DITUTUP => 'Ditutup',
            Event::STATUS_DIARSIPKAN => 'Diarsipkan',
            default => ucwords(str_replace('_', ' ', $status)),
        };
    }
}
