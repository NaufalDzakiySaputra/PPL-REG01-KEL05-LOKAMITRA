<?php

namespace App\Http\Resources;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Carbon;

/** @mixin User */
class UserResource extends JsonResource
{
    /**
     * Bentuk data user yang aman untuk dikonsumsi frontend/API.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'nomor_telepon' => $this->nomor_telepon,
            'role' => $this->role,
            'role_label' => config(
                "lokamitra.peran_registrasi.{$this->role}",
                ucfirst((string) $this->role)
            ),
            'status_akun' => $this->status_akun,
            'email_verified_at' => $this->formatTanggal($this->email_verified_at),
            'created_at' => $this->formatTanggal($this->created_at),
            'updated_at' => $this->formatTanggal($this->updated_at),
        ];
    }

    private function formatTanggal(string|\DateTimeInterface|null $tanggal): ?string
    {
        if ($tanggal === null || $tanggal === '') {
            return null;
        }

        return Carbon::parse($tanggal)->toIso8601String();
    }
}
