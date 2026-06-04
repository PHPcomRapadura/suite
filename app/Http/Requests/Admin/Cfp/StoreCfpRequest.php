<?php

namespace App\Http\Requests\Admin\Cfp;

use Illuminate\Foundation\Http\FormRequest;

class StoreCfpRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'opens_at'              => ['required', 'date'],
            'closes_at'             => ['required', 'date', 'after:opens_at'],
            'speaker_guide'         => ['nullable', 'string'],
            'max_talks_per_speaker' => ['nullable', 'integer', 'min:1', 'max:10'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'opens_at.required'              => 'A data de abertura é obrigatória.',
            'closes_at.required'             => 'A data de encerramento é obrigatória.',
            'closes_at.after'                => 'O encerramento deve ser posterior à abertura.',
            'max_talks_per_speaker.integer'  => 'O limite de propostas deve ser um número inteiro.',
            'max_talks_per_speaker.min'      => 'O limite de propostas deve ser pelo menos 1.',
            'max_talks_per_speaker.max'      => 'O limite de propostas não pode ser maior que 10.',
        ];
    }
}
