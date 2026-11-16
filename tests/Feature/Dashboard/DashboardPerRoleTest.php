<?php

namespace Tests\Feature\Dashboard;

use App\Models\Eo;
use App\Models\Event;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class DashboardPerRoleTest extends TestCase
{
    use RefreshDatabase;

    /** @return array<string, array{string, string}> */
    public static function mitra(): array
    {
        return [
            'vendor' => ['vendor', 'vendor/Dashboard'],
            'pemda' => ['pemda', 'pemda/Dashboard'],
            'komunitas' => ['komunitas', 'komunitas/Dashboard'],
            'akademisi' => ['akademisi', 'akademisi/Dashboard'],
            'media' => ['media', 'media/Dashboard'],
        ];
    }

    #[DataProvider('mitra')]
    public function test_dashboard_mitra_punya_komponen_sendiri_meski_profil_kosong(string $role, string $komponen): void
    {
        $this->actingAs(User::factory()->{$role}()->create())
            ->get(route("{$role}.dashboard"))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component($komponen)
                ->has('peran')
                ->has('statistik.tugasTotal')
                ->has('tugasTerdekat')
                ->has('eventTersedia')
                ->has('aktivitas'));
    }

    public function test_eo_tanpa_event_melihat_keadaan_kosong(): void
    {
        $user = User::factory()->eo()->create();
        Eo::create(['akun_id' => $user->id, 'nama_eo_organisasi' => 'EO Baru', 'status_verifikasi' => 'disetujui']);

        $this->actingAs($user)->get(route('eo.dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('eo/Dashboard')->where('event', null));
    }

    public function test_pengunjung_hanya_melihat_event_terverifikasi_dan_bisa_mencari(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->actingAs(User::where('email', 'pengunjung@lokamitra.test')->firstOrFail())
            ->get(route('pengunjung.dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Pengunjung/Dashboard')
                ->has('events.data', 4) // 3 terpublikasi + 1 berjalan; draft/menunggu/ditutup/arsip tidak tampil
                ->has('pilihan.kategori')
                ->has('pilihan.kota'));

        $this->get(route('pengunjung.dashboard', ['q' => 'Konser']))
            ->assertInertia(fn (Assert $page) => $page
                ->has('events.data', 1)
                ->where('events.data.0.nama', 'Konser Musik Akhir Tahun'));

        $this->get(route('pengunjung.dashboard', ['kategori' => 'MICE']))
            ->assertInertia(fn (Assert $page) => $page->has('events.data', 1));
    }

    public function test_dashboard_eo_dengan_data_demo_menghitung_persiapan_dan_tugas(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->actingAs(User::where('email', 'eo@lokamitra.test')->firstOrFail())
            ->get(route('eo.dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('eo/Dashboard')
                ->where('event.nama', 'Festival Budaya Banyumas')
                ->where('ringkasan.stakeholder', 5)
                ->where('ringkasan.jenisStakeholder', 5)
                ->where('ringkasan.tugasTotal', 12)
                ->where('ringkasan.kebutuhanTotal', 6)
                ->has('kebutuhan', 6)
                ->has('tenggat')
                ->has('progresStakeholder', 5)
                ->has('aktivitas'));
    }

    public function test_dashboard_admin_menghitung_ringkasan_dari_data_demo(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->actingAs(User::where('email', 'admin@lokamitra.test')->firstOrFail())
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/Dashboard')
                ->where('ringkasan.eventBerjalan', 1)
                ->where('ringkasan.eventTerpublikasi', 3)
                ->where('ringkasan.eventAktif', 4)
                ->where('ringkasan.eventDiarsipkan', 1)
                ->where('ringkasan.menunggu.event', 1)
                ->where('ringkasan.menunggu.akun', 1) // eo.menunggu@lokamitra.test
                ->has('pertumbuhan', 6)
                ->has('penggunaPerPeran', 7)
                ->has('perluTindakan.item')
                ->has('statusEvent', 4));
    }

    public function test_dashboard_admin_tidak_error_pada_database_kosong(): void
    {
        $this->actingAs(User::factory()->admin()->create())
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('ringkasan.eventAktif', 0)
                ->where('ringkasan.menunggu.total', 0)
                ->has('pertumbuhan', 6));
    }

    public function test_event_draft_tidak_tampil_di_katalog_pengunjung(): void
    {
        $eo = Eo::create([
            'akun_id' => User::factory()->eo()->create()->id,
            'nama_eo_organisasi' => 'EO Uji',
            'status_verifikasi' => 'disetujui',
        ]);

        Event::create([
            'eo_id' => $eo->eo_id, 'nama_event' => 'Event Draft', 'lokasi' => 'Bandung',
            'tanggal_mulai' => today()->addDays(5), 'tanggal_selesai' => today()->addDays(6),
            'status_event' => Event::STATUS_DRAFT,
        ]);

        $this->actingAs(User::factory()->pengunjung()->create())
            ->get(route('pengunjung.dashboard'))
            ->assertInertia(fn (Assert $page) => $page->has('events.data', 0));
    }
}
