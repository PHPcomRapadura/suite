<?php

namespace App\Http\Requests\Admin\EventSite;

use Illuminate\Foundation\Http\FormRequest;

class StoreEventSponsorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'logo' => ['nullable', 'image', 'max:2048'],
            'website_url' => ['nullable', 'url', 'max:500'],
            'level' => ['required', 'string', 'in:rapadura_tradicional,rapadura_com_coco,rapadura_com_castanha'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'name.required' => 'O nome do patrocinador é obrigatório.',
            'logo.image' => 'O logo deve ser uma imagem.',
            'logo.max' => 'O logo deve ter no máximo 2 MB.',
            'website_url.url' => 'Informe uma URL válida para o site do patrocinador.',
            'level.required' => 'O nível do patrocínio é obrigatório.',
            'level.in' => 'Nível de patrocínio inválido.',
        ];
    }
}
