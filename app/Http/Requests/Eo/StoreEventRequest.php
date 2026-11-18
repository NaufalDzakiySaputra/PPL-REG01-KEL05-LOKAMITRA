<?php

namespace App\Http\Requests\Eo;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreEventRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->eo !== null;
    }

    public function rules(): array
    {
        return [
            'nama_event' => ['required', 'string', 'max:255'],
            'kategori_event' => ['nullable', 'string', Rule::in(config('lokamitra.kategori_event', []))],
            'tanggal_mulai' => ['required', 'date', 'after_or_equal:today'],
            'tanggal_selesai' => ['required', 'date', 'after_or_equal:tanggal_mulai'],
            'lokasi' => ['required', 'string', 'max:255'],
            'deskripsi' => ['nullable', 'string', 'max:50000'],
        ];
    }

    public function attributes(): array
    {
        return [
            'nama_event' => 'Nama Event',
            'kategori_event' => 'Kategori',
            'tanggal_mulai' => 'Tanggal Mulai',
            'tanggal_selesai' => 'Tanggal Selesai',
            'lokasi' => 'Lokasi',
            'deskripsi' => 'Deskripsi',
        ];
    }

    public function messages(): array
    {
        return [
            'required' => ':attribute wajib diisi.',
            'max' => ':attribute tidak boleh lebih dari :max karakter.',
            'date' => ':attribute harus berupa tanggal yang valid.',
            'in' => 'Pilihan :attribute tidak valid.',
            'tanggal_mulai.after_or_equal' => 'Tanggal mulai tidak boleh sebelum hari ini.',
            'tanggal_selesai.after_or_equal' => 'Tanggal selesai tidak boleh sebelum tanggal mulai.',
        ];
    }
}
