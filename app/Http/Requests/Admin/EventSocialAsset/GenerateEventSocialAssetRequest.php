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
            'type' => ['nullable', 'string', 'in:announcement,speaker,sponsor,selling_out,tomorrow'],
        ];
    }

    public function messages(): array
    {
        return [
            'format.required' => 'Selecione um formato para gerar a arte.',
            'format.in' => 'Formato inválido. Escolha story ou post.',
            'type.in' => 'Tipo de arte inválido.',
        ];
    }

    public function type(): string
    {
        return $this->validated('type') ?: 'announcement';
    }
}
