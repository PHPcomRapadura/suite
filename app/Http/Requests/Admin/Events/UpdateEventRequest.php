<?php

namespace App\Http\Requests\Admin\Events;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateEventRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => [
                'nullable', 'string', 'max:255',
                Rule::unique('events', 'slug')->ignore($this->route('event')),
                'regex:/^[a-z0-9-]+$/',
            ],
            'edition' => ['nullable', 'integer', 'min:1', 'max:999'],
            'description' => ['nullable', 'string'],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'location' => ['nullable', 'string', 'max:255'],
            'latitude' => ['nullable', 'required_with:longitude', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'required_with:latitude', 'numeric', 'between:-180,180'],
            'is_online' => ['boolean'],
            'max_attendees' => ['nullable', 'integer', 'min:1'],
            'cover_image' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'social_base_image' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'logo' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'remove_social_base' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'O nome do evento é obrigatório.',
            'latitude.required_with' => 'Informe a latitude e a longitude juntas.',
            'longitude.required_with' => 'Informe a latitude e a longitude juntas.',
            'latitude.numeric' => 'Informe uma latitude numérica.',
            'longitude.numeric' => 'Informe uma longitude numérica.',
            'latitude.between' => 'A latitude deve estar entre -90 e 90.',
            'longitude.between' => 'A longitude deve estar entre -180 e 180.',
            'slug.unique' => 'Este slug já está em uso.',
            'slug.regex' => 'O slug deve conter apenas letras minúsculas, números e hífens.',
            'starts_at.required' => 'A data de início é obrigatória.',
            'starts_at.date' => 'Data de início inválida.',
            'ends_at.after_or_equal' => 'A data de encerramento não pode ser anterior ao início.',
            'max_attendees.min' => 'A capacidade deve ser pelo menos 1.',
            'cover_image.mimes' => 'A imagem de capa deve ser jpg, jpeg, png ou webp.',
            'cover_image.max' => 'A imagem de capa deve ter no máximo 5 MB.',
            'social_base_image.mimes' => 'A imagem base das redes sociais deve ser jpg, jpeg, png ou webp.',
            'social_base_image.max' => 'A imagem base das redes sociais deve ter no máximo 5 MB.',
            'logo.mimes' => 'O logo deve ser jpg, jpeg, png ou webp.',
            'logo.max' => 'O logo deve ter no máximo 2 MB.',
        ];
    }
}
