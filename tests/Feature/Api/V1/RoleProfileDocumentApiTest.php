<?php

namespace Tests\Feature\Api\V1;

use App\Models\DokumenPersyaratan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class RoleProfileDocumentApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
    }

    /** @param array<string, mixed> $data */
    private function unggah(array $data)
    {
        return $this->post('/api/v1/role-profile/dokumen', $data, [
            'Accept' => 'application/json',
        ]);
    }

    public function test_guest_cannot_upload_role_profile_document(): void
    {
        $this->unggah([
            'jenis_dokumen' => 'SK_KOMUNITAS',
            'file_dokumen' => UploadedFile::fake()->create('sk.pdf', 10, 'application/pdf'),
        ])->assertUnauthorized();
    }

    public function test_partner_can_upload_document_and_get_private_file_url(): void
    {
        $user = User::factory()->komunitas()->create();

        $response = $this->actingAs($user)
            ->unggah([
                'jenis_dokumen' => 'SK_KOMUNITAS',
                'file_dokumen' => UploadedFile::fake()->create('sk-banyumas.pdf', 20, 'application/pdf'),
            ])
            ->assertCreated()
            ->assertJsonPath('message', 'Dokumen persyaratan berhasil diunggah.')
            ->assertJsonPath('data.jenis_dokumen', 'SK_KOMUNITAS');

        $dokumen = DokumenPersyaratan::findOrFail($response->json('data.id'));

        $this->assertSame($user->id, $dokumen->akun_id);
        $this->assertSame('opsional', $dokumen->sifat_dokumen);
        $this->assertSame(
            "/api/v1/role-profile/dokumen/{$dokumen->dokumen_id}/download",
            $response->json('data.file_url')
        );
        Storage::disk('local')->assertExists($dokumen->lokasi_file);
    }

    public function test_uploaded_document_can_be_downloaded_only_by_its_owner(): void
    {
        $user = User::factory()->vendor()->create();
        $other = User::factory()->vendor()->create();

        $response = $this->actingAs($user)
            ->unggah([
                'jenis_dokumen' => 'NIB',
                'file_dokumen' => UploadedFile::fake()->create('nib.jpg', 20, 'image/jpeg'),
            ])
            ->assertCreated();

        $url = $response->json('data.file_url');

        $this->actingAs($user)->get($url)->assertOk();
        $this->actingAs($other)->get($url)->assertNotFound();
    }

    public function test_document_upload_rejects_invalid_file_type_and_oversized_file(): void
    {
        $user = User::factory()->vendor()->create();

        $this->actingAs($user)
            ->unggah([
                'jenis_dokumen' => 'NIB',
                'file_dokumen' => UploadedFile::fake()->create('nib.txt', 10, 'text/plain'),
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('file_dokumen');

        $this->actingAs($user)
            ->unggah([
                'jenis_dokumen' => 'NIB',
                'file_dokumen' => UploadedFile::fake()->create('nib.pdf', 6000, 'application/pdf'),
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('file_dokumen');

        $this->assertDatabaseCount('dokumen_persyaratan', 0);
    }

    public function test_document_upload_requires_jenis_dokumen(): void
    {
        $user = User::factory()->vendor()->create();

        $this->actingAs($user)
            ->unggah([
                'file_dokumen' => UploadedFile::fake()->create('nib.pdf', 10, 'application/pdf'),
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('jenis_dokumen');
    }

    public function test_visitor_cannot_upload_role_profile_document(): void
    {
        $visitor = User::factory()->pengunjung()->create();

        $this->actingAs($visitor)
            ->unggah([
                'jenis_dokumen' => 'NIB',
                'file_dokumen' => UploadedFile::fake()->create('nib.pdf', 10, 'application/pdf'),
            ])
            ->assertForbidden();
    }
}
