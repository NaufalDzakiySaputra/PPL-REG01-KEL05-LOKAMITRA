<?php

namespace Tests\Feature\Api\V1;

use Tests\TestCase;

class CsrfTokenApiTest extends TestCase
{
    public function test_guest_can_fetch_csrf_token(): void
    {
        $this->getJson('/api/v1/csrf-token')
            ->assertOk()
            ->assertJsonStructure(['token']);
    }

    public function test_csrf_token_is_a_non_empty_string(): void
    {
        $token = $this->getJson('/api/v1/csrf-token')->json('token');

        $this->assertIsString($token);
        $this->assertNotSame('', $token);
    }
}
