<?php

namespace App\Http\Requests\Cfp;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSpeakerProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $ufs = ['AC','AL','AM','AP','BA','CE','DF','ES','GO','MA','MG','MS','MT',
                'PA','PB','PE','PI','PR','RJ','RN','RO','RR','RS','SC','SE','SP','TO'];

        return [
            'bio'          => ['required', 'string', 'max:1000'],
            'company'      => ['nullable', 'string', 'max:255'],
            'city'         => ['nullable', 'string', 'max:255'],
            'state'        => ['nullable', 'string', 'in:'.implode(',', $ufs)],
            'avatar'       => ['nullable', 'image', 'max:2048'],
            'phone_number' => ['nullable', 'string', 'max:20'],
            'website'      => ['nullable', 'url', 'max:500'],
            'twitter'      => ['nullable', 'string', 'max:100'],
            'github'       => ['nullable', 'string', 'max:100'],
            'linkedin'     => ['nullable', 'string', 'max:255'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'bio.required' => 'A bio é obrigatória.',
            'bio.max'      => 'A bio deve ter no máximo 1000 caracteres.',
            'website.url'  => 'Informe uma URL válida para o site.',
            'avatar.image' => 'O avatar deve ser uma imagem.',
            'avatar.max'   => 'O avatar deve ter no máximo 2 MB.',
            'state.in'     => 'Selecione uma UF válida.',
        ];
    }
}
