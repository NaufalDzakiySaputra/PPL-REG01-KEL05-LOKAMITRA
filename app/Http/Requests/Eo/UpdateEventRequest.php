<?php

namespace App\Http\Requests\Eo;

use App\Models\Event;

class UpdateEventRequest extends StoreEventRequest
{
    // Hanya EO pemilik event, dan event masih boleh diubah.
    public function authorize(): bool
    {
        $event = $this->route('event');
        $eo = $this->user()?->eo;

        return $eo !== null
            && $event instanceof Event
            && $event->eo_id === $eo->eo_id
            && $event->dapatDiubah();
    }

    public function rules(): array
    {
        $rules = parent::rules();
        $event = $this->route('event');

        // Tanggal mulai lama yang sudah lewat tidak dipaksa berubah.
        if ($this->input('tanggal_mulai') === $event->tanggal_mulai->toDateString()) {
            $rules['tanggal_mulai'] = ['required', 'date'];
        }

        return $rules;
    }
}