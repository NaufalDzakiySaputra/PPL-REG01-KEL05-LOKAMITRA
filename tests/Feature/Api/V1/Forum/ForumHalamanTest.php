<?php

namespace Tests\Feature\Api\V1\Forum;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

// Halaman Inertia forum dipakai bersama oleh enam peran mitra.
class ForumHalamanTest extends TestCase
{
    use ForumTestHelpers;
    use RefreshDatabase;

    public function test_eo_dapat_membuka_halaman_daftar_forum(): void
    {
        [$eo, , $event] = $this->buatEventForum();

        $this->actingAs($eo)
            ->get('/eo/forum')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('forum/Index')
                ->where('awalan', 'eo')
                ->where('bolehBuatTopik', true)
                ->has('events', 1)
                ->where('events.0.id', $event->event_id));
    }

    public function test_peserta_dapat_membuka_halaman_utas(): void
    {
        [$eo, , $event] = $this->buatEventForum();
        $forum = $this->buatForum($event, $eo);

        $komunitas = User::factory()->komunitas()->create();
        $this->jadikanPeserta($komunitas, $event);

        $this->actingAs($komunitas)
            ->get("/komunitas/forum/{$event->event_id}?topik={$forum->forum_id}")
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('forum/Show')
                ->where('awalan', 'komunitas')
                ->where('event.id', $event->event_id)
                ->where('topikAwal', $forum->forum_id));
    }

    public function test_non_peserta_tidak_dapat_membuka_halaman_utas(): void
    {
        [$eo, , $event] = $this->buatEventForum();
        $this->buatForum($event, $eo);

        $vendor = User::factory()->vendor()->create();

        $this->actingAs($vendor)
            ->get("/vendor/forum/{$event->event_id}")
            ->assertNotFound();
    }
}
