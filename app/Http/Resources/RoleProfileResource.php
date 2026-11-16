<?php

namespace App\Http\Resources;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin User */
class RoleProfileResource extends JsonResource
{
    /**
     * @param  array<int, array<string, mixed>>  $fields
     */
    public function __construct(User $resource, private readonly array $fields)
    {
        parent::__construct($resource);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'role' => $this->role,
            'role_label' => config(
                "lokamitra.peran_registrasi.{$this->role}",
                ucfirst((string) $this->role)
            ),
            'fields' => $this->fields,
        ];
    }
}
