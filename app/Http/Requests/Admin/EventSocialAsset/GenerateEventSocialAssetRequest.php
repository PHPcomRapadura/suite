<?php

namespace App\Http\Requests\Admin\EventSocialAsset;

use Illuminate\Foundation\Http\FormRequest;

class GenerateEventSocialAssetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'format' => ['required', 'string', 'in:story,post'],
        ];
    }

    public function messages(): array
    {
        return [
            'format.required' => 'Selecione um formato para gerar a arte.',
            'format.in' => 'Formato inválido. Escolha story ou post.',
        ];
    }
}
