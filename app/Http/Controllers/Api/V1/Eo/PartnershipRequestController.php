<?php

namespace App\Http\Controllers\Api\V1\Eo;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\PermintaanKerjaSama;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PartnershipRequestController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'event_id' => ['required', 'integer', 'exists:event,event_id'],
            'stakeholder_akun_id' => ['required', 'integer', 'exists:users,id'],
            'pesan' => ['nullable', 'string', 'max:2000'],
        ]);

        $eo = $request->user()->eo;

        abort_if($eo === null, 403, 'Profil EO tidak ditemukan.');

        // Pastikan event merupakan milik EO yang sedang login.
        $event = Event::query()
            ->where('event_id', $validated['event_id'])
            ->where('eo_id', $eo->eo_id)
            ->first();

        abort_if($event === null, 403, 'Event bukan milik Anda.');

        // Pemetaan kategori stakeholder.
        $kategori = [
            User::ROLE_PEMDA => 'pemerintahDaerah',
            User::ROLE_VENDOR => 'vendor',
            User::ROLE_KOMUNITAS => 'komunitasLokal',
            User::ROLE_AKADEMISI => 'akademisi',
            User::ROLE_MEDIA => 'media',
        ];

        $role = User::query()
            ->whereKey($validated['stakeholder_akun_id'])
            ->value('role');

        abort_unless(isset($kategori[$role]), 422, 'Kategori stakeholder tidak valid.');

        // Pastikan akun aktif dan profil sudah disetujui.
        $stakeholderValid = User::query()
            ->whereKey($validated['stakeholder_akun_id'])
            ->where('status_akun', User::STATUS_AKTIF)
            ->whereHas($kategori[$role], fn ($query) =>
                $query->where('status_verifikasi', 'disetujui')
            )
            ->exists();

        abort_unless($stakeholderValid, 422, 'Stakeholder belum memenuhi persyaratan.');

        // Cegah pengiriman permintaan ganda yang masih menunggu.
        $sudahAda = PermintaanKerjaSama::query()
            ->where('event_id', $validated['event_id'])
            ->where('stakeholder_akun_id', $validated['stakeholder_akun_id'])
            ->where('status', PermintaanKerjaSama::STATUS_MENUNGGU)
            ->exists();

        abort_if($sudahAda, 422, 'Permintaan kerja sama untuk event dan stakeholder ini masih menunggu.');

        $permintaan = PermintaanKerjaSama::create([
            'event_id' => $validated['event_id'],
            'stakeholder_akun_id' => $validated['stakeholder_akun_id'],
            'pesan' => $validated['pesan'] ?? null,
            'status' => PermintaanKerjaSama::STATUS_MENUNGGU,
        ]);

        return response()->json([
            'message' => 'Permintaan kerja sama berhasil dikirim.',
            'data' => [
                'id' => $permintaan->id,
                'event_id' => $permintaan->event_id,
                'stakeholder_akun_id' => $permintaan->stakeholder_akun_id,
                'pesan' => $permintaan->pesan,
                'status' => $permintaan->status,
            ],
        ], 201);
    }
}
