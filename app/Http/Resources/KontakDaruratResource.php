<?php

namespace App\Http\Resources;

use App\Models\KontakDaruratEvent;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin KontakDaruratEvent */
class KontakDaruratResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->kontak_darurat_event_id,
            'nama' => $this->nama,
            'nomor_telepon' => $this->nomor_telepon,
        ];
    }
}
