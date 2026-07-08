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
            'talk_id' => ['required_if:type,speaker', 'integer', 'exists:talks,id'],
            'sponsor_id' => ['required_if:type,sponsor', 'integer', 'exists:event_sponsors,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'format.required' => 'Selecione um formato para gerar a arte.',
            'format.in' => 'Formato inválido. Escolha story ou post.',
            'type.in' => 'Tipo de arte inválido.',
            'talk_id.required_if' => 'Selecione uma palestra para divulgar.',
            'talk_id.exists' => 'Palestra não encontrada.',
            'sponsor_id.required_if' => 'Selecione um patrocinador para divulgar.',
            'sponsor_id.exists' => 'Patrocinador não encontrado.',
        ];
    }

    public function type(): string
    {
        return $this->validated('type') ?: 'announcement';
    }
}
