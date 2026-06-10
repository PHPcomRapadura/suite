<?php

namespace App\Http\Requests\Admin\EventSite;

use Illuminate\Foundation\Http\FormRequest;

class StoreEventScheduleItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'talk_id' => ['nullable', 'integer', 'exists:talks,id'],
            'title' => ['nullable', 'string', 'max:255'],
            'speaker_name' => ['nullable', 'string', 'max:255'],
            'starts_at' => ['required', 'date'],
            'duration' => ['nullable', 'integer', 'min:5', 'max:480'],
            'room' => ['nullable', 'string', 'max:100'],
            'type' => ['required', 'string', 'in:palestra,intervalo,abertura,encerramento,outro'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'starts_at.required' => 'O horário de início é obrigatório.',
            'starts_at.date' => 'Informe uma data e hora válida.',
            'type.required' => 'O tipo do slot é obrigatório.',
            'type.in' => 'Tipo inválido.',
            'duration.min' => 'A duração mínima é 5 minutos.',
            'duration.max' => 'A duração máxima é 480 minutos.',
        ];
    }
}
