<?php

namespace App\Http\Requests\Admin\Participants;

use Illuminate\Foundation\Http\FormRequest;

class UploadParticipantsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'csv' => ['required', 'file', 'mimes:csv,txt', 'max:10240'],
        ];
    }

    public function messages(): array
    {
        return [
            'csv.required' => 'Selecione um arquivo CSV.',
            'csv.mimes' => 'O arquivo deve ser um CSV.',
            'csv.max' => 'O arquivo deve ter no máximo 10 MB.',
        ];
    }
}
