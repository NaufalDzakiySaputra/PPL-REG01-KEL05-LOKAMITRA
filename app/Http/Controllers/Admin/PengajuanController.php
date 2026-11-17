<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\PengajuanService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PengajuanController extends Controller
{
    public function __construct(
        private readonly PengajuanService $pengajuan
    ) {}

    // Halaman daftar hanya merender shell; datanya diambil lewat REST API.
    public function index(Request $request): Response
    {
        return Inertia::render(
            'admin/pengajuan/Index',
            $this->pengajuan->daftar($request->query())
        );
    }

    // Halaman detail hanya merender shell; datanya diambil lewat REST API.
    public function show(User $pengajuan): Response
    {
        return Inertia::render(
            'admin/pengajuan/Show',
            $this->pengajuan->detail($pengajuan)
        );
    }
}
