<?php

namespace App\Http\Requests\Cfp;

use Illuminate\Foundation\Http\FormRequest;

class StoreTalkRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'title'    => ['required', 'string', 'max:255'],
            'abstract' => ['required', 'string', 'min:100', 'max:2000'],
            'duration' => ['required', 'in:25,50'],
            'level'    => ['required', 'in:iniciante,intermediario,avancado'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'title.required'    => 'O título é obrigatório.',
            'title.max'         => 'O título deve ter no máximo 255 caracteres.',
            'abstract.required' => 'O resumo é obrigatório.',
            'abstract.min'      => 'O resumo deve ter pelo menos 100 caracteres.',
            'abstract.max'      => 'O resumo deve ter no máximo 2000 caracteres.',
            'duration.required' => 'A duração é obrigatória.',
            'duration.in'       => 'A duração deve ser 25 ou 50 minutos.',
            'level.required'    => 'O nível é obrigatório.',
            'level.in'          => 'Nível inválido.',
        ];
    }
}
