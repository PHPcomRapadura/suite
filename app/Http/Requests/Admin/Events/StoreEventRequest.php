<?php

namespace App\Http\Requests\Admin\Events;

use Illuminate\Foundation\Http\FormRequest;

class StoreEventRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'unique:events,slug', 'regex:/^[a-z0-9-]+$/'],
            'edition' => ['nullable', 'integer', 'min:1', 'max:999'],
            'description' => ['nullable', 'string'],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['nullable', 'date', 'after:starts_at'],
            'location' => ['nullable', 'string', 'max:255'],
            'is_online' => ['boolean'],
            'max_attendees' => ['nullable', 'integer', 'min:1'],
            'cover_image' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'logo' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,svg', 'max:2048'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'O nome do evento é obrigatório.',
            'slug.unique' => 'Este slug já está em uso.',
            'slug.regex' => 'O slug deve conter apenas letras minúsculas, números e hífens.',
            'starts_at.required' => 'A data de início é obrigatória.',
            'starts_at.date' => 'Data de início inválida.',
            'ends_at.after' => 'A data de encerramento deve ser posterior ao início.',
            'max_attendees.min' => 'A capacidade deve ser pelo menos 1.',
            'cover_image.mimes' => 'A imagem de capa deve ser jpg, jpeg, png ou webp.',
            'cover_image.max' => 'A imagem de capa deve ter no máximo 5 MB.',
            'logo.mimes' => 'O logo deve ser jpg, jpeg, png, webp ou svg.',
            'logo.max' => 'O logo deve ter no máximo 2 MB.',
        ];
    }
}
