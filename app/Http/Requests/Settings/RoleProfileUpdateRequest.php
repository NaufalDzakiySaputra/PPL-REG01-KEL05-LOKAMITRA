<?php

namespace App\Http\Requests\Settings;

use App\Services\RoleProfileService;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class RoleProfileUpdateRequest extends FormRequest
{
    /**
     * Aturan validasi mengikuti definisi field yang tersedia untuk role user.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return app(RoleProfileService::class)->rules($this->user());
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'required' => ':attribute wajib diisi.',
            'url' => ':attribute harus berupa URL yang valid.',
            'integer' => ':attribute harus berupa angka.',
            'min' => ':attribute minimal :min.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return collect(app(RoleProfileService::class)->definitions($this->user()))
            ->mapWithKeys(fn (array $field, string $key) => [$key => $field['label']])
            ->all();
    }
}
